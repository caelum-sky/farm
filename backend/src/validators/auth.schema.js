// backend/src/validators/auth.schema.js
import { z } from 'zod';

export const registerProfileSchema = z.object({
  displayName: z.string().min(2).max(80),
  role: z.enum(['buyer', 'farmer', 'cooperative']), // admin is never self-assigned
  phone: z.string().min(7).max(20).optional(),
  orgName: z.string().min(2).max(120).optional(),
}).refine(
  (data) => data.role !== 'cooperative' || !!data.orgName,
  { message: 'orgName is required for cooperative accounts', path: ['orgName'] }
);

export const updateProfileSchema = z.object({
  displayName: z.string().min(2).max(80).optional(),
  phone: z.string().min(7).max(20).optional(),
  orgName: z.string().min(2).max(120).optional(),
  avatarUrl: z.string().url().optional(),
});
