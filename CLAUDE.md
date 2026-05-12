# Контекст проекта

Кратко для разработки сайта и для ИИ-ассистентов в редакторе (например Cursor): что это за проект и куда смотреть в ТЗ.

Сайт агентства недвижимости **Содействие** (домен: `an-sodeystvie.ru`).

## Документация (источник истины)

- Техническое задание и решения: каталог [`docs/`](docs/), начните с [`docs/README.md`](docs/README.md).
- Архитектура текущей реализации (PHP, `public/`, Docker, SCSS): [`docs/11-architecture.md`](docs/11-architecture.md).

## Стек (зафиксировано в ТЗ)

- Бэкенд: **PHP 8.3**
- Фронтенд: **HTML, SCSS, чистый JavaScript**, **БЭМ**
- Лиды: **Bitrix24**; каталог объектов: выгрузка из **CRM (Bitrix24)**
- Хостинг: **REG.RU**; темы: **светлая и тёмная** (референс тёмного фона в `assets/brand/references/`)
- Бренд: `assets/brand/` (`logo-text.svg` и др.)

Перед существенными изменениями сверяйтесь с `docs/04-functional-spec.md` и связанными файлами.

## Локальный PHP 8.3 + PostgreSQL (Docker)

- Сборка PHP с **pdo_pgsql**: [`Dockerfile`](Dockerfile). Оркестрация: [`docker-compose.yml`](docker-compose.yml).
- Сервисы: **`web`** (Apache + PHP 8.3), **`db`** (PostgreSQL 16). Данные БД в volume `postgres_data`.
- Учётные данные по умолчанию: см. [`.env.example`](.env.example) — скопируйте в **`.env`** при смене пароля (`.env` в git не входит).
- Хост БД из PHP: **`db`**, порт **5432**, переменные в контейнере `web`: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- **CRM API (каталог объектов):** переменные **`CRM_API_BASE`** и **`CRM_PUBLIC_BASE`** (см. `.env.example`) указывают на Nest из **an-realty-crm** (`pnpm api`, для этого сайта по умолчанию порт **3000** — см. `PORT` в `apps/api/.env`). На хосте должны быть запущены CRM API и при необходимости БД CRM; PHP в Docker ходит на API через **`host.docker.internal`** (см. `public/includes/config.php`).
- Порты: сайт **http://localhost:8080/** (или `WEB_PORT`), PostgreSQL с хоста **localhost:5432** (для DBeaver, `psql` и т.д.).
- Команды: `docker compose up -d --build` (первый раз или после правок Dockerfile), `docker compose down` (остановка; volume с данными сохранится).

### Prisma Studio (просмотр PostgreSQL)

Только для разработки: в корне **`npm install`**, в **`.env`** должен быть **`DATABASE_URL`** (см. [`.env.example`](.env.example)). Контейнер **db** должен быть запущен.

Запуск веб-интерфейса по умолчанию **http://localhost:5555**:

```bash
npm run prisma:studio
```

После появления таблиц в БД можно подтянуть схему: `npm run prisma:pull` (обновит [`prisma/schema.prisma`](prisma/schema.prisma)).

## Если позже подключите Claude Code (CLI)

Сейчас в проекте **нет** `npm`-зависимости на Claude Code — достаточно Cursor. Если понадобится терминальный агент **Claude Code**:

1. Нужен доступ Anthropic: подписка Claude **или** биллинг API в [Anthropic Console](https://console.anthropic.com/) (при первом `claude` выберите способ входа).
2. В корне репозитория: `npm init -y`, затем `npm install -D @anthropic-ai/claude-code`, в `package.json` удобно добавить скрипт `"claude": "claude"`.
3. Запуск: `npx claude` или `npm run claude`. Этот файл **`CLAUDE.md`** инструмент подхватывает как контекст проекта — менять название файла не нужно.

Полное ТЗ по-прежнему в **`docs/`**; `CLAUDE.md` только сжимает ориентиры.
