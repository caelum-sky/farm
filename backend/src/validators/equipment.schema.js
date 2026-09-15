// backend/src/validators/equipment.schema.js
import { z } from 'zod';

export const createEquipmentSchema = z.object({
  name: z.string().min(2).max(120),
  description: z.string().max(2000).default(''),
  category: z.string().min(2).max(60), // "tractor", "tiller", "sprayer", ...
  listingType: z.enum(['sale', 'rent']),
  price: z.number().positive(), // sale price, or price-per-day when listingType === 'rent'
  images: z.array(z.string().url()).max(8).default([]),
});

export const updateEquipmentSchema = createEquipmentSchema.partial();
