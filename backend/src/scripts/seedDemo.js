// backend/src/scripts/seedDemo.js
/**
 * Fills a fresh project with demo members and listings so the storefront has
 * something to show before real farms sign up.
 *
 *   node src/scripts/seedDemo.js
 *
 * Safe to re-run: accounts are matched by email and listings are skipped if a
 * listing with the same name already exists for that owner.
 */
import 'dotenv/config';
import { auth, db, FieldValue } from '../config/firebaseAdmin.js';

const DEMO_PASSWORD = 'FarmHubDemo#2026';

const MEMBERS = [
  {
    email: 'ana.farmer@example.ph',
    displayName: 'Ana Villaruel',
    role: 'farmer',
    products: [
      { name: 'Carabao mangoes', subcategory: 'fruits', price: 120, unit: 'kg', stock: 80, tags: ['just-harvested'], description: 'Tree-ripened, picked Tuesday morning in Calinan.' },
      { name: 'Native eggplant', subcategory: 'vegetables', price: 65, unit: 'kg', stock: 45, tags: ['organic'], description: 'Grown without synthetic pesticide. Firm and glossy.' },
      { name: 'Sweet corn', subcategory: 'vegetables', price: 40, unit: 'kg', stock: 120, tags: ['just-harvested'], description: 'Harvested green, best within three days.' },
    ],
    equipment: [],
  },
  {
    email: 'bayanihan.coop@example.ph',
    displayName: 'Bayanihan Coop Office',
    role: 'cooperative',
    orgName: 'Bayanihan Farmers Cooperative',
    products: [
      { name: 'Complete fertilizer 14-14-14', subcategory: 'fertilizer', price: 1450, unit: 'sack', stock: 60, tags: [], description: '50 kg sack. Member rate applied at checkout.' },
      { name: 'Organic foliar spray', subcategory: 'pesticide', price: 320, unit: 'bottle', stock: 90, tags: ['organic'], description: 'Neem-based, safe up to harvest week.' },
      { name: 'Certified rice seed', subcategory: 'seed', price: 1250, unit: 'sack', stock: 40, tags: [], description: 'NSIC Rc222, certified and tagged.' },
    ],
    equipment: [
      { name: 'Kubota L3408 tractor', category: 'tractor', listingType: 'rent', price: 2800, description: '34 hp four-wheel drive. Operator included in the daily rate.' },
      { name: 'Walk-behind tiller', category: 'tiller', listingType: 'rent', price: 950, description: 'Good for plots under a hectare. Fuel not included.' },
      { name: 'Knapsack mist blower', category: 'sprayer', listingType: 'rent', price: 400, description: 'Petrol powered, 20 litre tank.' },
    ],
  },
  {
    email: 'ramon.farmer@example.ph',
    displayName: 'Ramon Cordero',
    role: 'farmer',
    products: [
      { name: 'Puyat durian', subcategory: 'fruits', price: 180, unit: 'kg', stock: 30, tags: ['just-harvested'], description: 'Thick flesh, small seed. Sold whole.' },
      { name: 'Free-range eggs', subcategory: 'dairy-and-eggs', price: 260, unit: 'tray', stock: 25, tags: ['organic'], description: 'Tray of 30. Collected daily.' },
    ],
    equipment: [
      { name: 'Rice thresher', category: 'thresher', listingType: 'rent', price: 1600, description: 'Trailer-mounted. Rented with a two-person crew.' },
    ],
  },
  {
    email: 'liza.buyer@example.ph',
    displayName: 'Liza Mendoza',
    role: 'buyer',
    products: [],
    equipment: [],
  },
];

async function ensureMember(member) {
  let user;
  try {
    user = await auth.getUserByEmail(member.email);
  } catch (err) {
    if (err.code !== 'auth/user-not-found') throw err;
    user = await auth.createUser({
      email: member.email,
      password: DEMO_PASSWORD,
      displayName: member.displayName,
      emailVerified: true,
    });
  }

  await auth.setCustomUserClaims(user.uid, { role: member.role, status: 'active' });
  await db.collection('users').doc(user.uid).set(
    {
      email: member.email,
      displayName: member.displayName,
      role: member.role,
      orgName: member.orgName || null,
      status: 'active',
      phone: null,
      avatarUrl: null,
      createdAt: FieldValue.serverTimestamp(),
      updatedAt: FieldValue.serverTimestamp(),
    },
    { merge: true }
  );

  return user.uid;
}

async function ensureListing(collection, ownerId, ownerRole, payload) {
  const existing = await db
    .collection(collection)
    .where('ownerId', '==', ownerId)
    .where('name', '==', payload.name)
    .limit(1)
    .get();

  if (!existing.empty) return false;

  await db.collection(collection).add({
    ...payload,
    ownerId,
    ownerRole,
    images: [],
    status: 'active',
    createdAt: FieldValue.serverTimestamp(),
    updatedAt: FieldValue.serverTimestamp(),
  });
  return true;
}

async function run() {
  let created = 0;

  for (const member of MEMBERS) {
    const uid = await ensureMember(member);
    console.log(`${member.displayName} (${member.role}) → ${uid}`);

    for (const product of member.products) {
      const isSupply = ['fertilizer', 'pesticide', 'seed', 'feed', 'tools'].includes(
        product.subcategory
      );
      const added = await ensureListing('products', uid, member.role, {
        ...product,
        category: isSupply ? 'supply' : 'produce',
        tags: member.role === 'cooperative' ? [...product.tags, 'member-price'] : product.tags,
      });
      if (added) created += 1;
    }

    for (const machine of member.equipment) {
      const added = await ensureListing('equipment', uid, member.role, machine);
      if (added) created += 1;
    }
  }

  console.log(`\nDone. ${created} new listings added.`);
  console.log(`Demo accounts all use the password: ${DEMO_PASSWORD}`);
  process.exit(0);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
