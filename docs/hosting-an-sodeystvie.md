# an-sodeystvie.ru — объявления из CRM

Сайт берёт объекты из **https://an-realty-crm.ru** (стадия «Активный»).

## 1. Файлы витрины на REG.RU

Залейте актуальный ZIP (`npm run build:production` → `build/an-sodeystvie-public-hosting.zip`).

Для домена **an-sodeystvie.ru** в коде уже подставляется API `https://an-realty-crm.ru`, если нет `public/.env`. Надёжнее всё же создать **`public/.env`**:

```env
CRM_API_BASE=https://an-realty-crm.ru
CRM_PUBLIC_BASE=https://an-realty-crm.ru
CRM_LISTINGS_PATH=/api/public/listings
```

## 2. Обновить API на VPS (обязательно)

Без этого шага каталог не получит JSON, **описание** и полный список полей.

На сервере CRM:

```bash
cd /var/www/an-realty-crm && git pull origin main && cd apps/api && pnpm install && pnpm build && sudo systemctl restart an-realty-api
```

Проверка описания (после обновления API):

```bash
curl -sS "https://an-realty-crm.ru/api/public/listings/ID_ОБЪЕКТА/description"
```

Должен быть JSON с полем `description` (текст из CRM или `legalNotes`).

Добавьте в nginx блок для `/public/listings` (см. `deploy/nginx-crm-php-and-nest.example.conf`), затем:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Проверка:

```bash
curl -sS "https://an-realty-crm.ru/api/public/listings?limit=1"
```

Должен быть JSON с `items`, не HTML и не 404.

## 3. В CRM

У объекта стадия **«Активный»** — только такие попадают в публичный каталог.

## Где показываются объявления

- Главная — блок «Лучшие предложения» (до 8 объектов)
- `/catalog/` — полный каталог
- `/catalog/object/?id=…` — карточка объекта
