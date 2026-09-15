// backend/src/routes/rental.routes.js
import { Router } from 'express';
import { authenticate } from '../middleware/authenticate.js';
import { validate } from '../middleware/validate.js';
import { asyncHandler } from '../utils/asyncHandler.js';
import { createRentalSchema, updateRentalStatusSchema } from '../validators/transaction.schema.js';
import {
  createRental,
  getRental,
  listRentalsForUser,
  updateRentalStatus,
} from '../services/rental.service.js';
import { ApiError } from '../middleware/errorHandler.js';

const router = Router();

router.post(
  '/',
  authenticate,
  validate(createRentalSchema),
  asyncHandler(async (req, res) => {
    res.status(201).json(await createRental(req.user.uid, req.body));
  })
);

/** Equipment I've booked. */
router.get(
  '/mine',
  authenticate,
  asyncHandler(async (req, res) => {
    res.json(await listRentalsForUser(req.user.uid, 'renter'));
  })
);

/** Booking requests for equipment I own. */
router.get(
  '/incoming',
  authenticate,
  asyncHandler(async (req, res) => {
    res.json(await listRentalsForUser(req.user.uid, 'owner'));
  })
);

router.get(
  '/:id',
  authenticate,
  asyncHandler(async (req, res) => {
    const rental = await getRental(req.params.id);
    const involved =
      rental.renterId === req.user.uid ||
      rental.ownerId === req.user.uid ||
      req.user.role === 'admin';
    if (!involved) throw new ApiError(403, 'You are not part of this booking');
    res.json(rental);
  })
);

router.patch(
  '/:id/status',
  authenticate,
  validate(updateRentalStatusSchema),
  asyncHandler(async (req, res) => {
    res.json(await updateRentalStatus(req.params.id, req.user, req.body.status));
  })
);

export default router;
