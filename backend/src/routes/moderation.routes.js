// backend/src/routes/moderation.routes.js
import { Router } from 'express';
import { authenticate } from '../middleware/authenticate.js';
import { validate } from '../middleware/validate.js';
import { asyncHandler } from '../utils/asyncHandler.js';
import { createReportSchema, createReviewSchema } from '../validators/transaction.schema.js';
import { createReport, createReview, listReviews } from '../services/moderation.service.js';

const router = Router();

/** Any signed-in user can flag a listing or another user for admin review. */
router.post(
  '/reports',
  authenticate,
  validate(createReportSchema),
  asyncHandler(async (req, res) => {
    res.status(201).json(await createReport(req.user.uid, req.body));
  })
);

router.post(
  '/reviews',
  authenticate,
  validate(createReviewSchema),
  asyncHandler(async (req, res) => {
    res.status(201).json(await createReview(req.user.uid, req.body));
  })
);

router.get(
  '/reviews',
  asyncHandler(async (req, res) => {
    const { targetType, targetId } = req.query;
    res.json(await listReviews(targetType, targetId));
  })
);

export default router;
