// backend/src/services/order.service.js
import { db, FieldValue } from '../config/firebaseAdmin.js';
import { ApiError } from '../middleware/errorHandler.js';

const ordersRef = db.collection('orders');
const productsRef = db.collection('products');

/**
 * Places an order inside a Firestore transaction so two buyers racing for the
 * last sack of fertilizer can't both succeed. Prices come from the server's
 * copy of the product, never from the client payload.
 */
export async function createOrder(buyerId, items) {
  const orderId = ordersRef.doc().id;

  await db.runTransaction(async (tx) => {
    const productSnaps = await Promise.all(
      items.map((item) => tx.get(productsRef.doc(item.productId)))
    );

    const lineItems = [];
    let total = 0;

    productSnaps.forEach((snap, i) => {
      const requested = items[i].qty;

      if (!snap.exists) {
        throw new ApiError(404, `Product ${items[i].productId} no longer exists`);
      }
      const product = snap.data();
      if (product.status !== 'active') {
        throw new ApiError(400, `${product.name} is not available right now`);
      }
      if (product.stock < requested) {
        throw new ApiError(400, `Only ${product.stock} ${product.unit} of ${product.name} left`);
      }

      const lineTotal = product.price * requested;
      total += lineTotal;

      lineItems.push({
        productId: snap.id,
        name: product.name,
        unit: product.unit,
        price: product.price,
        qty: requested,
        lineTotal,
        sellerId: product.ownerId,
      });

      tx.update(snap.ref, { stock: product.stock - requested });
    });

    tx.set(ordersRef.doc(orderId), {
      buyerId,
      items: lineItems,
      // flattened for `array-contains` queries — Firestore can't filter inside
      // an array of objects, so seller lookups need this denormalized field
      sellerIds: [...new Set(lineItems.map((i) => i.sellerId))],
      total: Number(total.toFixed(2)),
      status: 'placed',
      createdAt: FieldValue.serverTimestamp(),
      updatedAt: FieldValue.serverTimestamp(),
    });
  });

  return getOrder(orderId);
}

export async function getOrder(id) {
  const snap = await ordersRef.doc(id).get();
  if (!snap.exists) throw new ApiError(404, 'Order not found');
  return { id: snap.id, ...snap.data() };
}

export async function listOrdersForBuyer(buyerId, limit = 25) {
  const snap = await ordersRef
    .where('buyerId', '==', buyerId)
    .orderBy('createdAt', 'desc')
    .limit(limit)
    .get();
  return snap.docs.map((d) => ({ id: d.id, ...d.data() }));
}

export async function listOrdersForSeller(sellerId, limit = 50) {
  // Firestore can't query inside array-of-objects, so we keep a flattened
  // sellerIds array for this access pattern.
  const snap = await ordersRef
    .where('sellerIds', 'array-contains', sellerId)
    .orderBy('createdAt', 'desc')
    .limit(limit)
    .get();
  return snap.docs.map((d) => ({ id: d.id, ...d.data() }));
}

export async function listAllOrders({ status, limit = 50 } = {}) {
  let query = ordersRef.orderBy('createdAt', 'desc');
  if (status) query = query.where('status', '==', status);
  const snap = await query.limit(limit).get();
  return snap.docs.map((d) => ({ id: d.id, ...d.data() }));
}

/**
 * Moves an order along. Who may set what:
 *   admin  — any status
 *   seller — confirm, fulfil, or cancel orders containing their own items
 *   buyer  — cancel, but only before the seller has confirmed
 */
export async function updateOrderStatus(id, user, status) {
  const order = await getOrder(id);

  const isAdmin = user.role === 'admin';
  const isSeller = (order.sellerIds || []).includes(user.uid);
  const isBuyer = order.buyerId === user.uid;

  if (!isAdmin) {
    if (isSeller && !['confirmed', 'fulfilled', 'cancelled'].includes(status)) {
      throw new ApiError(403, 'Sellers can confirm, fulfil, or cancel an order');
    }
    if (isBuyer && !isSeller) {
      if (status !== 'cancelled') {
        throw new ApiError(403, 'Buyers can only cancel an order');
      }
      if (order.status !== 'placed') {
        throw new ApiError(400, 'This order is already being prepared — contact the seller');
      }
    }
    if (!isSeller && !isBuyer) {
      throw new ApiError(403, 'You are not part of this order');
    }
  }

  // Cancelling returns the reserved stock so the listing goes back on sale.
  if (status === 'cancelled' && order.status !== 'cancelled') {
    await restoreStock(order);
  }

  await ordersRef.doc(id).update({ status, updatedAt: FieldValue.serverTimestamp() });
  return getOrder(id);
}

async function restoreStock(order) {
  await db.runTransaction(async (tx) => {
    const refs = order.items.map((item) => productsRef.doc(item.productId));
    const snaps = await Promise.all(refs.map((r) => tx.get(r)));

    snaps.forEach((snap, i) => {
      if (!snap.exists) return; // listing was deleted; nothing to give back
      tx.update(snap.ref, { stock: snap.data().stock + order.items[i].qty });
    });
  });
}
