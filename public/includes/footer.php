<?php

declare(strict_types=1);

// Подвал: бренд, меню, услуги, CTA; нижняя полоса — копирайт и юридические тексты.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services-catalog.php';
require_once __DIR__ . '/site-legal.php';

$nav = site_nav_items();
$currentNav = $currentNav ?? '';
$services = sodeystvie_services_catalog();
$siteHeaderJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-header.js') ?: time());
$themeJsVersion = (string) (@filemtime(__DIR__ . '/../js/theme.js') ?: time());
$siteFavoritesJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-favorites.js') ?: time());
$siteFooterJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-footer.js') ?: time());
$heroJsVersion = (string) (@filemtime(__DIR__ . '/../js/hero.js') ?: time());
$leadModalJsVersion = (string) (@filemtime(__DIR__ . '/../js/lead-modal.js') ?: time());
$siteChatJsVersion = (string) (@filemtime(__DIR__ . '/../js/site-chat.js') ?: time());

$footerMenuLinks = [];
foreach ($nav as $slug => $item) {
    $footerMenuLinks[] = [
        'slug' => $slug,
        'href' => $item['href'],
        'label' => $item['label'],
    ];
    if (site_nav_item_has_children($item)) {
        foreach ($item['children'] as $cslug => $child) {
            $footerMenuLinks[] = [
                'slug' => $cslug,
                'href' => $child['href'],
                'label' => $child['label'],
            ];
        }
    }
}

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
                    <?php foreach ($footerMenuLinks as $link) { ?>
                        <li class="site-footer__menu-item">
                            <a
                                class="site-footer__menu-link<?php echo $currentNav === $link['slug'] ? ' site-footer__menu-link--current' : ''; ?>"
                                href="<?php echo htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>"
                            ><?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                    <?php } ?>
                </ul>
            </div>

            <div class="site-footer__col site-footer__col--services">
                <h2 class="site-footer__heading">Услуги</h2>
                <ul class="site-footer__menu">
                    <?php foreach ($services as $service) { ?>
                        <?php $serviceSlug = 'service-' . $service['id']; ?>
                        <li class="site-footer__menu-item">
                            <a
                                class="site-footer__menu-link<?php echo $currentNav === $serviceSlug ? ' site-footer__menu-link--current' : ''; ?>"
                                href="<?php echo htmlspecialchars(sodeystvie_service_page_href($service), ENT_QUOTES, 'UTF-8'); ?>"
                            ><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                    <?php } ?>
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
                <a class="site-footer__privacy" href="/privacy/">Политика конфиденциальности</a>
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
