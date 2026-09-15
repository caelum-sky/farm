// backend/src/services/user.service.js
import { auth, db, FieldValue } from '../config/firebaseAdmin.js';
import { ApiError } from '../middleware/errorHandler.js';

const usersRef = db.collection('users');

/**
 * Creates the Firestore profile for a freshly-signed-up Firebase Auth user and
 * mirrors role/status onto custom claims so security rules can read them.
 * Idempotent: calling it twice for the same uid returns the existing profile.
 */
export async function createProfile(uid, email, payload) {
  const existing = await usersRef.doc(uid).get();
  if (existing.exists) return { id: uid, ...existing.data() };

  const profile = {
    email,
    displayName: payload.displayName,
    role: payload.role,
    phone: payload.phone || null,
    orgName: payload.orgName || null,
    avatarUrl: null,
    status: 'active',
    createdAt: FieldValue.serverTimestamp(),
    updatedAt: FieldValue.serverTimestamp(),
  };

  await usersRef.doc(uid).set(profile);
  await auth.setCustomUserClaims(uid, { role: payload.role, status: 'active' });

  return { id: uid, ...profile };
}

export async function getProfile(uid) {
  const snap = await usersRef.doc(uid).get();
  if (!snap.exists) throw new ApiError(404, 'Profile not found');
  return { id: snap.id, ...snap.data() };
}

export async function updateProfile(uid, payload) {
  // role and status are deliberately absent here — only admins can change those
  await usersRef.doc(uid).update({ ...payload, updatedAt: FieldValue.serverTimestamp() });
  return getProfile(uid);
}

// ---- admin operations -------------------------------------------------------

export async function listUsers({ role, status, limit = 50, cursor }) {
  let query = usersRef.orderBy('createdAt', 'desc');
  if (role) query = query.where('role', '==', role);
  if (status) query = query.where('status', '==', status);
  if (cursor) {
    const cursorDoc = await usersRef.doc(cursor).get();
    if (cursorDoc.exists) query = query.startAfter(cursorDoc);
  }

  const snap = await query.limit(limit).get();
  return {
    users: snap.docs.map((d) => ({ id: d.id, ...d.data() })),
    nextCursor: snap.docs.length === limit ? snap.docs[snap.docs.length - 1].id : null,
  };
}

export async function adminUpdateUser(uid, payload) {
  const snap = await usersRef.doc(uid).get();
  if (!snap.exists) throw new ApiError(404, 'User not found');

  await usersRef.doc(uid).update({ ...payload, updatedAt: FieldValue.serverTimestamp() });

  // keep custom claims in sync whenever role/status change
  if (payload.role || payload.status) {
    const fresh = (await usersRef.doc(uid).get()).data();
    await auth.setCustomUserClaims(uid, { role: fresh.role, status: fresh.status });
    await auth.revokeRefreshTokens(uid); // force the client to pick up new claims
  }

  return getProfile(uid);
}

export async function setBanStatus(uid, banned, adminUid) {
  const snap = await usersRef.doc(uid).get();
  if (!snap.exists) throw new ApiError(404, 'User not found');
  if (snap.data().role === 'admin') {
    throw new ApiError(403, 'Admin accounts cannot be banned');
  }

  const status = banned ? 'banned' : 'active';

  await usersRef.doc(uid).update({
    status,
    bannedAt: banned ? FieldValue.serverTimestamp() : null,
    bannedBy: banned ? adminUid : null,
    updatedAt: FieldValue.serverTimestamp(),
  });

  await auth.setCustomUserClaims(uid, { role: snap.data().role, status });
  await auth.updateUser(uid, { disabled: banned });
  await auth.revokeRefreshTokens(uid); // kills existing sessions immediately

  if (banned) {
    // hide the banned user's listings from the storefront
    await hideListingsForOwner(uid);
  }

  return getProfile(uid);
}

export async function deleteUser(uid) {
  const snap = await usersRef.doc(uid).get();
  if (!snap.exists) throw new ApiError(404, 'User not found');
  if (snap.data().role === 'admin') {
    throw new ApiError(403, 'Admin accounts cannot be deleted through this endpoint');
  }

  await hideListingsForOwner(uid, 'removed');
  await usersRef.doc(uid).delete();

  try {
    await auth.deleteUser(uid);
  } catch (err) {
    if (err.code !== 'auth/user-not-found') throw err;
  }

  return { id: uid, deleted: true };
}

async function hideListingsForOwner(uid, status = 'removed') {
  const batch = db.batch();
  for (const collection of ['products', 'equipment']) {
    const snap = await db.collection(collection).where('ownerId', '==', uid).get();
    snap.docs.forEach((doc) => {
      batch.update(doc.ref, { status, updatedAt: FieldValue.serverTimestamp() });
    });
  }
  await batch.commit();
}
