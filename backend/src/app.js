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
import { errorHandler, notFoundHandler } from './middleware/errorHandler.js';

const app = express();

app.get('/', (req, res) => {
  res.json({
    status: 'ok',
    service: 'farmhub-api',
    message: 'FarmHub API is running'
  });
});

app.set('trust proxy', 1);

app.use(
  cors({
    origin: (process.env.CORS_ORIGIN || 'http://localhost:5173')
      .split(',')
      .map((s) => s.trim()),
    credentials: true,
  })
);

app.use(express.json({ limit: '1mb' }));

app.use(
  '/api',
  rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 300,
    standardHeaders: true,
    legacyHeaders: false,
    message: {
      error: 'Too many requests. Try again in a few minutes.'
    },
  })
);

app.get('/api/health', (req, res) => {
  res.json({
    status: 'ok',
    service: 'farmhub-api',
    time: new Date().toISOString()
  });
});


app.use('/api/auth', authRoutes);
app.use('/api/products', productRoutes);
app.use('/api/equipment', equipmentRoutes);
app.use('/api/orders', orderRoutes);
app.use('/api/rentals', rentalRoutes);
app.use('/api', moderationRoutes);
app.use('/api/admin', adminRoutes);

app.use(notFoundHandler);
app.use(errorHandler);

export default app;