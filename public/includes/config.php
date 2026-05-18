<?php

declare(strict_types=1);

require_once __DIR__ . '/env-bootstrap.php';

/** Телефон (подставьте реальный номер заказчика) */
const SITE_PHONE_TEL = '+7(3952) 60-38-08';
const SITE_PHONE_DISPLAY = '+7 (3952) 60-38-08';

/** Email и адрес — заглушки до передачи данных */
const SITE_EMAIL = 'info@an-sodeystvie.ru';
const SITE_ADDRESS = 'г. Иркутск, ул. Карла Либкнехта 107а, офис 17';

/** Реквизиты — уточняются у заказчика */
const SITE_LEGAL_NAME = 'ООО «Содействие»';
const SITE_LEGAL_INN = '0000000000';
const SITE_LEGAL_OGRN = '0000000000000';

/**
 * Интеграции (локально/прод):
 * - CRM API (NestJS, monorepo an-realty-crm /apps/api): по умолчанию http://localhost:3000
 */
function site_env(string $key, ?string $default = null): string
{
    $v = getenv($key);
    if ($v === false || $v === null || $v === '') {
        return $default ?? '';
    }
    return (string) $v;
}

function site_is_production_vitrina_host(): bool
{
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
    $name = isset($_SERVER['SERVER_NAME']) ? strtolower((string) $_SERVER['SERVER_NAME']) : '';

    return str_contains($host, 'an-sodeystvie.ru') || str_contains($name, 'an-sodeystvie.ru');
}

function site_crm_default_api_base(): string
{
    if (site_is_production_vitrina_host()) {
        return 'https://an-realty-crm.ru';
    }

    return 'http://localhost:3000';
}

function site_crm_api_base(): string
{
    $fromEnv = site_env('CRM_API_BASE', '');
    $base = $fromEnv !== '' ? $fromEnv : site_crm_default_api_base();

    return rtrim($base, '/');
}

/**
 * База для ссылок, которые будет открывать браузер (картинки, публичные страницы API).
 */
function site_crm_public_base(): string
{
    $fromEnv = site_env('CRM_PUBLIC_BASE', '');
    $base = $fromEnv !== '' ? $fromEnv : site_crm_default_api_base();

    return rtrim($base, '/');
}

function site_running_in_docker(): bool
{
    return file_exists('/.dockerenv') || (site_env('DOCKER', '') !== '');
}

/**
 * Если витрина в Docker, а API на хосте, то localhost внутри контейнера не сработает.
 * В таком случае безопасно пробуем host.docker.internal (Docker Desktop).
 */
function site_crm_api_base_resolved(): string
{
    $base = site_crm_api_base();
    if (!site_running_in_docker()) {
        return $base;
    }

    $host = (string) parse_url($base, PHP_URL_HOST);
    if ($host === 'localhost' || $host === '127.0.0.1') {
        return preg_replace('/^(https?:\\/\\/)(localhost|127\\.0\\.0\\.1)(:\\d+)?/i', '$1host.docker.internal$3', $base) ?: $base;
    }

    return $base;
}

/** Путь публичного каталога на Nest (через nginx на CRM — только под /api/). */
function site_crm_listings_path(): string
{
    $path = site_env('CRM_LISTINGS_PATH', '/api/public/listings');
    $path = '/' . trim($path, '/');

    return $path;
}

function site_crm_listings_url(string $id = ''): string
{
    $base = site_crm_api_base_resolved() . site_crm_listings_path();
    if ($id === '') {
        return $base;
    }

    return $base . '/' . rawurlencode($id);
}

/** Подсказка, если на боевом домене не задан public/.env */
function site_crm_env_setup_hint(): ?string
{
    $base = site_crm_api_base();
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
    if ($host === '' || str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
        return null;
    }
    if (!str_contains($base, 'localhost') && !str_contains($base, '127.0.0.1')) {
        return null;
    }

    return 'Создайте на хостинге файл .env рядом с index.php (скопируйте из env.example.txt): CRM_API_BASE=https://an-realty-crm.ru и CRM_PUBLIC_BASE=https://an-realty-crm.ru';
}

function site_crm_public_url(string $pathOrUrl): string
{
    $u = trim($pathOrUrl);
    if ($u === '') return '';
    if (preg_match('#^https?://#i', $u)) return $u;
    if ($u[0] === '/') return site_crm_public_base() . $u;
    return site_crm_public_base() . '/' . ltrim($u, '/');
}

function site_crm_is_yandex_published_viewer_url(string $url): bool
{
    $u = trim($url);
    if ($u === '' || !preg_match('#^https?://#i', $u)) {
        return false;
    }
    $p = parse_url($u);
    if (!is_array($p) || empty($p['host']) || empty($p['path'])) {
        return false;
    }
    $host = strtolower((string) $p['host']);
    $path = (string) $p['path'];
    if (!preg_match('#^/i/#', $path)) {
        return false;
    }

    return $host === 'yadi.sk'
        || $host === 'disk.yandex.ru'
        || str_ends_with($host, '.disk.yandex.ru');
}

/**
 * Конвертация https://yadi.sk/i/… → прямой URL картинки (downloader.disk.yandex.ru).
 * Страница yadi.sk не годится для background-image / <img>.
 *
 * @return non-empty-string|null
 */
function site_crm_yandex_public_share_to_direct_url(string $publicViewerUrl, int $timeoutSeconds = 12): ?string
{
    $trimmed = trim($publicViewerUrl);
    if ($trimmed === '' || !site_crm_is_yandex_published_viewer_url($trimmed)) {
        return null;
    }

    $apiUrl = 'https://cloud-api.yandex.net/v1/disk/public/resources?' . http_build_query([
        'public_key' => $trimmed,
    ]);
    $data = site_http_get_json($apiUrl, $timeoutSeconds);
    if (isset($data['_error'])) {
        return null;
    }

    $sizes = isset($data['sizes']) && is_array($data['sizes']) ? $data['sizes'] : [];
    $preferNames = ['M', 'L', 'S', 'DEFAULT', 'ORIGINAL', 'XL'];
    foreach ($preferNames as $wantName) {
        foreach ($sizes as $sz) {
            if (!is_array($sz)) {
                continue;
            }
            $name = isset($sz['name']) ? (string) $sz['name'] : '';
            $sur = isset($sz['url']) ? trim((string) $sz['url']) : '';
            if ($name === $wantName && $sur !== '' && preg_match('#^https?://#i', $sur)) {
                return $sur;
            }
        }
    }

    if (!empty($data['preview']) && is_string($data['preview'])) {
        $pv = trim($data['preview']);
        if ($pv !== '' && preg_match('#^https?://#i', $pv)) {
            return $pv;
        }
    }

    foreach ($sizes as $sz) {
        if (!is_array($sz)) {
            continue;
        }
        $sur = isset($sz['url']) ? trim((string) $sz['url']) : '';
        if ($sur !== '' && preg_match('#^https?://#i', $sur)) {
            return $sur;
        }
    }

    if (!empty($data['file']) && is_string($data['file'])) {
        $f = trim($data['file']);
        if ($f !== '' && preg_match('#^https?://#i', $f)) {
            return $f;
        }
    }

    return null;
}

/**
 * Прямой URL картинки через Nest (если с REG.RU недоступен cloud-api.yandex.net).
 *
 * @return non-empty-string|null
 */
function site_crm_resolve_photo_via_crm_api(string $viewerOrStoredUrl): ?string
{
    $u = trim($viewerOrStoredUrl);
    if ($u === '') {
        return null;
    }
    $apiBase = site_crm_api_base_resolved();
    $endpoint =
        rtrim($apiBase, '/') .
        '/api/public/listings/resolve-photo-url?' .
        http_build_query(['url' => $u]);
    $data = site_http_get_json($endpoint, 20);
    if (isset($data['_error'])) {
        return null;
    }
    $resolved = isset($data['url']) ? trim((string) $data['url']) : '';
    if ($resolved !== '' && preg_match('#^https?://#i', $resolved)) {
        return $resolved;
    }

    return null;
}

/** URL из CRM → то, что браузер может загрузить как изображение. */
function site_crm_photo_src(string $urlFromApi): string
{
    $u = trim($urlFromApi);
    if ($u === '') {
        return '';
    }
    // Уже прямая ссылка на файл/превью (после resolve на CRM или локальные uploads).
    if (preg_match('#^https?://#i', $u)) {
        if (site_crm_is_yandex_published_viewer_url($u)) {
            $direct = site_crm_yandex_public_share_to_direct_url($u, 15);
            if ($direct !== null && $direct !== '') {
                return $direct;
            }
            $viaCrm = site_crm_resolve_photo_via_crm_api($u);
            if ($viaCrm !== null && $viaCrm !== '') {
                return $viaCrm;
            }
            // Страница yadi.sk в <img> не отображается — не подставляем её.
            return '';
        }

        return $u;
    }

    return site_crm_public_url($u);
}

/**
 * Тег <img> для фото с Яндекс.Диска: без Referer (иначе downloader.disk.yandex.ru отдаёт 403 на an-sodeystvie.ru).
 *
 * @param non-empty-string $src
 */
function site_crm_photo_img(string $src, string $alt = '', string $class = ''): string
{
    $src = trim($src);
    if ($src === '') {
        return '';
    }
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"' : '';

    return '<img src="' . htmlspecialchars($src, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
        . ' alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
        . ' loading="lazy" decoding="async" referrerpolicy="no-referrer"'
        . $classAttr
        . '>';
}

/**
 * Простой GET JSON для интеграции витрины.
 *
 * @return array<string, mixed>
 */
function site_http_get_json(string $url, int $timeoutSeconds = 3): array
{
    $raw = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                ],
            ]);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw === false) {
                return ['_error' => 'CRM API недоступен: ' . ($err ?: 'curl error')];
            }
            if ($code >= 400) {
                return ['_error' => 'CRM API ответил ошибкой HTTP ' . $code];
            }
        }
    }

    if ($raw === null) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeoutSeconds,
                'header' => "Accept: application/json\r\n",
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return ['_error' => 'CRM API недоступен (file_get_contents)'];
        }
    }

    $rawStr = (string) $raw;
    if ($rawStr !== '' && preg_match('/^\s*</', $rawStr)) {
        return ['_error' => 'CRM API вернул HTML вместо JSON — проверьте CRM_API_BASE и путь ' . site_crm_listings_path() . ' (на VPS нужен /api/public/listings)'];
    }

    try {
        $decoded = json_decode($rawStr, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return ['_error' => 'CRM API вернул не-JSON'];
    }

    return is_array($decoded) ? $decoded : ['_error' => 'CRM API вернул неожиданный формат'];
}

/**
 * Пункты основного меню (шапка и подвал).
 * У пункта может быть вложенный массив `children` — выпадающее подменю (например «О компании» → «Отзывы»).
 *
 * @return array<string, array{href: string, label: string, children?: array<string, array{href: string, label: string}>}>
 */
function site_nav_items(): array
{
    return [
        'home' => ['href' => '/', 'label' => 'Главная'],
        'catalog' => ['href' => '/catalog/', 'label' => 'Каталог'],
        'services' => ['href' => '/services/', 'label' => 'Услуги'],
        'mortgage' => [
            'href' => '/mortgage/',
            'label' => 'Ипотека',
            'children' => [
                'mortgage_2026' => ['href' => '/mortgage/#2026', 'label' => 'Ипотека 2026'],
                'mortgage_family' => ['href' => '/mortgage/#family', 'label' => '🔥 Семейная ипотека'],
                'mortgage_rural' => ['href' => '/mortgage/#rural', 'label' => 'Сельская ипотека'],
                'mortgage_resale' => ['href' => '/mortgage/#resale', 'label' => 'Ипотека на вторичное жилье'],
                'mortgage_newbuild' => ['href' => '/mortgage/#newbuild', 'label' => 'Ипотека на новостройки'],
                'mortgage_house' => ['href' => '/mortgage/#house', 'label' => 'Ипотека на дом'],
                'mortgage_build' => ['href' => '/mortgage/#build', 'label' => 'Ипотека на строительство'],
                'mortgage_military' => ['href' => '/mortgage/#military', 'label' => 'Военная ипотека'],
                'mortgage_it' => ['href' => '/mortgage/#it', 'label' => 'IT-ипотека'],
                'mortgage_maternity' => ['href' => '/mortgage/#maternity', 'label' => 'Материнский капитал'],
                'mortgage_calc' => ['href' => '/mortgage/#calculator', 'label' => 'Калькулятор'],
                'mortgage_apply' => ['href' => '/mortgage/#apply', 'label' => 'Заявка'],
            ],
        ],
        'about' => [
            'href' => '/about/',
            'label' => 'О компании',
            'children' => [
                'reviews' => ['href' => '/reviews/', 'label' => 'Отзывы'],
            ],
        ],
        'contacts' => [
            'href' => '/contacts/',
            'label' => 'Контакты',
            'children' => [
                'vacancies' => ['href' => '/vacancies/', 'label' => 'Вакансии'],
            ],
        ],
    ];
}

function site_nav_item_has_children(array $item): bool
{
    return !empty($item['children']) && is_array($item['children']);
}

/** Подсветка пункта: совпадение slug или любого дочернего (например reviews при открытой странице отзывов). */
function site_nav_item_is_current(array $item, string $slug, string $currentNav): bool
{
    if ($currentNav === $slug) {
        return true;
    }
    if (!site_nav_item_has_children($item)) {
        return false;
    }
    foreach ($item['children'] as $childSlug => $_child) {
        if ($currentNav === $childSlug) {
            return true;
        }
    }

    return false;
}
