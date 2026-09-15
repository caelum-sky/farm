// frontend/src/firebase/config.ts
import { initializeApp, type FirebaseApp } from 'firebase/app';
import { getAuth, type Auth } from 'firebase/auth';
import { getFirestore, type Firestore } from 'firebase/firestore';
import { getStorage, type FirebaseStorage } from 'firebase/storage';

const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

/**
 * Firebase throws on initialization when its config is missing, which would take
 * the whole page down — including the storefront, which needs no auth at all.
 * We check first and degrade instead: browsing keeps working, and only the
 * account features report that sign-in isn't configured.
 */
export const firebaseReady = Object.values(firebaseConfig).every(
  (value) => typeof value === 'string' && value.length > 0
);

let appInstance: FirebaseApp | null = null;
let authInstance: Auth | null = null;
let dbInstance: Firestore | null = null;
let storageInstance: FirebaseStorage | null = null;

if (firebaseReady) {
  appInstance = initializeApp(firebaseConfig);
  authInstance = getAuth(appInstance);
  dbInstance = getFirestore(appInstance);
  storageInstance = getStorage(appInstance);
} else if (import.meta.env.DEV) {
  console.warn(
    'Firebase is not configured. Copy .env.example to .env and fill in the VITE_FIREBASE_* values to enable sign-in.'
  );
}

export const app = appInstance;
export const auth = authInstance;
export const db = dbInstance;
export const storage = storageInstance;

/** Throws a readable error instead of a null-property crash deep in a handler. */
export function requireAuth(): Auth {
  if (!authInstance) {
    throw new Error('Sign-in is unavailable: this site has no Firebase configuration.');
  }
  return authInstance;
}
