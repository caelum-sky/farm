# FarmHub — Farm Commerce & Equipment-Sharing SaaS

FarmHub connects three kinds of users on one platform:

- **Buyers** — regular customers who purchase farm produce (fruits, vegetables, dairy, etc.)
- **Farmers** — individual growers who sell produce and can list equipment for rent or sale
- **Cooperatives / Stores** — organizations that sell supplies (fertilizer, pesticides), and
  sell or rent shared equipment (tractors, tillers, etc.) at low cost to members
- **Admins** — moderate the entire platform: users, listings, reports, bans

## Monorepo layout

```
farmhub/
├── frontend/            React + Vite + TypeScript + Tailwind — the web app
├── backend/              Node.js + Express — REST API, talks to Firebase Admin SDK
├── analytics-service/    Python + FastAPI — admin analytics/reporting microservice
├── firebase/             Firestore security rules, indexes, Storage rules
├── .github/workflows/    CI/CD — lint, test, build, deploy
└── render.yaml            Render deploy blueprint for backend + analytics-service
```

**Why this split:** Node/Express owns the transactional write path (orders, rentals,
moderation actions) where request-scoped Firebase Admin auth matters most. Python/FastAPI
owns read-heavy aggregation (admin dashboard stats, sales/rental trends) where
pandas-style number crunching is a better fit than doing it in JS. Both are stateless
and deploy independently on Render.

## Roles & permissions (RBAC)

| Role         | Can do |
|--------------|--------|
| `buyer`      | Browse/search, buy products, rent equipment, leave reviews, report listings |
| `farmer`     | Everything a buyer can, + list/manage own products & equipment (sell or rent) |
| `cooperative`| Everything a farmer can, + mark listings as "member pricing", manage a shared equipment pool |
| `admin`      | Manage all users (CRUD, ban/unban, delete), moderate all listings, resolve reports, view platform analytics |

Roles are stored on the Firebase Auth custom claims **and** mirrored on the
`users/{uid}` Firestore doc (claims for fast rule checks, Firestore doc for the
data the UI actually reads). `backend/src/middleware/authorize.js` enforces this
server-side; `firebase/firestore.rules` enforces it again at the database layer
so a compromised or buggy API can never bypass it — defense in depth.

## Data model (Firestore)

```
users/{uid}
  role: 'buyer' | 'farmer' | 'cooperative' | 'admin'
  displayName, email, phone, avatarUrl
  orgName?                 // cooperatives only
  status: 'active' | 'banned'
  createdAt, updatedAt

products/{id}
  ownerId, ownerRole
  name, description, category: 'produce' | 'supply'
  subcategory                // e.g. "fruits", "vegetables", "fertilizer", "pesticide"
  price, unit                // e.g. per kg, per sack
  stock
  images: string[]           // Firebase Storage URLs
  tags: string[]              // 'organic', 'just-harvested', 'member-price'
  status: 'active' | 'pending' | 'removed'
  createdAt, updatedAt

equipment/{id}
  ownerId, ownerRole
  name, description, category   // 'tractor', 'tiller', 'sprayer', ...
  listingType: 'sale' | 'rent'
  price                          // sale price OR price-per-day
  images: string[]
  availability: { start, end }[] // booked date ranges, for rentals
  status: 'active' | 'pending' | 'removed'
  createdAt, updatedAt

orders/{id}
  buyerId, items: [{ productId, name, qty, price }], total
  status: 'placed' | 'confirmed' | 'fulfilled' | 'cancelled'
  createdAt

rentals/{id}
  renterId, equipmentId, ownerId
  startDate, endDate, totalCost
  status: 'requested' | 'approved' | 'active' | 'returned' | 'cancelled'
  createdAt

reports/{id}
  reporterId, targetType: 'product' | 'equipment' | 'user', targetId
  reason, details
  status: 'open' | 'reviewed' | 'dismissed'
  createdAt, resolvedBy?, resolvedAt?

reviews/{id}
  targetType, targetId, authorId, rating, comment, createdAt
```

## Stack

- **Frontend:** React 18 + Vite + TypeScript + Tailwind CSS, deployed to Firebase Hosting
- **Backend API:** Node.js + Express, deployed to Render
- **Analytics service:** Python + FastAPI, deployed to Render
- **Auth/DB/Storage:** Firebase (Authentication, Firestore, Storage)
- **CI/CD:** GitHub Actions → lint/test/build → deploy backend + analytics to Render, frontend to Firebase Hosting
- **Mobile:** not included in this pass — the brief mixed Vite (web) with React Native
  (mobile); this build ships the web app first since a "website" was asked for. The API
  is already mobile-ready (stateless REST + Firebase Auth tokens), so an Expo/React Native
  client can be bolted on later reusing `backend/` as-is.

## Local development

```bash
# Backend
cd backend && cp .env.example .env   # fill in Firebase service account + config
npm install && npm run dev            # http://localhost:8080

# Analytics service
cd analytics-service
python -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn main:app --reload --port 8090

# Frontend
cd frontend && cp .env.example .env   # fill in Firebase web config + VITE_API_URL
npm install && npm run dev            # http://localhost:5173
```

## Deploying

1. **Firebase:** `firebase deploy --only firestore:rules,firestore:indexes,storage,hosting`
   (project config lives in `firebase/firebase.json`)
2. **Backend + analytics:** push to `main` — GitHub Actions builds and triggers a Render
   deploy hook (`render.yaml` defines both services). Set `RENDER_BACKEND_DEPLOY_HOOK` and
   `RENDER_ANALYTICS_DEPLOY_HOOK` as GitHub repo secrets.
3. **Frontend:** the same CI workflow builds `frontend/` and deploys to Firebase Hosting
   using a `FIREBASE_TOKEN` / `FIREBASE_SERVICE_ACCOUNT` secret.

## What's scaffolded vs. what's a next step

Done and working end-to-end: auth + RBAC, product & equipment CRUD (buy + rent),
cart/checkout → orders, rental booking flow, reviews, reporting, full admin panel
(user CRUD/ban/delete, listing moderation, report queue, analytics dashboard),
Firestore security rules, CI/CD, animated marketing homepage.

Good next steps (say "continue" and pick one): Stripe/PayMongo payment capture on
checkout, image upload pipeline (signed URLs to Storage), email notifications,
in-app messaging between buyer and seller, the Expo mobile client.

## Deployment Secrets

To enable CI/CD pipelines to deploy to Render and Firebase Hosting, the following GitHub repository secrets must be set:

### Frontend Build (GitHub Actions)
- `VITE_API_URL`: Base URL of the backend API (e.g., `https://farmhub-api.onrender.com`)
- `VITE_FIREBASE_API_KEY`: Firebase Web API key
- `VITE_FIREBASE_AUTH_DOMAIN`: Firebase Auth domain (e.g., `farmhub.firebaseapp.com`)
- `VITE_FIREBASE_PROJECT_ID`: Firebase project ID
- `VITE_FIREBASE_STORAGE_BUCKET`: Firebase Storage bucket (e.g., `farmhub.appspot.com`)
- `VITE_FIREBASE_MESSAGING_SENDER_ID`: Firebase messaging sender ID
- `VITE_FIREBASE_APP_ID`: Firebase App ID
- `FIREBASE_SERVICE_ACCOUNT`: JSON contents of a Firebase service account key (for deploying to Firebase Hosting)

### Backend & Analytics Services (Render)
These variables must be set in the Render dashboard for each service (backend and analytics) **or** can be injected via GitHub Actions if you prefer (though Render recommends setting them in the dashboard):
- `FIREBASE_PROJECT_ID`: Firebase project ID
- `FIREBASE_CLIENT_EMAIL`: Firebase service account email
- `FIREBASE_PRIVATE_KEY`: Firebase service account private key (with newlines)
- `CORS_ORIGIN`: Comma-separated list of allowed origins (e.g., `https://farmhub.firebaseapp.com,http://localhost:5173`)
- `NODE_ENV`: Set to `production` for the backend service (not required for analytics)

### Render Webhook Triggers (GitHub Actions)
- `RENDER_BACKEND_DEPLOY_HOOK`: Deploy hook URL for the backend service on Render
- `RENDER_ANALYTICS_DEPLOY_HOOK`: Deploy hook URL for the analytics service on Render
