// backend/src/validators/transaction.schema.js
import { z } from 'zod';

export const createOrderSchema = z.object({
  items: z.array(z.object({
    productId: z.string().min(1),
    qty: z.number().int().positive(),
  })).min(1),
});

export const createRentalSchema = z.object({
  equipmentId: z.string().min(1),
  startDate: z.string().datetime(),
  endDate: z.string().datetime(),
}).refine((d) => new Date(d.endDate) > new Date(d.startDate), {
  message: 'endDate must be after startDate',
  path: ['endDate'],
});

export const updateRentalStatusSchema = z.object({
  status: z.enum(['approved', 'active', 'returned', 'cancelled']),
});

export const updateOrderStatusSchema = z.object({
  status: z.enum(['placed', 'confirmed', 'fulfilled', 'cancelled']),
});

export const createReportSchema = z.object({
  targetType: z.enum(['product', 'equipment', 'user']),
  targetId: z.string().min(1),
  reason: z.string().min(3).max(80),
  details: z.string().max(1000).default(''),
});

export const createReviewSchema = z.object({
  targetType: z.enum(['product', 'equipment', 'user']),
  targetId: z.string().min(1),
  rating: z.number().int().min(1).max(5),
  comment: z.string().max(1000).default(''),
});
