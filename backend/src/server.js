// backend/src/server.js
import 'dotenv/config';
import app from './app.js';

const PORT = process.env.PORT || 8080;

const server = app.listen(PORT, () => {
  console.log(`FarmHub API listening on :${PORT} (${process.env.NODE_ENV || 'development'})`);
});

// Render sends SIGTERM on redeploy — drain in-flight requests before exiting
process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down');
  server.close(() => process.exit(0));
});
