<?php

declare(strict_types=1);

/** @var string $pageTitle Заголовок вкладки (<title>) */
/** @var string $currentNav Активный пункт меню: home | catalog | services | mortgage | about | reviews | contacts | vacancies */
/** @var string $preloadLcpImage URL первого кадра hero (preload LCP) */

require_once __DIR__ . '/config.php';

$pageTitle = $pageTitle ?? site_format_page_title();
$pageDescription = $pageDescription ?? null;
$currentNav = $currentNav ?? '';
$preloadLcpImage = $preloadLcpImage ?? '';

site_send_security_headers();

$nav = site_nav_items();
$cssVersion = (string) (@filemtime(__DIR__ . '/../css/main.css') ?: time());
$telegramUrl = site_telegram_url();
$maxUrl = site_max_url();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php site_render_head_meta($pageDescription); ?>
    <?php if ($preloadLcpImage !== '') { ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($preloadLcpImage, ENT_QUOTES, 'UTF-8'); ?>" fetchpriority="high">
    <?php } ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap"></noscript>
    <link rel="stylesheet" href="/css/main.css?v=<?php echo htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Ранняя установка темы: уменьшает мигание до загрузки CSS (localStorage или prefers-color-scheme). -->
    <script>
    (function () {
        try {
            var k = 'sodeystvie-theme';
            var t = localStorage.getItem(k);
            if (t !== 'light' && t !== 'dark') {
                t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
    })();
    </script>
    <script>
    (function () {
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('site-theme-toggle');
            if (!btn) return;
            var moon = btn.querySelector('.site-header__theme-icon--moon');
            var sun = btn.querySelector('.site-header__theme-icon--sun');
            if (moon) moon.hidden = !dark;
            if (sun) sun.hidden = dark;
        });
    })();
    </script>
</head>
<body<?php echo $currentNav === 'home' ? ' class="page-home"' : ''; ?>>
<header class="site-header" id="site-header">
    <div class="site-header__inner container">
        <div class="site-header__cluster">
            <div class="site-header__brand">
                <a class="site-header__logo-link" href="/" aria-label="<?php echo htmlspecialchars(site_brand_full() . ' — на главную', ENT_QUOTES, 'UTF-8'); ?>">
                    <?php require __DIR__ . '/logo-markup.php'; ?>
                </a>
            </div>

            <div class="site-header__bar" id="site-header-menu">
            <nav class="site-header__nav" aria-label="Основное меню">
                <ul class="site-header__menu">
                <?php foreach ($nav as $slug => $item) { ?>
                    <?php if (site_nav_item_has_children($item)) { ?>
                        <?php
                        $dropdownId = 'nav-dropdown-' . preg_replace('/[^a-z0-9_-]/i', '', $slug);
                        $parentCurrent = site_nav_item_is_current($item, $slug, $currentNav);
                        ?>
                        <li class="site-header__menu-item site-header__menu-item--dropdown" data-nav-dropdown>
                            <div class="site-header__dropdown">
                                <a
                                    class="site-header__menu-link site-header__dropdown-trigger<?php echo $parentCurrent ? ' site-header__menu-link--current' : ''; ?>"
                                    href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-expanded="false"
                                    aria-controls="<?php echo htmlspecialchars($dropdownId, ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-haspopup="true"
                                ><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?><svg class="site-header__dropdown-icon" width="11" height="11" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M2.75 4.5 L6 8.25 L9.25 4.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg></a>
                                <ul class="site-header__submenu" id="<?php echo htmlspecialchars($dropdownId, ENT_QUOTES, 'UTF-8'); ?>" role="list">
                                    <?php foreach ($item['children'] as $cslug => $child) { ?>
                                        <li class="site-header__submenu-item">
                                            <a
                                                class="site-header__submenu-link<?php echo $currentNav === $cslug ? ' site-header__submenu-link--current' : ''; ?>"
                                                href="<?php echo htmlspecialchars($child['href'], ENT_QUOTES, 'UTF-8'); ?>"
                                            ><?php echo htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } else { ?>
                        <li class="site-header__menu-item">
                            <a
                                class="site-header__menu-link<?php echo $currentNav === $slug ? ' site-header__menu-link--current' : ''; ?>"
                                href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                            ><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                    <?php } ?>
                <?php } ?>
                </ul>
            </nav>
            <div class="site-header__actions">
                <div class="site-header__contact">
                    <p class="site-header__hours"><?php echo htmlspecialchars(SITE_WORK_HOURS, ENT_QUOTES, 'UTF-8'); ?></p>
                    <a class="site-header__phone" href="tel:<?php echo htmlspecialchars(SITE_PHONE_TEL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_PHONE_DISPLAY, ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
                <div class="site-header__toolbar">
                    <?php if ($telegramUrl !== null) { ?>
                        <a
                            class="site-header__messenger site-header__messenger--tg"
                            href="<?php echo htmlspecialchars($telegramUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Написать в Telegram"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </a>
                    <?php } ?>
                    <a
                        class="site-header__messenger site-header__messenger--max"
                        href="<?php echo htmlspecialchars($maxUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Написать в MAX"
                    >
                        <img
                            class="site-header__messenger-img"
                            src="/assets/icons/max-messenger.png"
                            width="20"
                            height="20"
                            alt=""
                            decoding="async"
                        >
                    </a>
                    <a class="site-header__fav" href="/catalog/" id="site-header-favorites" aria-label="Избранное">
                        <svg class="site-header__fav-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <span class="site-header__fav-count" id="site-favorites-count" hidden>0</span>
                    </a>
                    <button
                        type="button"
                        class="site-header__cta"
                        id="site-header-lead-open"
                        aria-haspopup="dialog"
                        aria-controls="lead-modal"
                        aria-label="Оставить заявку"
                    >
                        <svg class="site-header__cta-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.07 21 3 13.93 3 5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.46.57 3.58a1 1 0 0 1-.25 1.01l-2.2 2.2z"/>
                        </svg>
                    </button>
                    <button type="button" class="site-header__theme" id="site-theme-toggle" aria-label="Включить светлую тему">
                        <svg class="site-header__theme-icon site-header__theme-icon--moon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg class="site-header__theme-icon site-header__theme-icon--sun" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" hidden>
                            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>
            </div>
        </div>
        <button type="button" class="site-header__burger" id="site-header-burger" aria-controls="site-header-menu" aria-expanded="false" aria-label="Открыть меню">
            <span class="site-header__burger-line"></span>
            <span class="site-header__burger-line"></span>
            <span class="site-header__burger-line"></span>
        </button>
    </div>
</header>
