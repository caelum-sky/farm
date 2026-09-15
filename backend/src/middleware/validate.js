// backend/src/middleware/validate.js

/**
 * Validates req.body against a zod schema. On failure, responds 400 with a
 * flattened list of field errors instead of letting bad data reach Firestore.
 */
export function validate(schema) {
  return (req, res, next) => {
    const result = schema.safeParse(req.body);
    if (!result.success) {
      return res.status(400).json({
        error: 'Validation failed',
        details: result.error.flatten().fieldErrors,
      });
    }
    req.body = result.data;
    next();
  };
}
