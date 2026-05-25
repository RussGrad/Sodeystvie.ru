<?php

declare(strict_types=1);

/**
 * WebP: статика (сборка + <picture>) и прокси /api/image.php для фото CRM/Яндекс.Диска.
 */

function site_image_webp_enabled(): bool
{
    $v = strtolower(trim(site_env('SITE_IMAGE_WEBP', 'true')));

    return !in_array($v, ['0', 'false', 'no', 'off'], true);
}

function site_image_webp_quality(): int
{
    $q = (int) site_env('SITE_IMAGE_WEBP_QUALITY', '82');

    return max(50, min(95, $q));
}

function site_image_webp_max_width(): int
{
    $w = (int) site_env('SITE_IMAGE_WEBP_MAX_WIDTH', '1920');

    return max(320, min(2560, $w));
}

function site_image_can_convert(): bool
{
    return function_exists('imagewebp') && function_exists('imagecreatefromstring');
}

function site_image_cache_dir(): string
{
    return dirname(__DIR__) . '/cache/img';
}

/**
 * @return list<string>
 */
function site_image_remote_host_allowlist(): array
{
    return [
        'downloader.disk.yandex.ru',
        'preview.disk.yandex.ru',
        'disk.yandex.ru',
        'yadi.sk',
    ];
}

function site_image_url_is_allowed_remote(string $url): bool
{
    $p = parse_url($url);
    if (!is_array($p) || empty($p['host']) || empty($p['scheme'])) {
        return false;
    }
    if (!in_array(strtolower((string) $p['scheme']), ['http', 'https'], true)) {
        return false;
    }
    $host = strtolower((string) $p['host']);
    foreach (site_image_remote_host_allowlist() as $allowed) {
        if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
            return true;
        }
    }
    $crmHost = parse_url(site_crm_api_base_resolved(), PHP_URL_HOST);
    if (is_string($crmHost) && $crmHost !== '' && $host === strtolower($crmHost)) {
        return true;
    }

    return false;
}

function site_image_public_path_is_allowed(string $webPath): bool
{
    if ($webPath === '' || $webPath[0] !== '/') {
        return false;
    }
    if (str_contains($webPath, '..')) {
        return false;
    }

    return str_starts_with($webPath, '/assets/');
}

/**
 * URL прокси WebP (только для внешних/резолвленных фото).
 */
function site_image_proxy_url(string $sourceUrl, int $maxWidth = 0): string
{
    $sourceUrl = trim($sourceUrl);
    if ($sourceUrl === '') {
        return '';
    }
    if (!site_image_webp_enabled() || !site_image_can_convert()) {
        return $sourceUrl;
    }
    if (str_starts_with($sourceUrl, '/')) {
        return site_image_public_web_path($sourceUrl, $maxWidth);
    }
    if (!site_image_url_is_allowed_remote($sourceUrl)) {
        return $sourceUrl;
    }

    $payload = ['u' => $sourceUrl];
    if ($maxWidth > 0) {
        $payload['w'] = $maxWidth;
    }

    return '/api/image.php?' . http_build_query($payload);
}

/**
 * Локальный файл из /assets/ → WebP-версия или прокси по path.
 */
function site_image_public_web_path(string $webPath, int $maxWidth = 0): string
{
    if (!site_image_public_path_is_allowed($webPath)) {
        return $webPath;
    }
    $publicRoot = dirname(__DIR__);
    $abs = $publicRoot . $webPath;
    if (!is_file($abs)) {
        return $webPath;
    }
    $webpWeb = preg_replace('/\.(jpe?g|png)$/i', '.webp', $webPath) ?? $webPath;
    if (is_file($publicRoot . $webpWeb)) {
        return $webpWeb;
    }
    if (!site_image_webp_enabled() || !site_image_can_convert()) {
        return $webPath;
    }

    $q = ['path' => $webPath];
    if ($maxWidth > 0) {
        $q['w'] = $maxWidth;
    }

    return '/api/image.php?' . http_build_query($q);
}

/**
 * Для CRM: исходный резолв → отображение (WebP-прокси при возможности).
 */
function site_crm_photo_display_src(string $resolvedUrl, int $maxWidth = 1200): string
{
    $resolvedUrl = trim($resolvedUrl);
    if ($resolvedUrl === '') {
        return '';
    }

    return site_image_proxy_url($resolvedUrl, $maxWidth);
}

/**
 * @param list<string> $urls
 * @return list<string>
 */
function site_crm_photo_display_src_list(array $urls, int $maxWidth = 1200): array
{
    $out = [];
    foreach ($urls as $u) {
        if (!is_string($u)) {
            continue;
        }
        $d = site_crm_photo_display_src($u, $maxWidth);
        if ($d !== '') {
            $out[] = $d;
        }
    }

    return $out;
}

/**
 * <picture> для статики в /assets/ (если есть .webp рядом с jpg/png).
 */
function site_render_static_picture(
    string $webPath,
    string $alt = '',
    string $class = '',
    string $extraAttrs = ''
): string {
    $webPath = trim($webPath);
    if ($webPath === '') {
        return '';
    }
    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"' : '';
    $extra = trim($extraAttrs);
    $extraStr = $extra !== '' ? ' ' . $extra : '';

    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $webPath) ?? '';
    $publicRoot = dirname(__DIR__);
    $hasWebp = $webp !== '' && is_file($publicRoot . $webp);

    if ($hasWebp) {
        return '<picture>'
            . '<source srcset="' . htmlspecialchars($webp, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '" type="image/webp">'
            . '<img src="' . htmlspecialchars($webPath, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
            . ' alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
            . ' loading="lazy" decoding="async"'
            . $classAttr
            . $extraStr
            . '></picture>';
    }

    $display = site_image_public_web_path($webPath);

    return '<img src="' . htmlspecialchars($display, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
        . ' alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
        . ' loading="lazy" decoding="async"'
        . $classAttr
        . $extraStr
        . '>';
}
