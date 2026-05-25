/**
 * Конвертация jpg/png в public/assets/ → .webp рядом с оригиналом.
 * Запуск: npm run build:images
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'public', 'assets');
const exts = new Set(['.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG']);

async function main() {
  let sharp;
  try {
    sharp = (await import('sharp')).default;
  } catch {
    console.error('Установите sharp: npm install --save-dev sharp');
    process.exit(1);
  }

  const quality = Number(process.env.WEBP_QUALITY || 82);
  let converted = 0;
  let skipped = 0;

  async function walk(dir) {
    for (const name of fs.readdirSync(dir)) {
      const abs = path.join(dir, name);
      const st = fs.statSync(abs);
      if (st.isDirectory()) {
        await walk(abs);
        continue;
      }
      const ext = path.extname(name);
      if (!exts.has(ext)) {
        continue;
      }
      const out = abs.replace(/\.(jpe?g|png)$/i, '.webp');
      const srcMtime = st.mtimeMs;
      if (fs.existsSync(out)) {
        const outMtime = fs.statSync(out).mtimeMs;
        if (outMtime >= srcMtime) {
          skipped++;
          continue;
        }
      }
      await sharp(abs)
        .resize({ width: 2560, withoutEnlargement: true })
        .webp({ quality })
        .toFile(out);
      converted++;
      console.log(path.relative(root, out));
    }
  }

  if (!fs.existsSync(root)) {
    console.log('Нет папки public/assets');
    return;
  }
  await walk(root);
  console.log(`WebP: создано/обновлено ${converted}, без изменений ${skipped}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
