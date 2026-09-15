// backend/src/middleware/errorHandler.js

export class ApiError extends Error {
  constructor(statusCode, message) {
    super(message);
    this.statusCode = statusCode;
  }
}

export function notFoundHandler(req, res) {
  res.status(404).json({ error: `No route for ${req.method} ${req.originalUrl}` });
}

// `next` is unused but required — Express only treats a 4-arg function as an
// error handler, so the parameter has to stay in the signature.
export function errorHandler(err, req, res, next) {
  const statusCode = err.statusCode || 500;
  const message = statusCode === 500 && process.env.NODE_ENV === 'production'
    ? 'Something went wrong on our end'
    : err.message;

  if (statusCode === 500) {
    console.error(err);
  }

  res.status(statusCode).json({ error: message });
}
