// backend/src/config/firebaseAdmin.js
import admin from 'firebase-admin';
import { readFileSync } from 'node:fs';

function getServiceAccount() {
  // Render mounts secret files at /etc/secrets. Prefer the JSON credential when
  // SERVICE_ACCOUNT_KEY points to one, so a private key is never split across
  // dashboard fields (or accidentally flattened by the environment editor).
  if (process.env.SERVICE_ACCOUNT_KEY) {
    try {
      return JSON.parse(readFileSync(process.env.SERVICE_ACCOUNT_KEY, 'utf8'));
    } catch {
      throw new Error('Could not load the Firebase service-account file configured by SERVICE_ACCOUNT_KEY.');
    }
  }

  return {
    projectId: process.env.FIREBASE_PROJECT_ID,
    clientEmail: process.env.FIREBASE_CLIENT_EMAIL,
    privateKey: (process.env.FIREBASE_PRIVATE_KEY || '').replace(/\\n/g, '\n'),
  };
}

if (!admin.apps.length) {
  admin.initializeApp({
    credential: admin.credential.cert(getServiceAccount()),
  });
}

export const auth = admin.auth();
export const db = admin.firestore();
export const FieldValue = admin.firestore.FieldValue;

export default admin;
