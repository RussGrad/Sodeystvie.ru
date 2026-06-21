# Безопасность витрины (an-sodeystvie.ru)

## Продакшен

- В `public/.env` **не** включайте `SITE_ALLOW_DEBUG=true` — иначе доступны `?phpinfo` и `?dbtest=1`.
- Задайте `PUBLIC_SITE_API_KEY` (тот же ключ, что в CRM `PUBLIC_SITE_API_KEY`).
- `CRM_API_BASE` — только ваш API (`https://an-realty-crm.ru`).
- Не кладите `.env` в ZIP: сборка `npm run build:production` удаляет его из архива.

## Что сделано в коде

| Область | Мера |
|--------|------|
| Отладка | `phpinfo` / тест БД только при `SITE_ALLOW_DEBUG=true` |
| Заявки | Same-origin, rate limit по IP, лимит тела 8 КБ, honeypot, санитизация полей |
| Онлайн-чат | Прокси без API-ключа в JS; same-origin GET/POST; rate limit; лимит длины; honeypot |
| Каталог | Валидация `id` объекта (буквы/цифры, до 64 символов) |
| HTTP к CRM | Ограничение редиректов, только http/https |
| Ответы | Заголовки `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` |
| Apache | `.htaccess` запрещает скачивание `.env` |
| JSON-контент | `public/data/.htaccess` — прямой доступ к JSON закрыт |
| Админка | `/admin/` — сессия, CSRF, rate limit входа; логин/хеш в `.env` (`SITE_ADMIN_*`) |

## Админка контента

URL: **https://an-sodeystvie.ru/admin/login.php** (или **/admin.php** — запасной вход)

Если `/admin/` отдаёт 404 на REG.RU — используйте прямую ссылку на `login.php` или `admin.php` в корне сайта.

В `public/.env` на сервере:

```env
SITE_ADMIN_LOGIN=admin
SITE_ADMIN_PASSWORD_HASH=<bcrypt из php -r "echo password_hash('пароль', PASSWORD_DEFAULT);">
```

Редактируются: контакты, тексты hero, команда, отзывы, кейсы, услуги, вакансии. Каталог объектов — только через CRM.

## Деплой после правок

```bash
cd Sodeystvie.ru && npm run build:production
```

Залить ZIP на REG.RU. На сервере вручную создать `public/.env` с ключами (не из git).

## CRM

Публичные эндпоинты CRM защищены ключом сайта; админка — JWT. См. `an-realty-crm/docs/security-rules.md`.
