// backend/src/services/listing.service.js
import { db, FieldValue } from '../config/firebaseAdmin.js';
import { ApiError } from '../middleware/errorHandler.js';

/**
 * Products and equipment share the same lifecycle (create → moderate → remove)
 * and the same ownership checks, so they share one service parameterized by
 * collection name rather than two near-identical copies.
 */
function ref(collection) {
  return db.collection(collection);
}

export async function listListings(collection, filters = {}) {
  const {
    category,
    subcategory,
    listingType,
    ownerId,
    search,
    minPrice,
    maxPrice,
    sort = 'newest',
    status = 'active',
    limit = 24,
    cursor,
  } = filters;

  let query = ref(collection).where('status', '==', status);
  if (category) query = query.where('category', '==', category);
  if (subcategory) query = query.where('subcategory', '==', subcategory);
  if (listingType) query = query.where('listingType', '==', listingType);
  if (ownerId) query = query.where('ownerId', '==', ownerId);

  // Firestore has no full-text search. A prefix range on `name` covers the
  // common case ("mang" → "Mangoes") without standing up a search service.
  // It requires ordering by `name`, so it takes precedence over other sorts.
  if (search) {
    const term = String(search);
    query = query
      .where('name', '>=', term)
      .where('name', '<=', `${term}\uf8ff`)
      .orderBy('name');
  } else if (sort === 'price-low' || sort === 'price-high' || minPrice || maxPrice) {
    // A range filter must be the first ordering, so price filters and price
    // sorting share this branch.
    if (minPrice) query = query.where('price', '>=', Number(minPrice));
    if (maxPrice) query = query.where('price', '<=', Number(maxPrice));
    query = query.orderBy('price', sort === 'price-high' ? 'desc' : 'asc');
  } else {
    query = query.orderBy('createdAt', 'desc');
  }

  if (cursor) {
    const cursorDoc = await ref(collection).doc(cursor).get();
    if (cursorDoc.exists) query = query.startAfter(cursorDoc);
  }

  const snap = await query.limit(Number(limit)).get();
  return {
    items: snap.docs.map((d) => ({ id: d.id, ...d.data() })),
    nextCursor: snap.docs.length === Number(limit) ? snap.docs[snap.docs.length - 1].id : null,
  };
}

export async function getListing(collection, id) {
  const snap = await ref(collection).doc(id).get();
  if (!snap.exists) throw new ApiError(404, 'Listing not found');
  return { id: snap.id, ...snap.data() };
}

export async function createListing(collection, user, payload) {
  const doc = {
    ...payload,
    ownerId: user.uid,
    ownerRole: user.role,
    status: 'active',
    createdAt: FieldValue.serverTimestamp(),
    updatedAt: FieldValue.serverTimestamp(),
  };

  // Cooperatives get member pricing available by default on supplies/rentals
  if (user.role === 'cooperative' && Array.isArray(doc.tags) && !doc.tags.includes('member-price')) {
    doc.tags = [...doc.tags, 'member-price'];
  }

  const created = await ref(collection).add(doc);
  return getListing(collection, created.id);
}

export async function updateListing(collection, id, user, payload) {
  const listing = await getListing(collection, id);
  assertCanMutate(listing, user);

  await ref(collection).doc(id).update({
    ...payload,
    updatedAt: FieldValue.serverTimestamp(),
  });
  return getListing(collection, id);
}

export async function deleteListing(collection, id, user) {
  const listing = await getListing(collection, id);
  assertCanMutate(listing, user);

  // Soft delete: keeps order/rental history referentially intact
  await ref(collection).doc(id).update({
    status: 'removed',
    updatedAt: FieldValue.serverTimestamp(),
  });
  return { id, status: 'removed' };
}

/** Admin moderation: force a listing's status without owning it. */
export async function moderateListing(collection, id, status) {
  await getListing(collection, id);
  await ref(collection).doc(id).update({
    status,
    moderatedAt: FieldValue.serverTimestamp(),
    updatedAt: FieldValue.serverTimestamp(),
  });
  return getListing(collection, id);
}

function assertCanMutate(listing, user) {
  if (user.role === 'admin') return;
  if (listing.ownerId !== user.uid) {
    throw new ApiError(403, 'You can only change your own listings');
  }
}
