<?php

declare(strict_types=1);

require_once __DIR__ . '/env-bootstrap.php';
require_once __DIR__ . '/site-content.php';
require_once __DIR__ . '/site-brand.php';
require_once __DIR__ . '/site-image.php';
require_once __DIR__ . '/security.php';

site_redirect_aliases_to_canonical();

/** Телефон (подставьте реальный номер заказчика) */
const SITE_PHONE_TEL = '+7(3952) 60-38-08';
const SITE_PHONE_DISPLAY = '+7 (3952) 60-38-08';

/** Email и адрес — заглушки до передачи данных */
const SITE_EMAIL = 'info@an-sodeystvie.ru';
const SITE_ADDRESS = 'г. Иркутск, ул. Карла Либкнехта 107а, офис 17';

/** Подпись в шапке */
const SITE_CITY_TAG = 'Иркутск';
const SITE_FOUNDED_YEAR = 2015;
const SITE_WORK_HOURS = 'Пн–Пт 9:00–19:00';

/**
 * ВКонтакте: SITE_VK_URL в .env (например https://vk.com/your_group).
 */
function site_vk_url(): ?string
{
    $fromContent = site_content_setting('vk_url', '');
    if ($fromContent !== '' && preg_match('#^https?://#i', $fromContent)) {
        return $fromContent;
    }
    $fromEnv = trim(site_env('SITE_VK_URL', ''));
    if ($fromEnv !== '' && preg_match('#^https?://#i', $fromEnv)) {
        return $fromEnv;
    }

    return null;
}

/**
 * WhatsApp: SITE_WHATSAPP_URL в .env или wa.me по номеру SITE_PHONE_TEL.
 */
function site_whatsapp_url(): ?string
{
    $fromEnv = trim(site_env('SITE_WHATSAPP_URL', ''));
    if ($fromEnv !== '') {
        return $fromEnv;
    }
    $digits = preg_replace('/\D+/', '', site_phone_tel()) ?? '';
    if (strlen($digits) >= 11) {
        return 'https://wa.me/' . ltrim($digits, '+');
    }

    return null;
}

/**
 * Telegram: SITE_TELEGRAM_URL в .env или t.me/+номер по SITE_PHONE_TEL.
 */
function site_telegram_url(): ?string
{
    $fromContent = site_content_setting('telegram_url', '');
    if ($fromContent !== '' && preg_match('#^https?://#i', $fromContent)) {
        return $fromContent;
    }
    $fromEnv = trim(site_env('SITE_TELEGRAM_URL', ''));
    if ($fromEnv !== '') {
        return $fromEnv;
    }
    $digits = preg_replace('/\D+/', '', site_phone_tel()) ?? '';
    if (strlen($digits) >= 11) {
        return 'https://t.me/+' . ltrim($digits, '+');
    }

    return null;
}

/**
 * Мессенджер MAX (max.ru): SITE_MAX_URL в .env.
 */
function site_max_url(): string
{
    $fromContent = site_content_setting('max_url', '');
    if ($fromContent !== '' && preg_match('#^https?://#i', $fromContent)) {
        return $fromContent;
    }
    $fromEnv = trim(site_env('SITE_MAX_URL', ''));
    if ($fromEnv !== '' && preg_match('#^https?://#i', $fromEnv)) {
        return $fromEnv;
    }

    return 'https://max.ru/';
}

/** API-ключ JavaScript API Яндекс.Карт (https://developer.tech.yandex.ru/) */
function site_yandex_maps_api_key(): string
{
    return trim(site_env('YANDEX_MAPS_API_KEY', ''));
}

/** Центр карты по умолчанию (Иркутск) */
function site_map_default_center(): array
{
    $lat = (float) site_env('SITE_MAP_CENTER_LAT', '52.2896');
    $lng = (float) site_env('SITE_MAP_CENTER_LNG', '104.2806');
    $zoom = (int) site_env('SITE_MAP_DEFAULT_ZOOM', '11');

    return ['lat' => $lat, 'lng' => $lng, 'zoom' => max(4, min(17, $zoom))];
}

/**
 * Офис на странице «Контакты» (ул. Карла Либкнехта 107а).
 * Координаты можно переопределить в .env: SITE_OFFICE_LAT, SITE_OFFICE_LNG, SITE_OFFICE_ZOOM.
 */
function site_office_location(): array
{
    $lat = (float) site_env('SITE_OFFICE_LAT', '52.2796');
    $lng = (float) site_env('SITE_OFFICE_LNG', '104.312');
    $zoom = (int) site_env('SITE_OFFICE_ZOOM', '16');

    return [
        'lat' => $lat,
        'lng' => $lng,
        'zoom' => max(10, min(18, $zoom)),
        'address' => SITE_ADDRESS,
        'title' => SITE_BRAND_FULL,
    ];
}

/** Текст под заголовком «Мессенджеры» на странице контактов. */
function site_contacts_messengers_lead(): string
{
    $custom = trim(site_env('SITE_CONTACTS_MESSENGERS_LEAD', ''));
    if ($custom !== '') {
        return $custom;
    }

    return 'Свяжемся в течение 15 минут.';
}

/** Ключ reCAPTCHA v2 для формы контактов (публичный). */
function site_recaptcha_site_key(): string
{
    return trim(site_env('RECAPTCHA_SITE_KEY', ''));
}

/** Ссылка «Открыть в Яндекс.Картах» (без JS API). */
function site_yandex_maps_external_url(?array $office = null): string
{
    $office ??= site_office_location();

    return 'https://yandex.ru/maps/?pt='
        . $office['lng']
        . ','
        . $office['lat']
        . '&z='
        . (int) ($office['zoom'] ?? 16)
        . '&text='
        . rawurlencode((string) ($office['address'] ?? SITE_ADDRESS));
}

/** Реквизиты — имя по умолчанию; ИНН/ОГРН из public/.env (SITE_LEGAL_INN, SITE_LEGAL_OGRN) */
const SITE_LEGAL_NAME = 'ООО «Содействие»';

function site_legal_inn(): string
{
    $fromContent = site_content_setting('legal_inn', '');
    if ($fromContent !== '') {
        return $fromContent;
    }

    return trim(site_env('SITE_LEGAL_INN', ''));
}

function site_legal_ogrn(): string
{
    $fromContent = site_content_setting('legal_ogrn', '');
    if ($fromContent !== '') {
        return $fromContent;
    }

    return trim(site_env('SITE_LEGAL_OGRN', ''));
}

function site_legal_inn_display(): string
{
    $inn = site_legal_inn();

    return $inn !== '' ? $inn : '—';
}

function site_legal_ogrn_display(): string
{
    $ogrn = site_legal_ogrn();

    return $ogrn !== '' ? $ogrn : '—';
}

/**
 * Интеграции (локально/прод):
 * - CRM API (NestJS, monorepo an-realty-crm /apps/api): по умолчанию http://localhost:3000
 */
function site_env(string $key, ?string $default = null): string
{
    $v = site_read_env_var($key);
    if ($v === '') {
        return $default ?? '';
    }

    return $v;
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

/** Путь приёма заявок с витрины на Nest (POST, ключ PUBLIC_SITE_API_KEY). */
function site_crm_leads_path(): string
{
    $path = site_env('CRM_LEADS_PATH', '/api/public/leads');
    $path = '/' . trim($path, '/');

    return $path;
}

function site_crm_leads_url(): string
{
    return site_crm_api_base_resolved() . site_crm_leads_path();
}

/** Путь онлайн-чата витрины на Nest (GET/POST, ключ PUBLIC_SITE_API_KEY). */
function site_crm_site_chat_path(): string
{
    $path = site_env('CRM_SITE_CHAT_PATH', '/api/public/site-chat');
    $path = '/' . trim($path, '/');

    return $path;
}

function site_crm_site_chat_url(): string
{
    return site_crm_api_base_resolved() . site_crm_site_chat_path();
}

/** Путь команды агентства для витрины (GET, ключ PUBLIC_SITE_API_KEY). */
function site_crm_team_path(): string
{
    $path = site_env('CRM_TEAM_PATH', '/api/public/team');
    $path = '/' . trim($path, '/');

    return $path;
}

function site_crm_team_url(): string
{
    return site_crm_api_base_resolved() . site_crm_team_path();
}

function site_public_site_api_key(): string
{
    return site_env('PUBLIC_SITE_API_KEY', '');
}

function site_crm_listings_url(string $id = ''): string
{
    $base = site_crm_api_base_resolved() . site_crm_listings_path();
    if ($id === '') {
        return $base;
    }
    if (!site_validate_crm_object_id($id)) {
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
    if (!preg_match('#^/(i|d)/#', $path)) {
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
    $data = site_http_get_json($endpoint, 6);
    if (isset($data['_error'])) {
        return null;
    }
    $resolved = isset($data['url']) ? trim((string) $data['url']) : '';
    if ($resolved !== '' && preg_match('#^https?://#i', $resolved)) {
        return $resolved;
    }

    return null;
}

/**
 * Публичный прокси фото на CRM (yadisk: и /api/disk/preview без JWT).
 *
 * @return non-empty-string
 */
function site_crm_photo_proxy_url(string $storedUrl): string
{
    return rtrim(site_crm_public_base(), '/') .
        '/api/public/listings/photo?' .
        http_build_query(['url' => $storedUrl]);
}

/** URL из CRM → то, что браузер может загрузить как изображение (с кэшем в рамках запроса). */
function site_crm_photo_src(string $urlFromApi): string
{
    static $memo = [];
    $u = trim($urlFromApi);
    if ($u === '') {
        return '';
    }
    if (isset($memo[$u])) {
        return $memo[$u];
    }

    $out = '';
    if (preg_match('#^https?://#i', $u)) {
        if (
            str_contains($u, 'downloader.disk.yandex.ru')
            || str_contains($u, 'preview.disk.yandex.ru')
        ) {
            $out = $u;
        } elseif (site_crm_is_yandex_published_viewer_url($u)) {
            $out = site_crm_photo_proxy_url($u);
        } else {
            $out = $u;
        }
    } elseif (str_starts_with($u, 'yadisk:') || str_contains($u, '/api/disk/preview')) {
        $out = site_crm_photo_proxy_url($u);
    } elseif (str_starts_with($u, '/uploads/')) {
        $out = site_crm_public_url($u);
    } else {
        $out = site_crm_public_url($u);
    }

    $memo[$u] = $out;

    return $out;
}

/**
 * Тег <img> для фото с Яндекс.Диска: без Referer (иначе downloader.disk.yandex.ru отдаёт 403 на an-sodeystvie.ru).
 *
 * @param non-empty-string $src
 */
function site_crm_photo_img(
    string $src,
    string $alt = '',
    string $class = '',
    string $extraAttrs = '',
    int|string $widthOrPreset = 'card'
): string {
    $src = trim($src);
    if ($src === '') {
        return '';
    }
    if (str_starts_with($src, '/assets/')) {
        return site_render_static_picture($src, $alt, $class, $extraAttrs);
    }
    $preset = is_string($widthOrPreset) ? $widthOrPreset : 'card';
    $display = str_contains($src, '/api/image.php')
        ? $src
        : site_crm_photo_display_src($src, $widthOrPreset);
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"' : '';
    $extra = trim($extraAttrs);
    $sizes = site_image_sizes_attr($preset);
    $loading = str_contains($extra, 'fetchpriority="high"') ? '' : ' loading="lazy"';

    return '<img src="' . htmlspecialchars($display, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
        . ' alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
        . ' decoding="async" referrerpolicy="no-referrer"'
        . ' sizes="' . htmlspecialchars($sizes, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
        . $loading
        . $classAttr
        . ($extra !== '' ? ' ' . $extra : '')
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
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
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

function site_crm_disk_cache_dir(): string
{
    $dir = dirname(__DIR__) . '/var/crm-cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir;
}

function site_crm_cache_file_path(string $key): string
{
    return site_crm_disk_cache_dir() . '/' . hash('sha256', $key) . '.json';
}

/**
 * @return array<string, mixed>|null
 */
function site_crm_cache_read_json(string $key, int $ttlSeconds = 900): ?array
{
    $path = site_crm_cache_file_path($key);
    if (!is_file($path)) {
        return null;
    }
    if (filemtime($path) + $ttlSeconds < time()) {
        @unlink($path);

        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

/**
 * @param array<string, mixed> $data
 */
function site_crm_cache_write_json(string $key, array $data): void
{
    $path = site_crm_cache_file_path($key);
    @file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

/**
 * GET JSON с файловым кэшем (ускоряет повторные открытия каталога).
 *
 * @return array<string, mixed>
 */
function site_http_get_json_cached(string $url, int $timeoutSeconds = 8, int $ttlSeconds = 900): array
{
    $cacheKey = 'crm_json:' . $url;
    $cached = site_crm_cache_read_json($cacheKey, $ttlSeconds);
    if ($cached !== null) {
        return $cached;
    }
    $fresh = site_http_get_json($url, $timeoutSeconds);
    if (!isset($fresh['_error'])) {
        site_crm_cache_write_json($cacheKey, $fresh);
    }

    return $fresh;
}

/**
 * POST JSON на CRM (заявки с витрины).
 *
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
function site_http_post_json(string $url, array $payload, int $timeoutSeconds = 8): array
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    $apiKey = site_public_site_api_key();
    if ($apiKey !== '') {
        $headers[] = 'X-Api-Key: ' . $apiKey;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw === false) {
                return ['_error' => 'CRM API недоступен: ' . ($err ?: 'curl error')];
            }
            if ($code >= 400) {
                $msg = 'CRM API ответил ошибкой HTTP ' . $code;
                if (is_string($raw) && $raw !== '') {
                    try {
                        $parsed = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($parsed) && isset($parsed['message'])) {
                            $m = $parsed['message'];
                            if (is_string($m)) {
                                $msg = $m;
                            } elseif (is_array($m)) {
                                $msg = implode(' ', array_map('strval', $m));
                            }
                        }
                    } catch (Throwable $e) {
                        /* ignore */
                    }
                }

                return ['_error' => $msg, '_http' => $code];
            }

            try {
                $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                return ['_error' => 'CRM API вернул не-JSON'];
            }

            return is_array($decoded) ? $decoded : ['_error' => 'CRM API вернул неожиданный формат'];
        }
    }

    return ['_error' => 'На сервере не доступен curl для отправки заявки'];
}

/** GET к публичному API CRM с X-Api-Key (онлайн-чат и т.п.). */
function site_http_public_api_get(string $url, int $timeoutSeconds = 8): array
{
    $headers = ['Accept: application/json'];
    $apiKey = site_public_site_api_key();
    if ($apiKey !== '') {
        $headers[] = 'X-Api-Key: ' . $apiKey;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw === false) {
                return ['_error' => 'CRM API недоступен: ' . ($err ?: 'curl error')];
            }
            if ($code >= 400) {
                $msg = 'CRM API ответил ошибкой HTTP ' . $code;
                if ($code === 404) {
                    $msg = 'На CRM не развёрнут API онлайн-чата (HTTP 404). Обновите an-realty-api на сервере: git pull, prisma migrate deploy, build, restart.';
                }

                return ['_error' => $msg, '_http' => $code];
            }
            try {
                $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                return ['_error' => 'CRM API вернул не-JSON'];
            }

            return is_array($decoded) ? $decoded : ['_error' => 'CRM API вернул неожиданный формат'];
        }
    }

    return ['_error' => 'На сервере не доступен curl'];
}

/**
 * Пункты основного меню (шапка и подвал).
 * У пункта может быть вложенный массив `children` — выпадающее подменю (например «О компании» → «Отзывы»).
 *
 * @return array<string, array{href: string, label: string, children?: array<string, array{href: string, label: string}>}>
 */
function site_nav_items(): array
{
    require_once __DIR__ . '/services-catalog.php';

    return [
        'home' => ['href' => '/', 'label' => 'Главная'],
        'catalog' => ['href' => '/catalog/', 'label' => 'Каталог'],
        'services' => [
            'href' => '/services/',
            'label' => 'Услуги',
            'children' => sodeystvie_services_nav_children(),
        ],
        'mortgage' => ['href' => '/mortgage/', 'label' => 'ИПОТЕКА'],
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
