// backend/src/validators/product.schema.js
import { z } from 'zod';

export const createProductSchema = z.object({
  name: z.string().min(2).max(120),
  description: z.string().max(2000).default(''),
  category: z.enum(['produce', 'supply']),
  subcategory: z.string().min(2).max(60), // e.g. "fruits", "vegetables", "fertilizer"
  price: z.number().positive(),
  unit: z.string().min(1).max(20), // "kg", "sack", "piece"
  stock: z.number().int().nonnegative(),
  images: z.array(z.string().url()).max(8).default([]),
  tags: z.array(z.enum(['organic', 'just-harvested', 'member-price'])).default([]),
});

export const updateProductSchema = createProductSchema.partial();
