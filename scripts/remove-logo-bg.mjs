/**
 * Удаление тёмного фона у премиум-логотипа (flood-fill от краёв).
 * Запуск: node scripts/remove-logo-bg.mjs [input.png] [output.png]
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const input = process.argv[2] || path.join(root, 'public/assets/brand/logo-premium-source.png');
const output = process.argv[3] || path.join(root, 'public/assets/brand/logo-premium.png');

function colorDist(a, b) {
  const dr = a[0] - b[0];
  const dg = a[1] - b[1];
  const db = a[2] - b[2];
  return Math.sqrt(dr * dr + dg * dg + db * db);
}

function isLikelyBackground(r, g, b, a) {
  if (a < 8) return true;
  const lum = 0.2126 * r + 0.7152 * g + 0.0722 * b;
  if (lum > 118) return false;
  if (r > 95 && g > 95 && b > 95) return false;
  // золото/бронза в иконке
  if (r > 120 && g > 90 && b < 95) return false;
  // серебро
  if (r > 105 && g > 105 && b > 105) return false;
  // стена офиса — холодный сине-серый оттенок
  const coolWall = b >= r + 4 && g >= r - 2 && lum < 88;
  if (coolWall) return true;
  // тени на стене (очень тёмные, но не часть логотипа)
  if (lum < 28 && b >= r) return true;
  return false;
}

async function main() {
  if (!fs.existsSync(input)) {
    console.error('Input not found:', input);
    process.exit(1);
  }

  const { data, info } = await sharp(input).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const { width, height, channels } = info;
  const out = Buffer.from(data);
  const visited = new Uint8Array(width * height);
  const queue = [];

  const push = (x, y) => {
    if (x < 0 || y < 0 || x >= width || y >= height) return;
    const idx = y * width + x;
    if (visited[idx]) return;
    const i = idx * channels;
    const px = [out[i], out[i + 1], out[i + 2], out[i + 3]];
    if (!isLikelyBackground(px[0], px[1], px[2], px[3])) return;
    visited[idx] = 1;
    queue.push(idx);
  };

  for (let x = 0; x < width; x++) {
    push(x, 0);
    push(x, height - 1);
  }
  for (let y = 0; y < height; y++) {
    push(0, y);
    push(width - 1, y);
  }

  while (queue.length) {
    const idx = queue.pop();
    const i = idx * channels;
    out[i + 3] = 0;
    const x = idx % width;
    const y = (idx - x) / width;
    push(x - 1, y);
    push(x + 1, y);
    push(x, y - 1);
    push(x, y + 1);
  }

  // Мягкая кромка: полупрозрачные тёмные пиксели у границы логотипа
  for (let y = 1; y < height - 1; y++) {
    for (let x = 1; x < width - 1; x++) {
      const idx = y * width + x;
      const i = idx * channels;
      if (out[i + 3] === 0) continue;
      let transparentNeighbors = 0;
      for (const [nx, ny] of [[x - 1, y], [x + 1, y], [x, y - 1], [x, y + 1]]) {
        if (out[(ny * width + nx) * channels + 3] === 0) transparentNeighbors++;
      }
      if (transparentNeighbors >= 2 && isLikelyBackground(out[i], out[i + 1], out[i + 2], out[i + 3])) {
        out[i + 3] = Math.min(out[i + 3], 48);
      }
    }
  }

  const trimmed = await sharp(out, { raw: { width, height, channels } })
    .trim({ threshold: 12 })
    .png()
    .toBuffer();

  await sharp(trimmed).png().toFile(output);
  const meta = await sharp(output).metadata();
  console.log('Saved', output, `${meta.width}x${meta.height}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
