// backend/src/middleware/authorize.js

/**
 * Restricts a route to one or more roles. Must run after `authenticate`.
 * Usage: router.post('/products', authenticate, authorize('farmer', 'cooperative'), handler)
 */
export function authorize(...allowedRoles) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' });
    }
    if (!allowedRoles.includes(req.user.role)) {
      return res.status(403).json({ error: 'You do not have permission to do this' });
    }
    next();
  };
}

/** Shorthand for admin-only routes. */
export const requireAdmin = authorize('admin');

/** Sellers = farmers or cooperatives. */
export const requireSeller = authorize('farmer', 'cooperative');
