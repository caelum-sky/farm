import 'dotenv/config';
import app from './app.js';

const PORT = Number(process.env.PORT) || 8080;

const server = app.listen(PORT, '0.0.0.0', () => {
  console.log(`FarmHub API listening on 0.0.0.0:${PORT}`);
  console.log(`Environment: ${process.env.NODE_ENV || 'development'}`);
});

process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down');

  server.close(() => {
    console.log('HTTP server closed');
    process.exit(0);
  });
});

process.on('SIGINT', () => {
  console.log('SIGINT received, shutting down');

  server.close(() => {
    console.log('HTTP server closed');
    process.exit(0);
  });
});