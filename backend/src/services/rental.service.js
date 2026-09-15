// backend/src/services/rental.service.js
import { db, FieldValue } from '../config/firebaseAdmin.js';
import { ApiError } from '../middleware/errorHandler.js';

const rentalsRef = db.collection('rentals');
const equipmentRef = db.collection('equipment');

const BLOCKING_STATUSES = ['requested', 'approved', 'active'];

/**
 * Books equipment for a date range. Runs in a transaction and rejects any
 * range that overlaps an existing non-cancelled booking, so two farmers can't
 * both reserve the same tractor for planting week.
 */
export async function createRental(renterId, { equipmentId, startDate, endDate }) {
  const start = new Date(startDate);
  const end = new Date(endDate);
  const rentalId = rentalsRef.doc().id;

  await db.runTransaction(async (tx) => {
    const equipSnap = await tx.get(equipmentRef.doc(equipmentId));
    if (!equipSnap.exists) throw new ApiError(404, 'Equipment not found');

    const equipment = equipSnap.data();
    if (equipment.status !== 'active') {
      throw new ApiError(400, `${equipment.name} is not available right now`);
    }
    if (equipment.listingType !== 'rent') {
      throw new ApiError(400, `${equipment.name} is listed for sale, not for rent`);
    }
    if (equipment.ownerId === renterId) {
      throw new ApiError(400, 'You cannot rent your own equipment');
    }

    const existing = await rentalsRef
      .where('equipmentId', '==', equipmentId)
      .where('status', 'in', BLOCKING_STATUSES)
      .get();

    const clash = existing.docs.some((doc) => {
      const r = doc.data();
      return start < r.endDate.toDate() && end > r.startDate.toDate();
    });
    if (clash) {
      throw new ApiError(409, 'Those dates are already booked. Pick another range.');
    }

    const days = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)));
    const totalCost = Number((equipment.price * days).toFixed(2));

    tx.set(rentalsRef.doc(rentalId), {
      renterId,
      ownerId: equipment.ownerId,
      equipmentId,
      equipmentName: equipment.name,
      startDate: start,
      endDate: end,
      days,
      dailyRate: equipment.price,
      totalCost,
      status: 'requested',
      createdAt: FieldValue.serverTimestamp(),
      updatedAt: FieldValue.serverTimestamp(),
    });
  });

  return getRental(rentalId);
}

/**
 * Date ranges already spoken for, so the booking form can grey them out instead
 * of letting someone submit a clash and get rejected.
 */
export async function listBookedRanges(equipmentId) {
  const snap = await rentalsRef
    .where('equipmentId', '==', equipmentId)
    .where('status', 'in', BLOCKING_STATUSES)
    .get();

  return snap.docs
    .map((doc) => {
      const r = doc.data();
      return {
        startDate: r.startDate?.toDate?.()?.toISOString() ?? null,
        endDate: r.endDate?.toDate?.()?.toISOString() ?? null,
      };
    })
    .filter((range) => range.startDate && range.endDate);
}

export async function getRental(id) {
  const snap = await rentalsRef.doc(id).get();
  if (!snap.exists) throw new ApiError(404, 'Rental not found');
  return { id: snap.id, ...snap.data() };
}

export async function listRentalsForUser(uid, role = 'renter', limit = 50) {
  const field = role === 'owner' ? 'ownerId' : 'renterId';
  const snap = await rentalsRef
    .where(field, '==', uid)
    .orderBy('createdAt', 'desc')
    .limit(limit)
    .get();
  return snap.docs.map((d) => ({ id: d.id, ...d.data() }));
}

export async function listAllRentals({ status, limit = 50 } = {}) {
  let query = rentalsRef.orderBy('createdAt', 'desc');
  if (status) query = query.where('status', '==', status);
  const snap = await query.limit(limit).get();
  return snap.docs.map((d) => ({ id: d.id, ...d.data() }));
}

/**
 * Owners approve/cancel; renters may only cancel their own request.
 * Admins may set any status.
 */
export async function updateRentalStatus(id, user, status) {
  const rental = await getRental(id);

  const isOwner = rental.ownerId === user.uid;
  const isRenter = rental.renterId === user.uid;
  const isAdmin = user.role === 'admin';

  if (!isOwner && !isRenter && !isAdmin) {
    throw new ApiError(403, 'You are not part of this booking');
  }
  if (isRenter && !isAdmin && !isOwner && status !== 'cancelled') {
    throw new ApiError(403, 'Renters can only cancel a booking');
  }

  await rentalsRef.doc(id).update({ status, updatedAt: FieldValue.serverTimestamp() });
  return getRental(id);
}
