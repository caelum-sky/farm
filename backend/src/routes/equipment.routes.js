// backend/src/routes/equipment.routes.js
import { Router } from 'express';
import { authenticate } from '../middleware/authenticate.js';
import { requireSeller } from '../middleware/authorize.js';
import { validate } from '../middleware/validate.js';
import { asyncHandler } from '../utils/asyncHandler.js';
import { createEquipmentSchema, updateEquipmentSchema } from '../validators/equipment.schema.js';
import {
  listListings,
  getListing,
  createListing,
  updateListing,
  deleteListing,
} from '../services/listing.service.js';
import { listBookedRanges } from '../services/rental.service.js';

const router = Router();
const COLLECTION = 'equipment';

router.get(
  '/',
  asyncHandler(async (req, res) => {
    res.json(await listListings(COLLECTION, req.query));
  })
);

router.get(
  '/:id',
  asyncHandler(async (req, res) => {
    res.json(await getListing(COLLECTION, req.params.id));
  })
);

/** Dates already booked, so the client can disable them in the date picker. */
router.get(
  '/:id/availability',
  asyncHandler(async (req, res) => {
    res.json({ booked: await listBookedRanges(req.params.id) });
  })
);

router.post(
  '/',
  authenticate,
  requireSeller,
  validate(createEquipmentSchema),
  asyncHandler(async (req, res) => {
    res.status(201).json(await createListing(COLLECTION, req.user, req.body));
  })
);

router.patch(
  '/:id',
  authenticate,
  validate(updateEquipmentSchema),
  asyncHandler(async (req, res) => {
    res.json(await updateListing(COLLECTION, req.params.id, req.user, req.body));
  })
);

router.delete(
  '/:id',
  authenticate,
  asyncHandler(async (req, res) => {
    res.json(await deleteListing(COLLECTION, req.params.id, req.user));
  })
);

export default router;
