const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

const outDir = path.join(__dirname, '..', 'public', 'assets');
fs.mkdirSync(outDir, { recursive: true });

const crcTable = (() => {
  const table = new Uint32Array(256);
  for (let n = 0; n < 256; n += 1) {
    let c = n;
    for (let k = 0; k < 8; k += 1) {
      c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    }
    table[n] = c >>> 0;
  }
  return table;
})();

function crc32(buffer) {
  let c = 0xffffffff;
  for (let i = 0; i < buffer.length; i += 1) {
    c = crcTable[(c ^ buffer[i]) & 0xff] ^ (c >>> 8);
  }
  return (c ^ 0xffffffff) >>> 0;
}

function chunk(type, data) {
  const typeBuffer = Buffer.from(type, 'ascii');
  const length = Buffer.alloc(4);
  length.writeUInt32BE(data.length, 0);
  const crc = Buffer.alloc(4);
  crc.writeUInt32BE(crc32(Buffer.concat([typeBuffer, data])), 0);
  return Buffer.concat([length, typeBuffer, data, crc]);
}

function writePng(file, width, height, paint) {
  const pixels = new Uint8Array(width * height * 4);
  const canvas = createCanvas(width, height, pixels);
  paint(canvas);

  const raw = Buffer.alloc((width * 4 + 1) * height);
  for (let y = 0; y < height; y += 1) {
    const rowStart = y * (width * 4 + 1);
    raw[rowStart] = 0;
    pixels.copyWithin;
    for (let x = 0; x < width * 4; x += 1) {
      raw[rowStart + 1 + x] = pixels[y * width * 4 + x];
    }
  }

  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(width, 0);
  ihdr.writeUInt32BE(height, 4);
  ihdr[8] = 8;
  ihdr[9] = 6;
  ihdr[10] = 0;
  ihdr[11] = 0;
  ihdr[12] = 0;

  const png = Buffer.concat([
    Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
    chunk('IHDR', ihdr),
    chunk('IDAT', zlib.deflateSync(raw, { level: 9 })),
    chunk('IEND', Buffer.alloc(0)),
  ]);

  fs.writeFileSync(path.join(outDir, file), png);
}

function createCanvas(width, height, pixels) {
  function set(x, y, color, alpha = color[3] ?? 255) {
    if (x < 0 || y < 0 || x >= width || y >= height) return;
    const i = (Math.floor(y) * width + Math.floor(x)) * 4;
    const a = alpha / 255;
    const inv = 1 - a;
    pixels[i] = Math.round((color[0] * a) + (pixels[i] * inv));
    pixels[i + 1] = Math.round((color[1] * a) + (pixels[i + 1] * inv));
    pixels[i + 2] = Math.round((color[2] * a) + (pixels[i + 2] * inv));
    pixels[i + 3] = 255;
  }

  function fill(color) {
    for (let i = 0; i < pixels.length; i += 4) {
      pixels[i] = color[0];
      pixels[i + 1] = color[1];
      pixels[i + 2] = color[2];
      pixels[i + 3] = 255;
    }
  }

  function rect(x, y, w, h, color, alpha = 255) {
    const x0 = Math.max(0, Math.floor(x));
    const y0 = Math.max(0, Math.floor(y));
    const x1 = Math.min(width, Math.ceil(x + w));
    const y1 = Math.min(height, Math.ceil(y + h));
    for (let yy = y0; yy < y1; yy += 1) {
      for (let xx = x0; xx < x1; xx += 1) set(xx, yy, color, alpha);
    }
  }

  function gradient(y0, y1, top, bottom) {
    for (let y = y0; y < y1; y += 1) {
      const t = (y - y0) / Math.max(1, y1 - y0);
      const color = [
        Math.round(top[0] + (bottom[0] - top[0]) * t),
        Math.round(top[1] + (bottom[1] - top[1]) * t),
        Math.round(top[2] + (bottom[2] - top[2]) * t),
      ];
      rect(0, y, width, 1, color);
    }
  }

  function ellipse(cx, cy, rx, ry, color, alpha = 255) {
    const x0 = Math.floor(cx - rx);
    const x1 = Math.ceil(cx + rx);
    const y0 = Math.floor(cy - ry);
    const y1 = Math.ceil(cy + ry);
    for (let y = y0; y <= y1; y += 1) {
      for (let x = x0; x <= x1; x += 1) {
        const dx = (x - cx) / rx;
        const dy = (y - cy) / ry;
        if (dx * dx + dy * dy <= 1) set(x, y, color, alpha);
      }
    }
  }

  function polygon(points, color, alpha = 255) {
    const minY = Math.max(0, Math.floor(Math.min(...points.map((p) => p[1]))));
    const maxY = Math.min(height - 1, Math.ceil(Math.max(...points.map((p) => p[1]))));
    for (let y = minY; y <= maxY; y += 1) {
      const nodes = [];
      let j = points.length - 1;
      for (let i = 0; i < points.length; i += 1) {
        const pi = points[i];
        const pj = points[j];
        if ((pi[1] < y && pj[1] >= y) || (pj[1] < y && pi[1] >= y)) {
          nodes.push(pi[0] + ((y - pi[1]) / (pj[1] - pi[1])) * (pj[0] - pi[0]));
        }
        j = i;
      }
      nodes.sort((a, b) => a - b);
      for (let i = 0; i < nodes.length; i += 2) {
        const x0 = Math.max(0, Math.floor(nodes[i]));
        const x1 = Math.min(width - 1, Math.ceil(nodes[i + 1]));
        for (let x = x0; x <= x1; x += 1) set(x, y, color, alpha);
      }
    }
  }

  function line(x0, y0, x1, y1, color, size = 2, alpha = 255) {
    const steps = Math.max(Math.abs(x1 - x0), Math.abs(y1 - y0));
    for (let i = 0; i <= steps; i += 1) {
      const t = i / Math.max(1, steps);
      ellipse(x0 + (x1 - x0) * t, y0 + (y1 - y0) * t, size, size, color, alpha);
    }
  }

  return { width, height, fill, rect, gradient, ellipse, polygon, line };
}

function drawFieldScene(canvas, variant = 'hero') {
  const { width: w, height: h } = canvas;
  canvas.gradient(0, h * 0.58, [126, 181, 221], [228, 243, 238]);
  canvas.ellipse(w * 0.78, h * 0.16, w * 0.08, w * 0.08, [243, 186, 54], 235);
  canvas.ellipse(w * 0.78, h * 0.16, w * 0.14, w * 0.14, [243, 186, 54], 42);

  canvas.polygon([[0, h * 0.5], [w * 0.32, h * 0.35], [w * 0.62, h * 0.51], [w, h * 0.38], [w, h], [0, h]], [85, 156, 84]);
  canvas.polygon([[0, h * 0.62], [w * 0.42, h * 0.47], [w, h * 0.57], [w, h], [0, h]], [233, 178, 61]);
  canvas.polygon([[0, h * 0.73], [w * 0.28, h * 0.6], [w * 0.54, h * 0.69], [w, h * 0.58], [w, h], [0, h]], [38, 104, 68]);
  canvas.polygon([[0, h * 0.82], [w, h * 0.69], [w, h], [0, h]], [94, 64, 45]);

  for (let i = 0; i < 18; i += 1) {
    const startX = -w * 0.15 + i * (w / 10);
    canvas.line(startX, h, w * 0.48 + i * 18, h * 0.64, [114, 82, 53], 3, 120);
  }
  for (let i = 0; i < 12; i += 1) {
    canvas.line(w * 0.52 + i * 55, h, w * 0.7 + i * 12, h * 0.63, [246, 216, 112], 2, 130);
  }

  drawTractor(canvas, w * 0.42, h * 0.62, w * (variant === 'hero' ? 0.22 : 0.32));
  drawCrates(canvas, w * 0.12, h * 0.72, w * (variant === 'hero' ? 0.13 : 0.2));
  drawPerson(canvas, w * 0.68, h * 0.66, w * (variant === 'hero' ? 0.045 : 0.07));
}

function drawTractor(canvas, x, y, size) {
  canvas.ellipse(x + size * 0.2, y + size * 0.47, size * 0.16, size * 0.16, [31, 49, 43]);
  canvas.ellipse(x + size * 0.2, y + size * 0.47, size * 0.09, size * 0.09, [232, 236, 225]);
  canvas.ellipse(x + size * 0.76, y + size * 0.5, size * 0.24, size * 0.24, [31, 49, 43]);
  canvas.ellipse(x + size * 0.76, y + size * 0.5, size * 0.13, size * 0.13, [232, 236, 225]);
  canvas.rect(x + size * 0.1, y + size * 0.18, size * 0.54, size * 0.22, [41, 132, 78]);
  canvas.rect(x + size * 0.55, y + size * 0.04, size * 0.24, size * 0.34, [31, 94, 70]);
  canvas.rect(x + size * 0.6, y + size * 0.09, size * 0.13, size * 0.13, [157, 205, 224], 230);
  canvas.rect(x + size * 0.03, y + size * 0.24, size * 0.17, size * 0.12, [241, 183, 45]);
  canvas.rect(x + size * 0.78, y - size * 0.08, size * 0.05, size * 0.18, [36, 45, 43]);
  canvas.line(x + size * 0.04, y + size * 0.44, x - size * 0.26, y + size * 0.64, [36, 45, 43], 3);
}

function drawCrates(canvas, x, y, size) {
  for (let row = 0; row < 2; row += 1) {
    for (let col = 0; col < 3; col += 1) {
      const cx = x + col * size * 0.28 + row * size * 0.06;
      const cy = y + row * size * 0.2;
      canvas.rect(cx, cy, size * 0.24, size * 0.16, [133, 87, 50]);
      canvas.rect(cx + 4, cy + 4, size * 0.24 - 8, size * 0.16 - 8, [182, 115, 58]);
      for (let i = 0; i < 5; i += 1) {
        canvas.ellipse(cx + size * 0.05 + i * size * 0.035, cy + size * 0.06, size * 0.025, size * 0.025, [219, 86, 66]);
      }
    }
  }
}

function drawPerson(canvas, x, y, size) {
  canvas.ellipse(x, y - size * 0.35, size * 0.12, size * 0.12, [137, 83, 55]);
  canvas.rect(x - size * 0.18, y - size * 0.22, size * 0.36, size * 0.42, [238, 177, 45]);
  canvas.line(x - size * 0.08, y + size * 0.2, x - size * 0.18, y + size * 0.58, [37, 76, 87], 4);
  canvas.line(x + size * 0.08, y + size * 0.2, x + size * 0.18, y + size * 0.58, [37, 76, 87], 4);
  canvas.line(x - size * 0.18, y - size * 0.08, x - size * 0.38, y + size * 0.1, [137, 83, 55], 3);
  canvas.line(x + size * 0.18, y - size * 0.08, x + size * 0.42, y - size * 0.02, [137, 83, 55], 3);
}

writePng('hero-farm-market.png', 1600, 1000, (canvas) => drawFieldScene(canvas, 'hero'));

writePng('equipment-tractor.png', 900, 650, (canvas) => {
  canvas.gradient(0, 650, [185, 224, 221], [246, 248, 235]);
  canvas.polygon([[0, 380], [900, 310], [900, 650], [0, 650]], [55, 123, 77]);
  for (let i = 0; i < 10; i += 1) canvas.line(i * 120 - 100, 650, 380 + i * 26, 360, [32, 91, 65], 4, 90);
  drawTractor(canvas, 190, 245, 460);
  canvas.rect(40, 70, 210, 26, [239, 183, 45], 210);
  canvas.rect(40, 110, 310, 22, [31, 94, 70], 190);
});

writePng('produce-crates.png', 900, 650, (canvas) => {
  canvas.gradient(0, 650, [130, 181, 220], [244, 242, 222]);
  canvas.polygon([[0, 390], [900, 330], [900, 650], [0, 650]], [226, 180, 66]);
  drawCrates(canvas, 160, 320, 540);
  canvas.ellipse(650, 210, 95, 95, [240, 181, 46], 210);
  for (let i = 0; i < 12; i += 1) canvas.line(i * 90, 650, 380 + i * 24, 360, [122, 82, 50], 3, 100);
});

writePng('farmer-market.png', 900, 650, (canvas) => {
  canvas.gradient(0, 650, [149, 200, 223], [237, 246, 232]);
  canvas.polygon([[0, 360], [900, 410], [900, 650], [0, 650]], [72, 139, 91]);
  canvas.rect(450, 300, 260, 150, [42, 101, 84]);
  canvas.rect(480, 330, 200, 90, [242, 188, 58]);
  drawCrates(canvas, 155, 375, 420);
  drawPerson(canvas, 560, 330, 190);
  canvas.line(695, 447, 780, 510, [37, 62, 58], 5);
  canvas.ellipse(795, 520, 36, 36, [37, 62, 58]);
  canvas.ellipse(795, 520, 19, 19, [232, 236, 225]);
});

console.log('Generated FarmBridge image assets in public/assets.');
