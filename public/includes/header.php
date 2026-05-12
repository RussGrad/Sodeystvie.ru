<?php

declare(strict_types=1);

/** @var string $pageTitle Заголовок вкладки (<title>) */
/** @var string $currentNav Активный пункт меню: home | catalog | services | mortgage | mortgage_calc | mortgage_apply | about | reviews | contacts | vacancies */

$pageTitle = $pageTitle ?? 'Содействие — агентство недвижимости';
$currentNav = $currentNav ?? '';

require_once __DIR__ . '/config.php';

$nav = site_nav_items();
$cssVersion = (string) (@filemtime(__DIR__ . '/../css/main.css') ?: time());

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
        <a class="site-header__logo-link" href="/" aria-label="Содействие — на главную">
            <?php require __DIR__ . '/logo-markup.php'; ?>
        </a>
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
                <a class="site-header__phone" href="tel:<?php echo htmlspecialchars(SITE_PHONE_TEL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_PHONE_DISPLAY, ENT_QUOTES, 'UTF-8'); ?></a>
                <button
                    type="button"
                    class="site-header__cta"
                    id="site-header-lead-open"
                    aria-haspopup="dialog"
                    aria-controls="lead-modal"
                >Оставить заявку</button>
                <button type="button" class="site-header__theme" id="site-theme-toggle" aria-label="Переключить тему оформления">
                    <svg class="site-header__theme-icon site-header__theme-label--light" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor" d="M12 18a6 6 0 1 1 0-12 6 6 0 0 1 0 12Zm0-2a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM11 1h2v3h-2V1Zm0 18h2v3h-2v-3ZM3.515 4.929l1.414-1.414L7.05 5.636 5.636 7.05 3.515 4.93ZM16.95 18.364l1.414-1.414 2.121 2.121-1.414 1.414-2.121-2.121Zm2.121-14.435-1.414-1.414-2.121 2.121 1.414 1.414 2.121-2.121ZM5.636 16.95l-1.414 1.414-2.121-2.121 1.414-1.414 2.121 2.121ZM23 11v2h-3v-2h3ZM4 11v2H1v-2h3Z"/>
                    </svg>
                    <svg class="site-header__theme-icon site-header__theme-label--dark" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor" d="M21 14.5A7.5 7.5 0 0 1 9.5 3a7.5 7.5 0 1 0 11.5 11.5Z"/>
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
