<?php

declare(strict_types=1);

/**
 * Логотип бренда: монограмма АНС + подпись справа или запасной SVG/PNG.
 *
 * Перед include задайте (опционально):
 *   $logoSvgClass — класс на <img>/<svg> (по умолчанию site-header__logo-svg)
 *   $logoFallbackClass — класс для PNG-запасного варианта
 *   $logoLockupClass — класс обёртки lockup (по умолчанию site-brand-lockup)
 *   $logoShowSubtitle — показывать подпись «АГЕНТСТВО НЕДВИЖИМОСТИ» (по умолчанию false)
 */
$logoSvgClass = $logoSvgClass ?? 'site-header__logo-svg';
$logoFallbackClass = $logoFallbackClass ?? 'site-header__logo site-header__logo--fallback';
$logoLockupClass = $logoLockupClass ?? 'site-brand-lockup';
$logoShowSubtitle = $logoShowSubtitle ?? false;
$logoAlt = function_exists('site_brand_full') ? site_brand_full() : 'АН «Содействие»';

$premiumWebp = __DIR__ . '/../assets/brand/logo-premium.webp';
$premiumPng = __DIR__ . '/../assets/brand/logo-premium.png';
$premiumSrc = is_readable($premiumWebp)
    ? '/assets/brand/logo-premium.webp'
    : (is_readable($premiumPng) ? '/assets/brand/logo-premium.png' : '');

if ($premiumSrc !== '') {
    $imgClass = htmlspecialchars($logoSvgClass . ' site-header__logo-premium site-brand-lockup__mark', ENT_QUOTES, 'UTF-8');
    $lockupClass = htmlspecialchars(
        $logoLockupClass . ($logoShowSubtitle ? '' : ' site-brand-lockup--title-only'),
        ENT_QUOTES,
        'UTF-8'
    );
    echo '<span class="' . $lockupClass . '">';
    echo '<img class="' . $imgClass . '" src="' . htmlspecialchars($premiumSrc, ENT_QUOTES, 'UTF-8') . '" width="122" height="150" alt="' . htmlspecialchars($logoAlt, ENT_QUOTES, 'UTF-8') . '" decoding="async">';
    echo '<span class="site-brand-lockup__text" aria-hidden="true">';
    echo '<span class="site-brand-lockup__title">СОДЕЙСТВИЕ</span>';
    if ($logoShowSubtitle) {
        echo '<span class="site-brand-lockup__subtitle">АГЕНТСТВО НЕДВИЖИМОСТИ</span>';
    }
    echo '</span></span>';
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
