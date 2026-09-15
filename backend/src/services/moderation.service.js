// backend/src/services/moderation.service.js
import { db, FieldValue } from '../config/firebaseAdmin.js';
import { ApiError } from '../middleware/errorHandler.js';

const reportsRef = db.collection('reports');
const reviewsRef = db.collection('reviews');

export async function createReport(reporterId, payload) {
  const doc = {
    ...payload,
    reporterId,
    status: 'open',
    createdAt: FieldValue.serverTimestamp(),
    resolvedBy: null,
    resolvedAt: null,
    resolutionNote: null,
  };
  const created = await reportsRef.add(doc);
  return { id: created.id, ...doc };
}

export async function listReports({ status = 'open', limit = 50 } = {}) {
  let query = reportsRef.orderBy('createdAt', 'desc');
  if (status && status !== 'all') query = query.where('status', '==', status);
  const snap = await query.limit(limit).get();
  return snap.docs.map((d) => ({ id: d.id, ...d.data() }));
}

export async function resolveReport(id, adminUid, { status, resolutionNote = '' }) {
  const snap = await reportsRef.doc(id).get();
  if (!snap.exists) throw new ApiError(404, 'Report not found');

  await reportsRef.doc(id).update({
    status, // 'reviewed' | 'dismissed'
    resolutionNote,
    resolvedBy: adminUid,
    resolvedAt: FieldValue.serverTimestamp(),
  });

  const updated = await reportsRef.doc(id).get();
  return { id: updated.id, ...updated.data() };
}

export async function createReview(authorId, payload) {
  const doc = {
    ...payload,
    authorId,
    createdAt: FieldValue.serverTimestamp(),
  };
  const created = await reviewsRef.add(doc);
  return { id: created.id, ...doc };
}

export async function listReviews(targetType, targetId, limit = 50) {
  const snap = await reviewsRef
    .where('targetType', '==', targetType)
    .where('targetId', '==', targetId)
    .orderBy('createdAt', 'desc')
    .limit(limit)
    .get();
  return snap.docs.map((d) => ({ id: d.id, ...d.data() }));
}

export async function deleteReview(id) {
  await reviewsRef.doc(id).delete();
  return { id, deleted: true };
}
