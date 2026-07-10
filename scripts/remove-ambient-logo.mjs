/**
 * Убирает встроенный логотип с фонового изображения офиса.
 *
 * Запуск: node scripts/remove-ambient-logo.mjs [input.png] [output.png]
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const input = process.argv[2] || path.join(root, 'public/assets/brand/site-ambient-bg-source.png');
const output = process.argv[3] || path.join(root, 'public/assets/brand/site-ambient-bg.png');

const PATCH = { left: 55, top: 20, width: 915, height: 720 };
const TEXTURE = { left: 8, top: 0, width: 185 };

async function buildMask(width, height) {
  const padX = 48;
  const padY = 36;
  const svg = `<svg width="${width}" height="${height}" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <filter id="soft" x="-20%" y="-20%" width="140%" height="140%">
        <feGaussianBlur stdDeviation="42"/>
      </filter>
    </defs>
    <rect width="100%" height="100%" fill="#000" opacity="0"/>
    <rect x="${padX}" y="${padY}" width="${width - padX * 2}" height="${height - padY * 2}" fill="#fff" filter="url(#soft)"/>
  </svg>`;
  return sharp(Buffer.from(svg)).png().toBuffer();
}

async function main() {
  if (!fs.existsSync(input)) {
    console.error('Input not found:', input);
    process.exit(1);
  }

  const { width, height } = await sharp(input).metadata();
  if (!width || !height) {
    throw new Error('Invalid image dimensions');
  }

  const patch = {
    left: PATCH.left,
    top: PATCH.top,
    width: Math.min(PATCH.width, width - PATCH.left),
    height: Math.min(PATCH.height, height - PATCH.top),
  };

  const blurredBase = await sharp(input)
    .extract(patch)
    .blur(40)
    .modulate({ brightness: 0.78, saturation: 0.7 })
    .toBuffer();

  const texture = await sharp(input)
    .extract({
      left: TEXTURE.left,
      top: TEXTURE.top,
      width: TEXTURE.width,
      height,
    })
    .resize(patch.width, patch.height, { fit: 'cover', position: 'centre' })
    .modulate({ brightness: 0.86, saturation: 0.84 })
    .blur(0.9)
    .toBuffer();

  const fill = await sharp(texture)
    .composite([{ input: blurredBase, blend: 'over', opacity: 0.38 }])
    .png()
    .toBuffer();

  const mask = await buildMask(patch.width, patch.height);
  const maskedFill = await sharp(fill)
    .composite([{ input: mask, blend: 'dest-in' }])
    .png()
    .toBuffer();

  await sharp(input)
    .composite([{ input: maskedFill, left: patch.left, top: patch.top }])
    .png()
    .toFile(output);

  const meta = await sharp(output).metadata();
  console.log('Saved', output, `${meta.width}x${meta.height}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
