#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ ! -d public ]]; then
  echo "Ожидается каталог public/ в $ROOT" >&2
  exit 1
fi

if command -v npm >/dev/null 2>&1; then
  if [[ -f package-lock.json ]]; then
    npm ci
  else
    npm install
  fi
  npm run build:css
else
  echo "Предупреждение: npm не найден, пропускаю сборку SCSS (используется текущий public/css/main.css)." >&2
fi

STAMP="$(date +%Y%m%d-%H%M)"
OUT_DIR="$ROOT/build/an-sodeystvie-production-$STAMP"
rm -rf "$OUT_DIR"
mkdir -p "$OUT_DIR"

# Только витрина: содержимое public/ — это и есть document root на REG.RU
cp -R "$ROOT/public/." "$OUT_DIR/"

find "$OUT_DIR" -name '.DS_Store' -delete 2>/dev/null || true

cp "$ROOT/public/.env.example" "$OUT_DIR/env.example.txt"

cat > "$OUT_DIR/README-REG-RU.txt" << 'EOF'
Содействие — витрина для REG.RU / ispmgr
========================================

1) В панели укажите корень сайта на эту папку (куда распаковали архив):
   внутри должны лежать index.php, css/, includes/, catalog/ и т.д.

2) Скопируйте env.example.txt в файл с именем .env в ЭТОЙ ЖЕ папке (рядом с index.php).
   Заполните CRM_API_BASE и CRM_PUBLIC_BASE — HTTPS-URL вашего Nest API (an-realty-crm).
   Пример:
     CRM_API_BASE="https://api.ваш-домен.ru"
     CRM_PUBLIC_BASE="https://api.ваш-домен.ru"

3) PHP: версия 8.1+ желательно 8.3. Нужны curl или allow_url_fopen для запросов к API.

4) Не заливайте сюда node_modules, src/, prisma/ — они для разработки, на shared не нужны.

Каталог: GET {CRM_API_BASE}{CRM_LISTINGS_PATH} (по умолчанию /api/public/listings)
EOF

ZIP_NAME="an-sodeystvie-production-$STAMP.zip"
(cd "$ROOT/build" && rm -f "$ZIP_NAME" && zip -rq "$ZIP_NAME" "$(basename "$OUT_DIR")")

STABLE_ZIP="$ROOT/build/an-sodeystvie-public-hosting.zip"
cp -f "$ROOT/build/$ZIP_NAME" "$STABLE_ZIP"

mkdir -p "$ROOT/releases"
cp -f "$STABLE_ZIP" "$ROOT/releases/an-sodeystvie-public-hosting.zip"

echo "Готово:"
echo "  Папка: $OUT_DIR"
echo "  Архив: $ROOT/build/$ZIP_NAME"
echo "  Удобная копия (всегда одно имя): $STABLE_ZIP"
echo "  Для GitHub (releases/): $ROOT/releases/an-sodeystvie-public-hosting.zip"
