<?php

declare(strict_types=1);

$pageTitle = 'Услуги — Содействие';
$currentNav = 'services';

require_once __DIR__ . '/../includes/services-catalog.php';
require_once __DIR__ . '/../includes/services-icon.php';

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
        </header>

        <ul class="services-page__list">
            <?php foreach ($servicesItems as $item) { ?>
                <li class="services-page__item" id="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <article class="services-page__card">
                        <div class="services-page__card-head">
                            <div class="services__icon-wrap" aria-hidden="true">
                                <?php sodeystvie_services_render_icon($item['icon']); ?>
                            </div>
                            <div class="services-page__card-titles">
                                <h2 class="services-page__card-title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="services-page__card-lead"><?php echo htmlspecialchars($item['short'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                        <p class="services-page__card-text"><?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($item['bullets'])) { ?>
                            <ul class="services-page__bullets">
                                <?php foreach ($item['bullets'] as $bullet) { ?>
                                    <li><?php echo htmlspecialchars($bullet, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                        <div class="services-page__actions">
                            <?php if (!empty($item['href'])) { ?>
                                <a class="btn btn--ghost services-page__link" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($item['hrefLabel'] ?? 'Подробнее', ENT_QUOTES, 'UTF-8'); ?>
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
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\D+/', '', SITE_PHONE_TEL) ?: SITE_PHONE_TEL, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(SITE_PHONE_DISPLAY, ENT_QUOTES, 'UTF-8'); ?>
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
