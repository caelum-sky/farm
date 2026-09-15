// backend/src/middleware/authenticate.js
import { auth, db } from '../config/firebaseAdmin.js';

/**
 * Verifies the Firebase ID token in the Authorization header and attaches
 * req.user = { uid, role, status, email, ... } for downstream handlers.
 * Also refuses requests from banned accounts, even with a still-valid token.
 */
export async function authenticate(req, res, next) {
  try {
    const header = req.headers.authorization || '';
    const token = header.startsWith('Bearer ') ? header.slice(7) : null;

    if (!token) {
      return res.status(401).json({ error: 'Missing bearer token' });
    }

    const decoded = await auth.verifyIdToken(token);

    // Custom claims (role/status) are the fast path, but we re-check the
    // Firestore doc for status so a ban takes effect immediately without
    // waiting for the client to refresh its ID token / claims.
    const userDoc = await db.collection('users').doc(decoded.uid).get();
    if (!userDoc.exists) {
      return res.status(401).json({ error: 'Account not found' });
    }

    const userData = userDoc.data();
    if (userData.status === 'banned') {
      return res.status(403).json({ error: 'This account has been banned' });
    }

    req.user = {
      uid: decoded.uid,
      email: decoded.email,
      role: userData.role,
      status: userData.status,
    };

    next();
  } catch {
    return res.status(401).json({ error: 'Invalid or expired token' });
  }
}

/**
 * Token-only verification for the profile-bootstrap endpoint, which runs
 * *before* a users/{uid} document exists. Every other route should use
 * `authenticate`, which additionally loads the profile and enforces bans.
 */
export async function authenticateToken(req, res, next) {
  try {
    const header = req.headers.authorization || '';
    const token = header.startsWith('Bearer ') ? header.slice(7) : null;
    if (!token) return res.status(401).json({ error: 'Missing bearer token' });

    const decoded = await auth.verifyIdToken(token);
    req.user = { uid: decoded.uid, email: decoded.email, role: decoded.role || null };
    next();
  } catch {
    return res.status(401).json({ error: 'Invalid or expired token' });
  }
}

/**
 * Optional auth: attaches req.user if a valid token is present, but never
 * blocks the request. Used on public read endpoints that personalize output
 * (e.g. showing "is this mine?" flags) when a user happens to be signed in.
 */
export async function attachUserIfPresent(req, res, next) {
  const header = req.headers.authorization || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;
  if (!token) return next();

  try {
    const decoded = await auth.verifyIdToken(token);
    const userDoc = await db.collection('users').doc(decoded.uid).get();
    if (userDoc.exists) {
      const userData = userDoc.data();
      req.user = { uid: decoded.uid, email: decoded.email, role: userData.role, status: userData.status };
    }
  } catch {
    // ignore — treat as anonymous
  }
  next();
}
