// backend/src/routes/admin.routes.js
import { Router } from 'express';
import { z } from 'zod';
import { authenticate } from '../middleware/authenticate.js';
import { requireAdmin } from '../middleware/authorize.js';
import { validate } from '../middleware/validate.js';
import { asyncHandler } from '../utils/asyncHandler.js';
import { db } from '../config/firebaseAdmin.js';
import {
  listUsers,
  getProfile,
  adminUpdateUser,
  setBanStatus,
  deleteUser,
} from '../services/user.service.js';
import { listListings, moderateListing } from '../services/listing.service.js';
import { listAllOrders, updateOrderStatus } from '../services/order.service.js';
import { listAllRentals } from '../services/rental.service.js';
import { listReports, resolveReport, deleteReview } from '../services/moderation.service.js';

const router = Router();

// Every route below is admin-only.
router.use(authenticate, requireAdmin);

// ---- schemas ----------------------------------------------------------------
const adminUpdateUserSchema = z.object({
  displayName: z.string().min(2).max(80).optional(),
  role: z.enum(['buyer', 'farmer', 'cooperative', 'admin']).optional(),
  status: z.enum(['active', 'banned']).optional(),
  phone: z.string().min(7).max(20).optional(),
  orgName: z.string().min(2).max(120).optional(),
});

const banSchema = z.object({
  banned: z.boolean(),
  reason: z.string().max(300).optional(),
});

const moderateListingSchema = z.object({
  status: z.enum(['active', 'pending', 'removed']),
});

const resolveReportSchema = z.object({
  status: z.enum(['reviewed', 'dismissed']),
  resolutionNote: z.string().max(500).optional(),
});

const orderStatusSchema = z.object({
  status: z.enum(['placed', 'confirmed', 'fulfilled', 'cancelled']),
});

// ---- users -------------------------------------------------------------------
router.get(
  '/users',
  asyncHandler(async (req, res) => {
    res.json(await listUsers(req.query));
  })
);

router.get(
  '/users/:uid',
  asyncHandler(async (req, res) => {
    res.json(await getProfile(req.params.uid));
  })
);

router.patch(
  '/users/:uid',
  validate(adminUpdateUserSchema),
  asyncHandler(async (req, res) => {
    res.json(await adminUpdateUser(req.params.uid, req.body));
  })
);

/** Ban or unban. Banning disables the Auth account, revokes live sessions, and hides listings. */
router.post(
  '/users/:uid/ban',
  validate(banSchema),
  asyncHandler(async (req, res) => {
    res.json(await setBanStatus(req.params.uid, req.body.banned, req.user.uid));
  })
);

router.delete(
  '/users/:uid',
  asyncHandler(async (req, res) => {
    res.json(await deleteUser(req.params.uid));
  })
);

// ---- listing moderation ---------------------------------------------------------
router.get(
  '/products',
  asyncHandler(async (req, res) => {
    res.json(await listListings('products', { ...req.query, status: req.query.status || 'active' }));
  })
);

router.patch(
  '/products/:id/status',
  validate(moderateListingSchema),
  asyncHandler(async (req, res) => {
    res.json(await moderateListing('products', req.params.id, req.body.status));
  })
);

router.get(
  '/equipment',
  asyncHandler(async (req, res) => {
    res.json(await listListings('equipment', { ...req.query, status: req.query.status || 'active' }));
  })
);

router.patch(
  '/equipment/:id/status',
  validate(moderateListingSchema),
  asyncHandler(async (req, res) => {
    res.json(await moderateListing('equipment', req.params.id, req.body.status));
  })
);

// ---- reports ----------------------------------------------------------------------
router.get(
  '/reports',
  asyncHandler(async (req, res) => {
    res.json(await listReports(req.query));
  })
);

router.patch(
  '/reports/:id',
  validate(resolveReportSchema),
  asyncHandler(async (req, res) => {
    res.json(await resolveReport(req.params.id, req.user.uid, req.body));
  })
);

router.delete(
  '/reviews/:id',
  asyncHandler(async (req, res) => {
    res.json(await deleteReview(req.params.id));
  })
);

// ---- transactions -------------------------------------------------------------------
router.get(
  '/orders',
  asyncHandler(async (req, res) => {
    res.json(await listAllOrders(req.query));
  })
);

router.patch(
  '/orders/:id/status',
  validate(orderStatusSchema),
  asyncHandler(async (req, res) => {
    res.json(await updateOrderStatus(req.params.id, req.user, req.body.status));
  })
);

router.get(
  '/rentals',
  asyncHandler(async (req, res) => {
    res.json(await listAllRentals(req.query));
  })
);

// ---- dashboard counters ---------------------------------------------------------------
/**
 * Lightweight counts for the admin overview cards. Deeper trend analysis lives
 * in the Python analytics service; this endpoint stays cheap so the dashboard
 * paints immediately.
 */
router.get(
  '/stats',
  asyncHandler(async (req, res) => {
    const [users, products, equipment, orders, openReports] = await Promise.all([
      db.collection('users').count().get(),
      db.collection('products').where('status', '==', 'active').count().get(),
      db.collection('equipment').where('status', '==', 'active').count().get(),
      db.collection('orders').count().get(),
      db.collection('reports').where('status', '==', 'open').count().get(),
    ]);

    res.json({
      users: users.data().count,
      activeProducts: products.data().count,
      activeEquipment: equipment.data().count,
      orders: orders.data().count,
      openReports: openReports.data().count,
    });
  })
);

export default router;
