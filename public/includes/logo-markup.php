<?php

declare(strict_types=1);

/**
 * Логотип бренда: премиум PNG/WebP (как на макете) или запасной SVG.
 *
 * Перед include задайте (опционально):
 *   $logoSvgClass — класс на <img>/<svg> (по умолчанию site-header__logo-svg)
 *   $logoFallbackClass — класс для PNG-запасного варианта
 */
$logoSvgClass = $logoSvgClass ?? 'site-header__logo-svg';
$logoFallbackClass = $logoFallbackClass ?? 'site-header__logo site-header__logo--fallback';
$logoAlt = function_exists('site_brand_full') ? site_brand_full() : 'АН «Содействие»';

$premiumWebp = __DIR__ . '/../assets/brand/logo-premium.webp';
$premiumPng = __DIR__ . '/../assets/brand/logo-premium.png';
$premiumSrc = is_readable($premiumWebp)
    ? '/assets/brand/logo-premium.webp'
    : (is_readable($premiumPng) ? '/assets/brand/logo-premium.png' : '');

if ($premiumSrc !== '') {
    $imgClass = htmlspecialchars($logoSvgClass . ' site-header__logo-premium', ENT_QUOTES, 'UTF-8');
    echo '<img class="' . $imgClass . '" src="' . htmlspecialchars($premiumSrc, ENT_QUOTES, 'UTF-8') . '" width="670" height="450" alt="' . htmlspecialchars($logoAlt, ENT_QUOTES, 'UTF-8') . '" decoding="async">';
    return;
}

$logoPath = __DIR__ . '/../assets/brand/logo-text.svg';
$logoSvg = is_readable($logoPath) ? file_get_contents($logoPath) : '';

if ($logoSvg === '') {
    echo '<img class="' . htmlspecialchars($logoFallbackClass, ENT_QUOTES, 'UTF-8') . '" src="/assets/brand/logo-agenciya-nedvizhimosti.png" width="933" height="255" alt="' . htmlspecialchars($logoAlt, ENT_QUOTES, 'UTF-8') . '" decoding="async">';
    return;
}

$logoSvg = preg_replace('/<\?xml[^>]*\?>\s*/u', '', $logoSvg);
$logoSvg = preg_replace('/<svg\b/', '<svg class="' . htmlspecialchars($logoSvgClass, ENT_QUOTES, 'UTF-8') . '" width="134" height="36" role="img" aria-hidden="true"', $logoSvg, 1);
$logoSvg = preg_replace(
    '/(<svg[^>]*>)/',
    '$1<title>' . htmlspecialchars($logoAlt, ENT_QUOTES, 'UTF-8') . '</title>',
    $logoSvg,
    1
);

echo $logoSvg;
