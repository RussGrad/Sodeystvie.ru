<?php

declare(strict_types=1);

/**
 * Встроенный SVG логотипа: внешний CSS может менять .st26 (светлая тема → тёмный контраст),
 * монограмма АН и нижняя строка «АГЕНТСТВО НЕДВИЖИМОСТИ» на градиентах не используют .st26.
 *
 * Перед include задайте (опционально):
 *   $logoSvgClass — класс на <svg> (по умолчанию site-header__logo-svg)
 *   $logoFallbackClass — класс для PNG-запасного варианта
 */
$logoSvgClass = $logoSvgClass ?? 'site-header__logo-svg';
$logoFallbackClass = $logoFallbackClass ?? 'site-header__logo site-header__logo--fallback';

$logoPath = __DIR__ . '/../assets/brand/logo-text.svg';
$logoSvg = is_readable($logoPath) ? file_get_contents($logoPath) : '';

if ($logoSvg === '') {
    echo '<img class="' . htmlspecialchars($logoFallbackClass, ENT_QUOTES, 'UTF-8') . '" src="/assets/brand/logo-agenciya-nedvizhimosti.png" width="933" height="255" alt="Агентство недвижимости" decoding="async">';
    return;
}

$logoSvg = preg_replace('/<\?xml[^>]*\?>\s*/u', '', $logoSvg);
$logoSvg = preg_replace('/<svg\b/', '<svg class="' . htmlspecialchars($logoSvgClass, ENT_QUOTES, 'UTF-8') . '" role="img" aria-hidden="true"', $logoSvg, 1);
$logoSvg = preg_replace(
    '/(<svg[^>]*>)/',
    '$1<title>' . htmlspecialchars(function_exists('site_brand_full') ? site_brand_full() : 'АН «Содействие»', ENT_QUOTES, 'UTF-8') . '</title>',
    $logoSvg,
    1
);

echo $logoSvg;
