# Выкат витрины и CRM API

## 1. CRM API (VPS `/var/www/an-realty-crm`)

```bash
cd /var/www/an-realty-crm
git pull origin main
cd apps/api && pnpm install && pnpm build
sudo systemctl restart an-realty-api
```

**Проверка:**

```bash
curl -sS "https://an-realty-crm.ru/api/public/listings?limit=1" | head -c 400
# В items[0] должны быть: description, addressLine, photos (при наличии фото)

curl -sS "https://an-realty-crm.ru/api/public/listings/ID/description"
# {"id":"...","description":"..."} — не 404
```

## 2. Витрина Sodeystvie.ru

### Локально перед выкатом

```bash
cd Sodeystvie.ru
npm run build:css
# или полный архив:
npm run build:production
```

### На REG.RU (FTP / GitHub Actions)

Загрузить из `public/` (или ZIP из `build:production`):

- `includes/crm-listing-helpers.php`
- `includes/config.php` (если менялся)
- `catalog/index.php`, `catalog/object/index.php`
- `api/crm-resolve-photo.php`
- `js/catalog-listing-card.js`
- `css/main.css`
- `includes/header.php`, `js/site-header.js`, `js/site-favorites.js` (после Фазы 1)

Очистить кэш CRM на сервере при необходимости: удалить `public/var/crm-cache/*` на хостинге.

### Docker (локальная разработка)

```bash
docker compose up -d
# http://localhost:8090
```

## 3. Переменные `.env` на хостинге

```env
CRM_API_BASE=https://an-realty-crm.ru
CRM_PUBLIC_BASE=https://an-realty-crm.ru
SITE_WHATSAPP_URL=https://wa.me/73952603808
SITE_TELEGRAM_URL=https://t.me/your_channel
SITE_MAX_URL=https://max.ru/your_profile
```

---

*См. также: [hosting-an-sodeystvie.md](./hosting-an-sodeystvie.md), [github-deploy-ftp.md](./github-deploy-ftp.md)*
