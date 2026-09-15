// backend/src/scripts/seedAdmin.js
/**
 * Bootstraps the first admin account. Roles can't be self-assigned through the
 * API, so the very first admin has to be created out-of-band by someone with
 * service-account access — that's this script.
 *
 *   node src/scripts/seedAdmin.js admin@farmhub.ph "Strong#Passw0rd" "Site Admin"
 */
import 'dotenv/config';
import { auth, db, FieldValue } from '../config/firebaseAdmin.js';

const [, , email, password, displayName = 'FarmHub Admin'] = process.argv;

if (!email || !password) {
  console.error('Usage: node src/scripts/seedAdmin.js <email> <password> [displayName]');
  process.exit(1);
}

async function run() {
  let user;
  try {
    user = await auth.getUserByEmail(email);
    console.log(`Found existing auth user ${user.uid}`);
  } catch (err) {
    if (err.code !== 'auth/user-not-found') throw err;
    user = await auth.createUser({ email, password, displayName, emailVerified: true });
    console.log(`Created auth user ${user.uid}`);
  }

  await auth.setCustomUserClaims(user.uid, { role: 'admin', status: 'active' });

  await db.collection('users').doc(user.uid).set(
    {
      email,
      displayName,
      role: 'admin',
      status: 'active',
      phone: null,
      orgName: null,
      avatarUrl: null,
      createdAt: FieldValue.serverTimestamp(),
      updatedAt: FieldValue.serverTimestamp(),
    },
    { merge: true }
  );

  console.log(`${email} is now an admin. Sign in on the web app to use the admin panel.`);
  process.exit(0);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
