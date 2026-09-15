// backend/src/routes/order.routes.js
import { Router } from 'express';
import { authenticate } from '../middleware/authenticate.js';
import { validate } from '../middleware/validate.js';
import { asyncHandler } from '../utils/asyncHandler.js';
import {
  createOrderSchema,
  updateOrderStatusSchema,
} from '../validators/transaction.schema.js';
import {
  createOrder,
  getOrder,
  listOrdersForBuyer,
  listOrdersForSeller,
  updateOrderStatus,
} from '../services/order.service.js';
import { ApiError } from '../middleware/errorHandler.js';

const router = Router();

router.post(
  '/',
  authenticate,
  validate(createOrderSchema),
  asyncHandler(async (req, res) => {
    res.status(201).json(await createOrder(req.user.uid, req.body.items));
  })
);

/** Orders I placed as a buyer. */
router.get(
  '/mine',
  authenticate,
  asyncHandler(async (req, res) => {
    res.json(await listOrdersForBuyer(req.user.uid));
  })
);

/** Orders placed against my listings, for farmers and cooperatives. */
router.get(
  '/sales',
  authenticate,
  asyncHandler(async (req, res) => {
    res.json(await listOrdersForSeller(req.user.uid));
  })
);

router.get(
  '/:id',
  authenticate,
  asyncHandler(async (req, res) => {
    const order = await getOrder(req.params.id);
    const involved =
      order.buyerId === req.user.uid ||
      (order.sellerIds || []).includes(req.user.uid) ||
      req.user.role === 'admin';
    if (!involved) throw new ApiError(403, 'You are not part of this order');
    res.json(order);
  })
);

router.patch(
  '/:id/status',
  authenticate,
  validate(updateOrderStatusSchema),
  asyncHandler(async (req, res) => {
    res.json(await updateOrderStatus(req.params.id, req.user, req.body.status));
  })
);

export default router;
