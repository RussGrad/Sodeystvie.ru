<?php

declare(strict_types=1);

/**
 * Бренд «Содействие»: слоганы, SEO, канонический домен и редирект с алиасов.
 */

const SITE_BRAND_NAME = 'Содействие';
const SITE_BRAND_FULL = 'АН «Содействие»';

function site_brand_name(): string
{
    return SITE_BRAND_NAME;
}

function site_brand_full(): string
{
    return SITE_BRAND_FULL;
}

function site_slogan_short(): string
{
    return trim(site_env('SITE_SLOGAN_SHORT', 'Сделки под ключ'));
}

function site_slogan_hero(): string
{
    return trim(site_env(
        'SITE_SLOGAN_HERO',
        'Полное юридическое сопровождение — от подбора объекта до получения ключей'
    ));
}

function site_hero_headline(): string
{
    return trim(site_env(
        'SITE_HERO_HEADLINE',
        'Безопасная покупка и продажа недвижимости в Иркутске'
    ));
}

/** Подпись у логотипа в шапке: «в Иркутске с 2015 года». */
function site_header_tagline_geo(): string
{
    return 'в Иркутске с ' . SITE_FOUNDED_YEAR . ' года';
}

function site_header_tagline(): string
{
    return site_header_tagline_geo() . ' • ' . site_slogan_short();
}

/**
 * Заголовок вкладки: «Раздел — АН «Содействие» — недвижимость в Иркутске».
 */
function site_format_page_title(string $section = ''): string
{
    $base = SITE_BRAND_FULL . ' — недвижимость в ' . SITE_CITY_TAG;

    return $section !== '' ? $section . ' — ' . $base : $base;
}

function site_default_meta_description(): string
{
    return SITE_BRAND_FULL . ' в ' . SITE_CITY_TAG . ': покупка, продажа и аренда недвижимости. '
        . site_slogan_hero();
}

function site_canonical_host(): string
{
    $host = strtolower(trim(site_env('SITE_CANONICAL_HOST', 'an-sodeystvie.ru')));

    return $host !== '' ? $host : 'an-sodeystvie.ru';
}

/**
 * Список хостов для 301 на канонический (через запятую в SITE_REDIRECT_HOSTS).
 *
 * @return list<string>
 */
function site_redirect_host_aliases(): array
{
    $raw = site_env('SITE_REDIRECT_HOSTS', 'www.an-sodeystvie.ru');
    $parts = preg_split('/\s*,\s*/', $raw) ?: [];

    $hosts = [];
    foreach ($parts as $part) {
        $h = strtolower(trim($part));
        if ($h === '') {
            continue;
        }
        $h = (string) preg_replace('/:\d+$/', '', $h);
        if ($h !== site_canonical_host() && $h !== 'www.' . site_canonical_host()) {
            $hosts[] = $h;
        }
    }

    return array_values(array_unique($hosts));
}

function site_redirect_aliases_to_canonical(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $canonical = site_canonical_host();
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = (string) preg_replace('/:\d+$/', '', $host);

    if ($host === '') {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $scheme = $isHttps ? 'https' : 'http';
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

    $needsRedirect = false;

    if ($host === 'www.' . $canonical) {
        $needsRedirect = true;
    } elseif (in_array($host, site_redirect_host_aliases(), true)) {
        $needsRedirect = true;
    }

    if (!$needsRedirect) {
        return;
    }

    header('Location: ' . $scheme . '://' . $canonical . $uri, true, 301);
    exit;
}

function site_absolute_url(string $path = '/'): string
{
    $path = $path !== '' && $path[0] === '/' ? $path : '/' . ltrim($path, '/');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    return ($isHttps ? 'https' : 'http') . '://' . site_canonical_host() . $path;
}

/**
 * Meta description, canonical, Open Graph (базовый набор).
 */
function site_render_head_meta(?string $description = null, ?string $canonicalPath = null): void
{
    $desc = $description !== null && trim($description) !== ''
        ? trim($description)
        : site_default_meta_description();

    $path = $canonicalPath ?? (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }

    $canonicalUrl = site_absolute_url($path);
    $ogImage = site_absolute_url('/assets/brand/logo-agenciya-nedvizhimosti.png');
    $faviconPath = __DIR__ . '/../assets/brand/favicon.png';
    $faviconVersion = (string) (@filemtime($faviconPath) ?: '');

    echo '<link rel="icon" href="/favicon.ico' . ($faviconVersion !== '' ? '?v=' . rawurlencode($faviconVersion) : '') . '" sizes="any">' . "\n";
    echo '    <link rel="icon" type="image/png" href="/assets/brand/favicon.png' . ($faviconVersion !== '' ? '?v=' . rawurlencode($faviconVersion) : '') . '" sizes="48x48">' . "\n";
    echo '    <link rel="apple-touch-icon" href="/assets/brand/favicon.png' . ($faviconVersion !== '' ? '?v=' . rawurlencode($faviconVersion) : '') . '">' . "\n";
    echo '    <meta name="description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:type" content="website">' . "\n";
    echo '    <meta property="og:locale" content="ru_RU">' . "\n";
    echo '    <meta property="og:site_name" content="' . htmlspecialchars(SITE_BRAND_FULL, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:title" content="' . htmlspecialchars((string) ($GLOBALS['pageTitle'] ?? site_format_page_title()), ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:url" content="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}
