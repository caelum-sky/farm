// backend/tests/validators.test.js
import test from 'node:test';
import assert from 'node:assert/strict';
import { createProductSchema } from '../src/validators/product.schema.js';
import { createRentalSchema, createOrderSchema } from '../src/validators/transaction.schema.js';
import { registerProfileSchema } from '../src/validators/auth.schema.js';

test('product schema rejects negative prices', () => {
  const result = createProductSchema.safeParse({
    name: 'Tomatoes', category: 'produce', subcategory: 'vegetables',
    price: -5, unit: 'kg', stock: 10,
  });
  assert.equal(result.success, false);
});

test('product schema applies defaults for images and tags', () => {
  const result = createProductSchema.safeParse({
    name: 'Tomatoes', category: 'produce', subcategory: 'vegetables',
    price: 60, unit: 'kg', stock: 10,
  });
  assert.equal(result.success, true);
  assert.deepEqual(result.data.images, []);
  assert.deepEqual(result.data.tags, []);
});

test('rental schema rejects an end date before the start date', () => {
  const result = createRentalSchema.safeParse({
    equipmentId: 'abc',
    startDate: '2026-06-10T00:00:00.000Z',
    endDate: '2026-06-08T00:00:00.000Z',
  });
  assert.equal(result.success, false);
});

test('order schema requires at least one line item', () => {
  assert.equal(createOrderSchema.safeParse({ items: [] }).success, false);
  assert.equal(
    createOrderSchema.safeParse({ items: [{ productId: 'p1', qty: 2 }] }).success,
    true
  );
});

test('profile schema refuses a self-assigned admin role', () => {
  const result = registerProfileSchema.safeParse({ displayName: 'Mallory', role: 'admin' });
  assert.equal(result.success, false);
});

test('cooperative signup requires an organization name', () => {
  assert.equal(
    registerProfileSchema.safeParse({ displayName: 'Coop', role: 'cooperative' }).success,
    false
  );
  assert.equal(
    registerProfileSchema.safeParse({ displayName: 'Coop', role: 'cooperative', orgName: 'Davao Farmers Coop' }).success,
    true
  );
});
