const http = require('http');
const fs = require('fs');
const path = require('path');

const publicDir = path.resolve(__dirname, '..', 'public');
const port = Number(process.env.PORT || 4173);

const routes = new Map([
  ['/', 'preview.html'],
  ['/marketplace', 'preview.html'],
  ['/login', 'preview-login.html'],
  ['/signup', 'preview-signup.html'],
]);

const contentTypes = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.svg': 'image/svg+xml',
};

function send(res, status, body, type = 'text/plain; charset=utf-8') {
  res.writeHead(status, { 'Content-Type': type });
  res.end(body);
}

function safeFilePath(urlPath) {
  const cleanPath = decodeURIComponent(urlPath).replace(/^[/\\]+/, '');
  const normalized = path.normalize(cleanPath).replace(/^(\.\.[/\\])+/, '');
  const file = path.resolve(publicDir, normalized);
  return file === publicDir || file.startsWith(publicDir + path.sep) ? file : null;
}

const server = http.createServer((req, res) => {
  const url = new URL(req.url, `http://localhost:${port}`);
  const routeFile = routes.get(url.pathname);
  const filePath = routeFile
    ? path.join(publicDir, routeFile)
    : safeFilePath(url.pathname);

  if (!filePath) {
    send(res, 403, 'Forbidden');
    return;
  }

  fs.readFile(filePath, (error, data) => {
    if (error) {
      send(res, 404, 'Not found');
      return;
    }

    const ext = path.extname(filePath).toLowerCase();
    send(res, 200, data, contentTypes[ext] || 'application/octet-stream');
  });
});

server.listen(port, () => {
  if (process.env.PREVIEW_VERBOSE) {
    console.log(`FarmBridge preview running at http://localhost:${port}`);
  }
});
