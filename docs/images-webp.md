# WebP на витрине

## Два канала

| Источник | Как получается WebP |
|----------|---------------------|
| **Локальные файлы** `public/assets/**/*.jpg\|png` | `npm run build:images` (sharp) → файл `.webp` рядом; в HTML — `<picture>` |
| **Фото CRM / Яндекс.Диск** | Прокси `GET /api/image.php?u=…` — PHP GD, кэш в `public/cache/img/` |

## Команды

```bash
npm run build:images   # только картинки в assets/
npm run build          # CSS + WebP
```

После деплоя на REG.RU папка `public/cache/img/` должна быть **доступна на запись** (создаётся автоматически при первом запросе).

## Настройка (.env)

```env
SITE_IMAGE_WEBP=true
SITE_IMAGE_WEBP_QUALITY=82
SITE_IMAGE_WEBP_MAX_WIDTH=1920
```

Отключить: `SITE_IMAGE_WEBP=false` — снова отдаются исходные JPG/URL.

## Требования хостинга

- PHP с **GD** и функцией `imagewebp` (проверка: открыть `/api/image.php?path=/assets/hero/hero-bg.jpg` — должен вернуть картинку webp).

## Деплой

В GitHub Actions перед FTP выполняется `npm run build` (CSS + WebP для assets).
