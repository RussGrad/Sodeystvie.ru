<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services-catalog.php';
require_once __DIR__ . '/services-icon.php';

/**
 * Страница отдельной услуги: /services/{id}/.
 */
function sodeystvie_render_service_detail_page(string $serviceId): void
{
    if ($serviceId === 'legal') {
        require dirname(__DIR__) . '/services/legal/index.php';

        return;
    }

    if ($serviceId === 'mortgage-center') {
        header('Location: /mortgage/', true, 302);
        exit;
    }

    $service = sodeystvie_service_by_id($serviceId);
    if ($service === null) {
        http_response_code(404);
        $pageTitle = site_format_page_title('Страница не найдена');
        $currentNav = 'services';
        require __DIR__ . '/header.php';
        echo '<main class="page-main page-main--inner" id="main"><div class="container"><h1 class="page-main__heading">Услуга не найдена</h1><p class="page-main__lead"><a href="/services/">Вернуться к списку услуг</a></p></div></main>';
        require __DIR__ . '/footer.php';

        return;
    }

    $pageTitle = site_format_page_title($service['title']);
    $currentNav = 'service-' . $service['id'];
    $serviceHref = sodeystvie_service_page_href($service);

    require __DIR__ . '/header.php';
    ?>
<main class="page-main page-main--inner page-main--service-detail" id="main">
    <div class="container">
        <header class="service-detail__intro">
            <nav class="service-detail__crumbs" aria-label="Хлебные крошки">
                <a class="service-detail__crumb" href="/services/">Услуги</a>
                <span class="service-detail__crumb-sep" aria-hidden="true">/</span>
                <span class="service-detail__crumb service-detail__crumb--current"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            </nav>
            <div class="service-detail__head">
                <div class="services__icon-wrap service-detail__icon" aria-hidden="true">
                    <?php sodeystvie_services_render_icon($service['icon']); ?>
                </div>
                <div>
                    <h1 class="page-main__heading"><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if ($service['short'] !== '') { ?>
                        <p class="page-main__lead"><?php echo htmlspecialchars($service['short'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                </div>
            </div>
        </header>

        <article class="service-detail__card">
            <?php if ($service['text'] !== '') { ?>
                <p class="service-detail__text"><?php echo htmlspecialchars($service['text'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
            <?php if (!empty($service['bullets'])) { ?>
                <ul class="service-detail__bullets">
                    <?php foreach ($service['bullets'] as $bullet) { ?>
                        <li><?php echo htmlspecialchars($bullet, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
            <div class="service-detail__actions">
                <?php if (!empty($service['href']) && $service['href'] !== $serviceHref && !empty($service['hrefLabel'])) { ?>
                    <a class="btn btn--ghost service-detail__link" href="<?php echo htmlspecialchars($service['href'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($service['hrefLabel'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php } ?>
                <button
                    type="button"
                    class="btn btn--primary service-detail__cta"
                    data-lead-open
                    data-lead-topic="service-<?php echo htmlspecialchars($service['id'], ENT_QUOTES, 'UTF-8'); ?>"
                    aria-haspopup="dialog"
                    aria-controls="lead-modal"
                >
                    Оставить заявку
                </button>
            </div>
        </article>

        <section class="service-detail__bottom" aria-label="Другие услуги">
            <p class="service-detail__bottom-text">
                Нужна другая услуга или консультация по нескольким направлениям? Позвоните
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\D+/', '', site_phone_tel()) ?: site_phone_tel(), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(site_phone_display(), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                или посмотрите полный список услуг агентства.
            </p>
            <a class="btn btn--ghost" href="/services/">Все услуги</a>
        </section>
    </div>
</main>
    <?php
    require __DIR__ . '/footer.php';
}
