# GitHub → сайт (пуш и автодеплой по FTP)

Цель: код в репозитории на GitHub, при каждом пуше в `main` или `master` GitHub Actions собирает SCSS и выгружает **только** каталог `public/` на хостинг (REG.RU / ispmgr и аналоги с FTP).

## 1. Репозиторий на GitHub

В каталоге проекта (если ещё нет git):

```bash
cd Sodeystvie.ru
git init
git add .
git commit -m "Initial commit: витрина + деплой"
```

Создайте пустой репозиторий на GitHub (без README, если уже есть локальный коммит), затем:

```bash
git remote add origin https://github.com/ВАШ_ЛОГИН/ВАШ_РЕПО.git
git branch -M main
git push -u origin main
```

Файл `.env` в корне репозитория **не коммитьте** (он в `.gitignore`). На сервере для PHP используется **`public/.env`** — создайте его один раз вручную в ispmgr / по FTP (см. `.env.example`).

## 2. Данные FTP в ispmgr / REG.RU

В панели хостинга найдите: хост FTP (часто `ftp.ваш-домен.ru` или IP), логин, пароль, **каталог сайта** (document root), например `www/an-sodeystvie.ru` или `public_html`.

Убедитесь, что в этот каталог попадают файлы **как из текущего `public/`** (рядом должны быть `index.php`, `includes/`, `css/`).

## 3. Секреты и переменные в GitHub

**Settings → Secrets and variables → Actions**

### Secrets (обязательно для деплоя)

| Имя | Значение |
|-----|----------|
| `FTP_SERVER` | Хост FTP |
| `FTP_USERNAME` | Логин |
| `FTP_PASSWORD` | Пароль |

### Variables (по необходимости)

| Имя | Пример | Если не задать |
|-----|--------|----------------|
| `FTP_SERVER_DIR` | `www/an-sodeystvie.ru/` или `public_html/` | `./` (корень FTP-аккаунта) — часто неверно; **лучше задать явно** |
| `FTP_PROTOCOL` | `ftps` | `ftp` |
| `FTP_PORT` | `21` или порт из панели | `21` |

У `FTP_SERVER_DIR` и `local-dir` в экшене должен быть **завершающий слэш** там, где это требует хостинг (см. документацию [FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action)).

## 4. Поведение деплоя

- Workflow: `.github/workflows/deploy-public.yml`.
- Перед выгрузкой выполняется `npm ci` и `npm run build:css`.
- На FTP уходит **содержимое** `./public/` (не весь монорепозиторий).
- В списке исключений указаны `**/.env` и `**/.env.*`, чтобы синхронизация **не трогала** секреты на сервере (файл `public/.env` остаётся только на хостинге).

Ручной прогон: **Actions → Deploy public (FTP) → Run workflow**. Опция **dry_run** — только лог, без записи на FTP.

## 5. Если доступ только по SSH (без FTP)

Тогда этот workflow не подойдёт; используйте, например, [web-deploy](https://github.com/SamKirkland/web-deploy) (rsync по SSH) или свой runner с `rsync`/`scp` и ключом в Secrets.
