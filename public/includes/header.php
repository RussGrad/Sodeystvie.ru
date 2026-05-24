<?php

declare(strict_types=1);

/** @var string $pageTitle Заголовок вкладки (<title>) */
/** @var string $currentNav Активный пункт меню: home | catalog | services | mortgage | about | reviews | contacts | vacancies */

$pageTitle = $pageTitle ?? 'Содействие — агентство недвижимости';
$currentNav = $currentNav ?? '';

require_once __DIR__ . '/config.php';

site_send_security_headers();

$nav = site_nav_items();
$cssVersion = (string) (@filemtime(__DIR__ . '/../css/main.css') ?: time());
$whatsappUrl = site_whatsapp_url();
$telegramUrl = site_telegram_url();
$headerTagline = site_header_tagline();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
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
</head>
<body>
<header class="site-header" id="site-header">
    <div class="site-header__inner container">
        <div class="site-header__brand">
            <a class="site-header__logo-link" href="/" aria-label="Содействие — на главную">
                <?php require __DIR__ . '/logo-markup.php'; ?>
            </a>
            <p class="site-header__tagline"><?php echo htmlspecialchars($headerTagline, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <nav class="site-header__nav" id="site-header-menu" aria-label="Основное меню">
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
                                    class="site-header__menu-link<?php echo $parentCurrent ? ' site-header__menu-link--current' : ''; ?>"
                                    href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                                ><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                                <button
                                    type="button"
                                    class="site-header__dropdown-toggle"
                                    aria-expanded="false"
                                    aria-controls="<?php echo htmlspecialchars($dropdownId, ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-haspopup="true"
                                    aria-label="Подменю: <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <svg class="site-header__dropdown-icon" width="11" height="11" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M2.75 4.5 L6 8.25 L9.25 4.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
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
            <div class="site-header__actions">
                <div class="site-header__contact">
                    <p class="site-header__hours"><?php echo htmlspecialchars(SITE_WORK_HOURS, ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="site-header__phone-row">
                        <a class="site-header__phone" href="tel:<?php echo htmlspecialchars(SITE_PHONE_TEL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_PHONE_DISPLAY, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php if ($whatsappUrl !== null) { ?>
                            <a
                                class="site-header__messenger site-header__messenger--wa"
                                href="<?php echo htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Написать в WhatsApp"
                            >
                                <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                            </a>
                        <?php } ?>
                        <?php if ($telegramUrl !== null) { ?>
                            <a
                                class="site-header__messenger site-header__messenger--tg"
                                href="<?php echo htmlspecialchars($telegramUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Написать в Telegram"
                            >
                                <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                            </a>
                        <?php } ?>
                    </div>
                </div>
                <a class="site-header__fav" href="/catalog/" id="site-header-favorites" aria-label="Избранное">
                    <svg class="site-header__fav-icon" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
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
                    <svg class="site-header__cta-icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor" d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.07 21 3 13.93 3 5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.46.57 3.58a1 1 0 0 1-.25 1.01l-2.2 2.2z"/>
                    </svg>
                    <span class="site-header__cta-text">Оставить заявку</span>
                </button>
                <button type="button" class="site-header__theme" id="site-theme-toggle" aria-label="Включить светлую тему">
                    <?php /* Светлая тема активна → показать луну (переключить на тёмную) */ ?>
                    <svg class="site-header__theme-icon site-header__theme-icon--moon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php /* Тёмная тема активна → показать солнце */ ?>
                    <svg class="site-header__theme-icon site-header__theme-icon--sun" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </nav>
        <button type="button" class="site-header__burger" id="site-header-burger" aria-controls="site-header-menu" aria-expanded="false" aria-label="Открыть меню">
            <span class="site-header__burger-line"></span>
            <span class="site-header__burger-line"></span>
            <span class="site-header__burger-line"></span>
        </button>
    </div>
</header>
