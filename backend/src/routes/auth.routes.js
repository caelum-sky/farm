// backend/src/routes/auth.routes.js
import { Router } from 'express';
import { authenticate, authenticateToken } from '../middleware/authenticate.js';
import { validate } from '../middleware/validate.js';
import { asyncHandler } from '../utils/asyncHandler.js';
import { registerProfileSchema, updateProfileSchema } from '../validators/auth.schema.js';
import { createProfile, getProfile, updateProfile } from '../services/user.service.js';

const router = Router();

/**
 * POST /api/auth/profile
 * Called right after Firebase Auth sign-up on the client. The client creates the
 * credential; this endpoint creates the profile document and sets the role claim
 * so the role can never be self-assigned as 'admin' from the browser.
 */
router.post(
  '/profile',
  authenticateToken,
  validate(registerProfileSchema),
  asyncHandler(async (req, res) => {
    const profile = await createProfile(req.user.uid, req.user.email, req.body);
    res.status(201).json(profile);
  })
);

router.get(
  '/me',
  authenticate,
  asyncHandler(async (req, res) => {
    res.json(await getProfile(req.user.uid));
  })
);

router.patch(
  '/me',
  authenticate,
  validate(updateProfileSchema),
  asyncHandler(async (req, res) => {
    res.json(await updateProfile(req.user.uid, req.body));
  })
);

export default router;
