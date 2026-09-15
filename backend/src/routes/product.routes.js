// backend/src/routes/product.routes.js
import { Router } from 'express';
import { authenticate } from '../middleware/authenticate.js';
import { requireSeller } from '../middleware/authorize.js';
import { validate } from '../middleware/validate.js';
import { asyncHandler } from '../utils/asyncHandler.js';
import { createProductSchema, updateProductSchema } from '../validators/product.schema.js';
import {
  listListings,
  getListing,
  createListing,
  updateListing,
  deleteListing,
} from '../services/listing.service.js';

const router = Router();
const COLLECTION = 'products';

// Public: browse the storefront
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

// Sellers: farmers list produce, cooperatives list supplies
router.post(
  '/',
  authenticate,
  requireSeller,
  validate(createProductSchema),
  asyncHandler(async (req, res) => {
    res.status(201).json(await createListing(COLLECTION, req.user, req.body));
  })
);

router.patch(
  '/:id',
  authenticate,
  validate(updateProductSchema),
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
