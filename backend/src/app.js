// backend/src/app.js

import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';
import rateLimit from 'express-rate-limit';

import authRoutes from './routes/auth.routes.js';
import productRoutes from './routes/product.routes.js';
import equipmentRoutes from './routes/equipment.routes.js';
import orderRoutes from './routes/order.routes.js';
import rentalRoutes from './routes/rental.routes.js';
import moderationRoutes from './routes/moderation.routes.js';
import adminRoutes from './routes/admin.routes.js';

import {
  errorHandler,
  notFoundHandler,
} from './middleware/errorHandler.js';

const app = express();

app.set('trust proxy', 1);

// =====================================================
// CORS
// =====================================================

const allowedOrigins = (
  process.env.CORS_ORIGIN ||
  'http://localhost:5173'
)
  .split(',')
  .map((origin) => origin.trim())
  .filter(Boolean);

// Configure CORS middleware with robust origin checking
const corsOptions = {
  origin: (origin, callback) => {
    // Allow requests with no origin (like Postman, curl, or server-to-server)
    if (!origin) {
      return callback(null, true);
    }

    // Normalize the origin for comparison (remove trailing slash, convert to lowercase)
    const normalizedOrigin = origin.replace(/\/$/, '').toLowerCase();

    // Check if the normalized origin matches any allowed origin
    const isAllowed = allowedOrigins.some(allowedOrigin => {
      const normalizedAllowed = allowedOrigin.replace(/\/$/, '').toLowerCase();
      return normalizedOrigin === normalizedAllowed;
    });

    if (isAllowed) {
      return callback(null, true);
    }

    console.warn(`CORS blocked origin: ${origin}`);
    return callback(
      new Error(`CORS policy: Origin ${origin} is not allowed`)
    );
  },

  credentials: true,

  methods: [
    'GET',
    'POST',
    'PUT',
    'PATCH',
    'DELETE',
    'OPTIONS',
  ],

  allowedHeaders: [
    'Origin',
    'X-Requested-With',
    'Content-Type',
    'Accept',
    'Authorization',
  ],

  optionsSuccessStatus: 204, // Important for legacy browsers
};

app.use(cors(corsOptions));

// Explicitly handle browser preflight requests
app.options('*', cors(corsOptions));

// =====================================================
// SECURITY / BODY / LOGGING
// =====================================================

app.use(helmet());

app.use(express.json({ limit: '1mb' }));

app.use(
  morgan(
    process.env.NODE_ENV === 'production'
      ? 'combined'
      : 'dev'
  )
);

// =====================================================
// RATE LIMITING
// =====================================================

app.use(
  '/api',
  rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 300,
    standardHeaders: true,
    legacyHeaders: false,
    message: {
      error: 'Too many requests. Try again in a few minutes.',
    },
  })
);

app.use(
  '/api/auth',
  rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 40,
    standardHeaders: true,
    legacyHeaders: false,
  })
);

// =====================================================
// HEALTH CHECK
// =====================================================

app.get('/api/health', (req, res) => {
  res.json({
    status: 'ok',
    service: 'farmhub-api',
    time: new Date().toISOString(),
  });
});

// =====================================================
// API ROUTES
// =====================================================

app.use('/api/auth', authRoutes);

app.use('/api/products', productRoutes);

app.use('/api/equipment', equipmentRoutes);

app.use('/api/orders', orderRoutes);

app.use('/api/rentals', rentalRoutes);

app.use('/api', moderationRoutes);

app.use('/api/admin', adminRoutes);

// =====================================================
// ERROR HANDLING
// =====================================================

app.use(notFoundHandler);

app.use(errorHandler);

export default app;