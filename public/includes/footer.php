<?php

declare(strict_types=1);

// Подвал: логотип, меню, контакты, реквизиты, политики; затем скрипты и закрытие документа.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/site-legal.php';

$nav = site_nav_items();
$currentNav = $currentNav ?? '';
$siteHeaderJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-header.js') ?: time());
$themeJsVersion = (string) (@filemtime(__DIR__ . '/../js/theme.js') ?: time());
$siteFavoritesJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-favorites.js') ?: time());
$siteFooterJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-footer.js') ?: time());
$heroJsVersion = (string) (@filemtime(__DIR__ . '/../js/hero.js') ?: time());
$leadModalJsVersion = (string) (@filemtime(__DIR__ . '/../js/lead-modal.js') ?: time());
$siteChatJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-chat.js') ?: time());

?>
<footer class="site-footer" id="site-footer">
    <div class="site-footer__inner container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <a class="site-footer__logo-link" href="/" aria-label="<?php echo htmlspecialchars(site_brand_full() . ' — на главную', ENT_QUOTES, 'UTF-8'); ?>">
                    <?php
                    $logoSvgClass = 'site-footer__logo-svg';
                    $logoFallbackClass = 'site-footer__logo site-footer__logo--fallback';
                    require __DIR__ . '/logo-markup.php';
                    ?>
                </a>
                <p class="site-footer__tagline"><?php echo htmlspecialchars(site_header_tagline(), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="site-footer__col site-footer__col--nav">
                <h2 class="site-footer__heading">Разделы</h2>
                <ul class="site-footer__menu">
                    <?php foreach ($nav as $slug => $item) { ?>
                        <?php if (site_nav_item_has_children($item)) { ?>
                            <?php
                            $footerSubmenuId = 'footer-submenu-' . preg_replace('/[^a-z0-9_-]/i', '', $slug);
                            $isCurrentParent = site_nav_item_is_current($item, $slug, $currentNav);
                            ?>
                            <li
                                class="site-footer__menu-item site-footer__menu-item--group"
                                data-footer-menu-group
                            >
                                <div class="site-footer__menu-row">
                                    <a
                                        class="site-footer__menu-link<?php echo $isCurrentParent ? ' site-footer__menu-link--current' : ''; ?>"
                                        href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                                    ><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <button
                                        type="button"
                                        class="site-footer__submenu-toggle"
                                        aria-expanded="false"
                                        aria-controls="<?php echo htmlspecialchars($footerSubmenuId, ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="Показать подменю: <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <svg class="site-footer__submenu-icon" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M3.5 5.5L7 9L10.5 5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                                <ul class="site-footer__submenu" id="<?php echo htmlspecialchars($footerSubmenuId, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php foreach ($item['children'] as $cslug => $child) { ?>
                                        <li class="site-footer__menu-item">
                                            <a
                                                class="site-footer__menu-link site-footer__menu-link--sub<?php echo $currentNav === $cslug ? ' site-footer__menu-link--current' : ''; ?>"
                                                href="<?php echo htmlspecialchars($child['href'], ENT_QUOTES, 'UTF-8'); ?>"
                                            ><?php echo htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </li>
                        <?php } else { ?>
                            <li class="site-footer__menu-item">
                                <a
                                    class="site-footer__menu-link<?php echo $currentNav === $slug ? ' site-footer__menu-link--current' : ''; ?>"
                                    href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                                ><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                        <?php } ?>
                    <?php } ?>
                </ul>
            </div>

            <div class="site-footer__col site-footer__col--contacts">
                <h2 class="site-footer__heading">Контакты</h2>
                <ul class="site-footer__contacts">
                    <li class="site-footer__contact">
                        <a class="site-footer__contact-link" href="tel:<?php echo htmlspecialchars(site_phone_tel(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(site_phone_display(), ENT_QUOTES, 'UTF-8'); ?></a>
                    </li>
                    <li class="site-footer__contact">
                        <a class="site-footer__contact-link" href="mailto:<?php echo htmlspecialchars(site_email_address(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(site_email_address(), ENT_QUOTES, 'UTF-8'); ?></a>
                    </li>
                    <li class="site-footer__contact site-footer__contact--address"><?php echo htmlspecialchars(site_postal_address(), ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>
            </div>

            <div class="site-footer__col site-footer__col--legal">
                <h2 class="site-footer__heading">Реквизиты</h2>
                <p class="site-footer__requisites">
                    <?php echo htmlspecialchars(SITE_LEGAL_NAME, ENT_QUOTES, 'UTF-8'); ?><br>
                    ИНН <?php echo htmlspecialchars(site_legal_inn_display(), ENT_QUOTES, 'UTF-8'); ?> · ОГРН <?php echo htmlspecialchars(site_legal_ogrn_display(), ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <ul class="site-footer__legal-links">
                    <li><a class="site-footer__legal-link" href="/privacy/">Политика конфиденциальности</a></li>
                    <li><a class="site-footer__legal-link" href="/cookies/">Согласие на использование cookies</a></li>
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p class="site-footer__copy">© <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_LEGAL_NAME, ENT_QUOTES, 'UTF-8'); ?>. Все права защищены.</p>
            <p class="site-footer__legal-note"><?php echo htmlspecialchars(site_footer_reprint_notice(), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="site-footer__legal-note"><?php echo htmlspecialchars(site_footer_info_disclaimer(), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
</footer>
<?php require __DIR__ . '/modal-lead.php'; ?>
<?php require __DIR__ . '/modal-site-chat.php'; ?>
    <script src="/js/theme.js?v=<?php echo htmlspecialchars($themeJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/site-favorites.js?v=<?php echo htmlspecialchars($siteFavoritesJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/site-header.js?v=<?php echo htmlspecialchars($siteHeaderJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/site-footer.js?v=<?php echo htmlspecialchars($siteFooterJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/lead-modal.js?v=<?php echo htmlspecialchars($leadModalJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/site-chat.js?v=<?php echo htmlspecialchars($siteChatJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/hero.js?v=<?php echo htmlspecialchars($heroJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php
    $mortgageQuizJsVersion = (string) (@filemtime(__DIR__ . '/../js/mortgage-quiz.js') ?: time());
    ?>
    <script src="/js/mortgage-quiz.js?v=<?php echo htmlspecialchars($mortgageQuizJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php
    $mortgageJsVersion = (string) (@filemtime(__DIR__ . '/../js/mortgage.js') ?: time());
    ?>
    <script src="/js/mortgage.js?v=<?php echo htmlspecialchars($mortgageJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php
    $listingPageJsVersion = (string) (@filemtime(__DIR__ . '/../js/listing-page.js') ?: time());
    ?>
    <script src="/js/listing-page.js?v=<?php echo htmlspecialchars($listingPageJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php
    if (!empty($extraDeferScripts) && is_array($extraDeferScripts)) {
        foreach ($extraDeferScripts as $scriptSrc) {
            if (!is_string($scriptSrc) || $scriptSrc === '') {
                continue;
            }
            ?>
    <script src="<?php echo htmlspecialchars($scriptSrc, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
            <?php
        }
    }
    ?>
</body>
</html>
