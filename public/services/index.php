<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$pageTitle = site_format_page_title('Услуги');
$currentNav = 'services';

require_once __DIR__ . '/../includes/services-catalog.php';
require_once __DIR__ . '/../includes/services-icon.php';
require_once __DIR__ . '/../includes/visual-editor.php';

$servicesItems = sodeystvie_services_catalog();

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner page-main--services" id="main">
    <div class="container">
        <header class="services-page__intro">
            <h1 class="page-main__heading">Услуги</h1>
            <p class="page-main__lead">
                Полный спектр сопровождения сделок с недвижимостью в Иркутске: от оценки и подбора объекта
                до ипотеки, юридической проверки и координации цепочки заказов.
            </p>
            <?php if (site_visual_editor_enabled()) { ?>
                <p class="services-page__ve-actions">
                    <button type="button" class="ve-add-btn" data-ve-add-service>Добавить услугу</button>
                </p>
            <?php } ?>
        </header>

        <ul class="services-page__list">
            <?php foreach ($servicesItems as $item) {
                $sid = (string) ($item['id'] ?? '');
                ?>
                <li class="services-page__item">
                    <article class="services-page__card<?php echo site_visual_editor_enabled() ? ' services-page__card--ve' : ''; ?>">
                        <?php if (site_visual_editor_enabled()) { ?>
                            <button
                                type="button"
                                class="ve-card-delete"
                                data-ve-delete-service="<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>"
                                title="Удалить услугу"
                                aria-label="Удалить услугу «<?php echo htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>»"
                            >×</button>
                        <?php } ?>
                        <div class="services-page__card-head">
                            <?php sodeystvie_services_render_icon_wrap($item); ?>
                            <div class="services-page__card-titles">
                                <h2 class="services-page__card-title"<?php echo site_ve_attrs('title', 'text', 'Название услуги', 'services', $sid); ?>><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="services-page__card-lead"<?php echo site_ve_attrs('short', 'textarea', 'Краткое описание', 'services', $sid); ?>><?php echo htmlspecialchars($item['short'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                        <p class="services-page__card-text"<?php echo site_ve_attrs('text', 'textarea', 'Полное описание', 'services', $sid); ?>><?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($item['bullets'])) { ?>
                            <ul class="services-page__bullets">
                                <?php foreach ($item['bullets'] as $bullet) { ?>
                                    <li><?php echo htmlspecialchars($bullet, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                        <div class="services-page__actions">
                            <a class="btn btn--ghost services-page__link" href="<?php echo htmlspecialchars(sodeystvie_service_page_href($item), ENT_QUOTES, 'UTF-8'); ?>">
                                Подробнее
                            </a>
                            <?php if (!empty($item['href']) && !empty($item['hrefLabel']) && $item['href'] !== sodeystvie_service_page_href($item)) { ?>
                                <a class="btn btn--ghost services-page__link" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($item['hrefLabel'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            <?php } ?>
                            <button
                                type="button"
                                class="btn btn--primary services-page__cta"
                                data-lead-open
                                data-lead-topic="service-<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                aria-haspopup="dialog"
                                aria-controls="lead-modal"
                            >
                                Оставить заявку
                            </button>
                        </div>
                    </article>
                </li>
            <?php } ?>
        </ul>

        <section class="services-page__bottom" aria-label="Связаться с нами">
            <p class="services-page__bottom-text">
                Не нашли нужную услугу или остались вопросы? Позвоните
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\D+/', '', site_phone_tel()) ?: site_phone_tel(), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(site_phone_display(), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                или оставьте заявку — перезвоним и подскажем оптимальный вариант.
            </p>
            <button
                type="button"
                class="btn btn--primary"
                data-lead-open
                data-lead-topic="services"
                aria-haspopup="dialog"
                aria-controls="lead-modal"
            >
                Заказать консультацию
            </button>
        </section>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
