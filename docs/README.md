# Документация проекта: сайт агентства недвижимости

## Назначение

Здесь собраны материалы для согласования объёма работ и приёмки: бизнес-цели, термины, сценарии, функциональное ТЗ, нефункциональные требования, SEO/контент, интеграции, критерии приёмки и открытые вопросы.

**Документы — источник истины; реализация в коде следует за утверждённым ТЗ.**

Краткий контекст проекта для людей и ИИ-ассистентов в IDE — в корне репозитория: [`CLAUDE.md`](../CLAUDE.md) (не заменяет полное ТЗ в `docs/`). Там же в конце — **как при желании подключить Claude Code CLI** к этому репозиторию.

## Как читать

| Порядок | Файл | Содержание |
|--------:|------|------------|
| 1 | [01-business-goals.md](01-business-goals.md) | Цели, аудитория, KPI |
| 2 | [02-glossary.md](02-glossary.md) | Термины |
| 3 | [03-user-scenarios.md](03-user-scenarios.md) | Пользовательские сценарии |
| 4 | [04-functional-spec.md](04-functional-spec.md) | **Основное функциональное ТЗ** |
| 5 | [05-non-functional.md](05-non-functional.md) | NFR: производительность, безопасность, a11y |
| 6 | [06-content-and-seo.md](06-content-and-seo.md) | Контент и SEO |
| 7 | [07-integrations.md](07-integrations.md) | Интеграции (Bitrix24 и др.) |
| 8 | [08-acceptance-criteria.md](08-acceptance-criteria.md) | Критерии приёмки |
| 9 | [09-open-questions.md](09-open-questions.md) | Нерешённые вопросы |
| — | [tz-an-sodeystvie-2026.md](tz-an-sodeystvie-2026.md) | **ТЗ доработки сайта 2026** + чеклист фаз |
| — | [deploy-vitrina-checklist.md](deploy-vitrina-checklist.md) | Выкат витрины и CRM API |
| — | [10-next-steps-after-tz.md](10-next-steps-after-tz.md) | Шаги после заморозки ТЗ |
| — | [11-architecture.md](11-architecture.md) | **Архитектура кода и инфраструктуры** (реализация) |

## Зафиксированные технические решения

| Решение | Где подробнее |
|---------|----------------|
| Бэкенд на **PHP 8.3** | [04-functional-spec.md](04-functional-spec.md) §0 |
| Фронтенд: **HTML, SCSS, чистый JS**, вёрстка по **БЭМ** | [04-functional-spec.md](04-functional-spec.md) §0; [05-non-functional.md](05-non-functional.md) |
| **Хостинг REG.RU**, домен **an-sodeystvie.ru** | [04-functional-spec.md](04-functional-spec.md) §0 |
| Вёрстка **сначала шапка/подвал главной**, затем **секции** | [04-functional-spec.md](04-functional-spec.md) §1.1, §3 |
| **Каталог объектов** — обязателен в MVP | [04-functional-spec.md](04-functional-spec.md) §1, §7 |
| Лиды в **Bitrix24** | [04-functional-spec.md](04-functional-spec.md) §0, §8; [07-integrations.md](07-integrations.md) |
| Каталог: **выгрузка из CRM** (Bitrix24) | [04-functional-spec.md](04-functional-spec.md) §0, §7.4; [07-integrations.md](07-integrations.md) |
| Языки: **только RU** (первый этап) | [04-functional-spec.md](04-functional-spec.md) §0; [05-non-functional.md](05-non-functional.md) |
| **Логотип** — [`logo-text.svg`](../assets/brand/logo-text.svg) (+ PNG в [`../assets/brand/`](../assets/brand/)) | [04-functional-spec.md](04-functional-spec.md) §3.1; [06-content-and-seo.md](06-content-and-seo.md) |
| **Светлая и тёмная тема** (переключатель + сохранение) | [04-functional-spec.md](04-functional-spec.md) §0, §3.1; [05-non-functional.md](05-non-functional.md) |
| Фон **тёмной** темы — референс [`dark-theme-bg-reference.jpg`](../assets/brand/references/dark-theme-bg-reference.jpg) | [04-functional-spec.md](04-functional-spec.md) §0; [05-non-functional.md](05-non-functional.md) |

## Версия и согласование

| Версия ТЗ | Дата | Примечание |
|-----------|------|------------|
| — | 2026-04-19 | Из корня проекта убран Claude Code (`npm`/Claude Code CLI) |
| 0.15 | 2026-04-19 | Референс фона тёмной темы (фото + токены цвета) |
| 0.14 | 2026-04-19 | Добавлен вектор логотипа logo-text.svg |
| 0.13 | 2026-04-19 | Светлая и тёмная тема; рекомендация по SVG логотипа |
| 0.12 | 2026-04-19 | Утверждённый логотип (файл в assets/brand) |
| 0.11 | 2026-04-19 | Логотип компании: требования к шапке и материалам |
| 0.10 | 2026-04-19 | Фронтенд: HTML, SCSS, чистый JS, БЭМ |
| 0.9 | 2026-04-19 | Языки первого этапа: только RU |
| 0.8 | 2026-04-19 | Каталог: выгрузка / синхронизация из CRM |
| 0.7 | 2026-04-19 | Каналы лидов: Bitrix24 |
| 0.6 | 2026-04-19 | Каталог объектов обязателен в MVP |
| 0.5 | 2026-04-19 | Поэтапно: главная — шапка/подвал, затем секции |
| 0.4 | 2026-04-19 | Уточнены REG.RU и домен an-sodeystvie.ru |
| 0.3 | 2026-04-19 | Зафиксированы хостинг и домен у заказчика |
| 0.2 | 2026-04-19 | Зафиксирован бэкенд PHP 8.3 |
| 0.1 | 2026-04-19 | Первичная структура и черновик разделов |

Изменения: правки вносятся в соответствующие файлы; при существенных изменениях — обновлять версию в этой таблице и кратко фиксировать в [09-open-questions.md](09-open-questions.md) или в коммите.

## Ритм работы (рекомендуемый)

1. Утвердить оглавление и состав файлов.
2. Заполнять разделы по одному; после крупных блоков — пауза на ревью.
3. Разработка главной — **сначала шапка и подвал, затем секции** ([04-functional-spec.md](04-functional-spec.md) §1.1).
4. После фиксации ТЗ и критериев приёмки — переход к репозиторию, стеку и разработке ([10-next-steps-after-tz.md](10-next-steps-after-tz.md)).
