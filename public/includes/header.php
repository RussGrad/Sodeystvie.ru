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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;500&family=Montserrat:wght@400;600;700&display=swap">
    <link rel="preload" href="/assets/fonts/aalterna.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/css/main.css?v=<?php echo htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8'); ?>">
    <style>
    /* Критичные стили шапки: совпадают с main.css, чтобы не было скачка при догрузке */
    :root[data-theme="dark"]{--header-bg:rgba(18,22,30,.78);--header-border:rgba(255,255,255,.12)}
    :root[data-theme="light"]{--header-bg:#fff;--header-border:rgba(22,22,22,.1)}
    html{scrollbar-gutter:stable}
    body{margin:0;background-color:var(--page-bg,#fff)}
    :root[data-theme="dark"] body{background-color:#121820;background-image:linear-gradient(rgba(10,14,20,.74),rgba(10,14,20,.86)),url('/assets/brand/site-ambient-bg.webp');background-size:cover;background-position:center;background-attachment:fixed}
    .container{width:100%;max-width:none;margin-inline:auto;padding-inline:1.25rem}
    @media(min-width:1024px){.container{padding-inline:120px}}
    .site-header{position:sticky;top:0;z-index:100;background:var(--header-bg);border-bottom:1px solid var(--header-border)}
    .site-header__inner{display:flex;align-items:center;justify-content:space-between;gap:.75rem;box-sizing:border-box;min-height:0;height:auto;padding-block:29px}
    .site-header__cluster{display:flex;align-items:center;flex:1 1 auto;min-width:0;gap:.75rem}
    .site-header__logo-link{display:block;line-height:1;text-decoration:none}
    .site-brand-lockup{display:inline-flex;align-items:center;gap:.55rem;line-height:1}
    .site-brand-lockup--title-only{gap:.62rem}
    .site-brand-lockup--title-only .site-brand-lockup__mark{height:clamp(1.58rem,3.7vw,1.82rem)}
    .site-brand-lockup__mark{height:2.35rem;width:auto;display:block;flex-shrink:0}
    .site-brand-lockup__text{display:inline-grid;grid-template-columns:minmax(0,max-content);justify-items:stretch;gap:.22rem}
    .site-brand-lockup__title,.site-brand-lockup__subtitle{font-family:"AAlterna","Montserrat",system-ui,sans-serif;line-height:1;white-space:nowrap}
    .site-brand-lockup--title-only .site-brand-lockup__text{display:block}
    .site-brand-lockup--title-only .site-brand-lockup__title{display:block;width:auto;font-size:clamp(1.18rem,2.85vw,1.58rem);letter-spacing:.035em;text-align:left;color:var(--header-text,#f5f5f5)}
    .site-brand-lockup__title{display:block;width:100%;font-size:clamp(1.1rem,2.55vw,1.5rem);letter-spacing:.06em;text-align:justify;text-align-last:justify;text-justify:inter-character;color:var(--header-text,#f5f5f5)}
    .site-brand-lockup__subtitle{font-size:clamp(.42rem,1vw,.56rem);letter-spacing:.16em;text-transform:uppercase;color:#d4b56a}
    .site-header__logo-svg,.site-header__logo--fallback,.site-header__logo-premium:not(.site-brand-lockup__mark){height:2.35rem;width:auto;display:block;max-width:min(9.5rem,34vw)}
    .site-header__menu{list-style:none;margin:0;padding:0;display:flex}
    .site-header__menu-link{white-space:nowrap}
    .site-header__burger{display:flex;flex-shrink:0;margin-left:auto}
    @media(max-width:1023.98px){
      .site-header__bar{position:fixed;inset:0;z-index:99;visibility:hidden;opacity:0;pointer-events:none}
    }
    @media(min-width:1024px){
      .site-header__cluster{display:grid;grid-template-columns:auto 1fr auto;width:100%;column-gap:clamp(1rem,2vw,2rem)}
      .site-header__bar{display:contents;min-width:0;position:static;visibility:visible;opacity:1;pointer-events:auto;padding:0}
      .site-header__nav{grid-column:2;display:flex;justify-content:center;justify-self:center;min-width:0}
      .site-header__actions{grid-column:3;display:flex;align-items:center;justify-self:end;flex-shrink:0;gap:.55rem;margin:0;width:auto}
      .site-header__burger{display:none}
      .site-brand-lockup--title-only .site-brand-lockup__mark{height:clamp(1.72rem,2.2vw,1.82rem)}
      .site-brand-lockup__mark{height:2.65rem}
      .site-header__logo-svg,.site-header__logo--fallback,.site-header__logo-premium:not(.site-brand-lockup__mark){height:2.65rem;max-width:10.5rem}
    }
    </style>
    <!-- Ранняя установка темы: уменьшает мигание до загрузки CSS (localStorage или prefers-color-scheme). -->
    <script>
    (function () {
        try {
            var k = 'sodeystvie-theme';
            var t = localStorage.getItem(k);
            if (t !== 'light' && t !== 'dark') {
                t = 'dark';
            }
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
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
                    <p class="site-header__hours"><?php echo htmlspecialchars(site_office_hours(), ENT_QUOTES, 'UTF-8'); ?></p>
                    <a class="site-header__phone" href="tel:<?php echo htmlspecialchars(site_phone_tel(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(site_phone_display(), ENT_QUOTES, 'UTF-8'); ?></a>
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
                    <a class="site-header__fav" href="/catalog/?favorites=1" id="site-header-favorites" aria-label="Избранное">
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
