<?php

declare(strict_types=1);

// Подвал: бренд, меню, реквизиты, CTA; нижняя полоса — копирайт и юридические тексты.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/site-legal.php';
require_once __DIR__ . '/messenger-links.php';

$nav = site_nav_items();
$currentNav = $currentNav ?? '';
$siteHeaderJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-header.js') ?: time());
$themeJsVersion = (string) (@filemtime(__DIR__ . '/../js/theme.js') ?: time());
$siteFavoritesJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-favorites.js') ?: time());
$siteFooterJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-footer.js') ?: time());
$heroJsVersion = (string) (@filemtime(__DIR__ . '/../js/hero.js') ?: time());
$leadModalJsVersion = (string) (@filemtime(__DIR__ . '/../js/lead-modal.js') ?: time());
$siteChatJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-chat.js') ?: time());
$privacyModalJsVersion = (string) (@filemtime(__DIR__ . '/../js/privacy-modal.js') ?: time());

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
                <p class="site-footer__tagline">
                    Услуги по продаже, покупке и аренде недвижимости в <?php echo htmlspecialchars(SITE_CITY_TAG, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>

            <div class="site-footer__col site-footer__col--nav">
                <h2 class="site-footer__heading">Меню</h2>
                <ul class="site-footer__menu">
                    <?php foreach ($nav as $slug => $item) { ?>
                        <?php if (site_nav_item_has_children($item)) { ?>
                            <?php
                            $footerSubmenuId = 'footer-submenu-' . preg_replace('/[^a-z0-9_-]/i', '', $slug);
                            $isCurrentParent = site_nav_item_is_current($item, $slug, $currentNav);
                            ?>
                            <li
                                class="site-footer__menu-item site-footer__menu-item--group<?php echo $isCurrentParent ? ' site-footer__menu-item--open' : ''; ?>"
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
                                        aria-expanded="<?php echo $isCurrentParent ? 'true' : 'false'; ?>"
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

            <div class="site-footer__col site-footer__col--legal">
                <h2 class="site-footer__heading">Реквизиты</h2>
                <dl class="site-footer__requisites">
                    <div class="site-footer__requisite">
                        <dt class="site-footer__requisite-label">Организация</dt>
                        <dd class="site-footer__requisite-value"><?php echo htmlspecialchars(SITE_LEGAL_NAME, ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <?php if (site_legal_inn() !== '') { ?>
                        <div class="site-footer__requisite">
                            <dt class="site-footer__requisite-label">ИНН</dt>
                            <dd class="site-footer__requisite-value"><?php echo htmlspecialchars(site_legal_inn(), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                    <?php } ?>
                    <?php if (site_legal_ogrn() !== '') { ?>
                        <div class="site-footer__requisite">
                            <dt class="site-footer__requisite-label">ОГРН</dt>
                            <dd class="site-footer__requisite-value"><?php echo htmlspecialchars(site_legal_ogrn(), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                    <?php } ?>
                    <div class="site-footer__requisite">
                        <dt class="site-footer__requisite-label">Телефон</dt>
                        <dd class="site-footer__requisite-value">
                            <a class="site-footer__requisite-link" href="tel:<?php echo htmlspecialchars(site_phone_tel(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(site_phone_display(), ENT_QUOTES, 'UTF-8'); ?></a>
                        </dd>
                    </div>
                    <div class="site-footer__requisite">
                        <dt class="site-footer__requisite-label">Email</dt>
                        <dd class="site-footer__requisite-value">
                            <a class="site-footer__requisite-link" href="mailto:<?php echo htmlspecialchars(site_email_address(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(site_email_address(), ENT_QUOTES, 'UTF-8'); ?></a>
                        </dd>
                    </div>
                    <div class="site-footer__requisite">
                        <dt class="site-footer__requisite-label">Адрес</dt>
                        <dd class="site-footer__requisite-value"><?php echo htmlspecialchars(site_postal_address(), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="site-footer__requisite">
                        <dt class="site-footer__requisite-label">Режим работы</dt>
                        <dd class="site-footer__requisite-value"><?php echo htmlspecialchars(site_office_hours(), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="site-footer__requisite site-footer__requisite--messengers">
                        <dt class="site-footer__requisite-label">Мессенджеры</dt>
                        <dd class="site-footer__requisite-value">
                            <?php site_render_messenger_links(
                                'messenger-links messenger-links--flat site-footer__messengers',
                                'messenger-links__item',
                            ); ?>
                        </dd>
                    </div>
                </dl>
                <ul class="site-footer__legal-links">
                    <li><a class="site-footer__legal-link" href="/cookies/">Согласие на cookies</a></li>
                </ul>
            </div>

            <div class="site-footer__col site-footer__col--cta">
                <ul class="site-footer__cta-list">
                    <li class="site-footer__cta-item">
                        <a
                            class="site-footer__cta"
                            href="/catalog/"
                        >
                            <span class="site-footer__cta-text">Подобрать недвижимость для покупки</span>
                            <span class="site-footer__cta-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </a>
                    </li>
                    <li class="site-footer__cta-item">
                        <a
                            class="site-footer__cta"
                            href="/services/valuation/"
                        >
                            <span class="site-footer__cta-text">Оценить вашу недвижимость</span>
                            <span class="site-footer__cta-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="site-footer__bar">
            <div class="site-footer__bar-col site-footer__bar-col--left">
                <p class="site-footer__copy">
                    <?php echo htmlspecialchars(SITE_CITY_TAG, ENT_QUOTES, 'UTF-8'); ?>
                    <?php echo (int) SITE_FOUNDED_YEAR; ?>–<?php echo date('Y'); ?>
                    © Все права защищены
                </p>
                <a class="site-footer__privacy" href="/privacy/" data-privacy-open>Политика конфиденциальности</a>
            </div>

            <div class="site-footer__bar-col site-footer__bar-col--center">
                <p class="site-footer__legal-note"><?php echo htmlspecialchars(site_footer_reprint_notice(), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="site-footer__legal-note"><?php echo htmlspecialchars(site_footer_info_disclaimer(), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="site-footer__bar-col site-footer__bar-col--right">
                <button
                    type="button"
                    class="site-footer__top"
                    id="site-footer-top"
                    aria-label="Наверх"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</footer>
<?php require __DIR__ . '/modal-lead.php'; ?>
<?php require __DIR__ . '/modal-privacy.php'; ?>
<?php require __DIR__ . '/modal-site-chat.php'; ?>
    <script src="/js/theme.js?v=<?php echo htmlspecialchars($themeJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/site-favorites.js?v=<?php echo htmlspecialchars($siteFavoritesJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/site-header.js?v=<?php echo htmlspecialchars($siteHeaderJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/site-footer.js?v=<?php echo htmlspecialchars($siteFooterJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/lead-modal.js?v=<?php echo htmlspecialchars($leadModalJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="/js/privacy-modal.js?v=<?php echo htmlspecialchars($privacyModalJsVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
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
