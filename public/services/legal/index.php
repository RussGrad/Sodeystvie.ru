<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';

$pageTitle = site_format_page_title('Юридические услуги');
$currentNav = 'service-legal';

require_once __DIR__ . '/../../includes/legal-services.php';
require_once __DIR__ . '/../../includes/services-icon.php';

$legalPage = sodeystvie_legal_services_page();
$legalItems = $legalPage['items'];

require __DIR__ . '/../../includes/header.php';
?>
<main class="page-main page-main--inner page-main--legal-services" id="main">
    <div class="container">
        <header class="legal-services-page__intro">
            <nav class="legal-services-page__crumbs" aria-label="Хлебные крошки">
                <a class="legal-services-page__crumb" href="/services/">Услуги</a>
                <span class="legal-services-page__crumb-sep" aria-hidden="true">/</span>
                <span class="legal-services-page__crumb legal-services-page__crumb--current">Юридические услуги</span>
            </nav>
            <div class="legal-services-page__head">
                <div class="services__icon-wrap legal-services-page__icon" aria-hidden="true">
                    <?php sodeystvie_services_render_icon('legal'); ?>
                </div>
                <div>
                    <h1 class="page-main__heading">Юридические услуги</h1>
                    <p class="page-main__lead"><?php echo htmlspecialchars($legalPage['intro'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </header>

        <?php if (count($legalItems) > 0) { ?>
            <ul class="legal-services-page__list">
                <?php foreach ($legalItems as $index => $item) { ?>
                    <li class="legal-services-page__item" id="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <article class="legal-services-page__card">
                            <span class="legal-services-page__num" aria-hidden="true"><?php echo (int) ($index + 1); ?></span>
                            <div class="legal-services-page__body">
                                <h2 class="legal-services-page__title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <?php if ($item['short'] !== '') { ?>
                                    <p class="legal-services-page__short"><?php echo htmlspecialchars($item['short'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php } ?>
                                <?php if ($item['text'] !== '') { ?>
                                    <p class="legal-services-page__text"><?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php } ?>
                                <?php if (!empty($item['bullets'])) { ?>
                                    <ul class="legal-services-page__bullets">
                                        <?php foreach ($item['bullets'] as $bullet) { ?>
                                            <li><?php echo htmlspecialchars($bullet, ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                                <button
                                    type="button"
                                    class="btn btn--primary legal-services-page__cta"
                                    data-lead-open
                                    data-lead-topic="legal-<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-haspopup="dialog"
                                    aria-controls="lead-modal"
                                >
                                    Получить консультацию
                                </button>
                            </div>
                        </article>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>

        <section class="legal-services-page__bottom" aria-label="Связаться с юристом">
            <p class="legal-services-page__bottom-text">
                Опишите ситуацию — юрист перезвонит, подскажет порядок действий и оценит объём работ.
                Телефон:
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\D+/', '', site_phone_tel()) ?: site_phone_tel(), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(site_phone_display(), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </p>
            <div class="legal-services-page__bottom-actions">
                <a class="btn btn--ghost" href="/services/">Все услуги агентства</a>
                <button
                    type="button"
                    class="btn btn--primary"
                    data-lead-open
                    data-lead-topic="legal"
                    aria-haspopup="dialog"
                    aria-controls="lead-modal"
                >
                    Оставить заявку
                </button>
            </div>
        </section>
    </div>
</main>
<?php
require __DIR__ . '/../../includes/footer.php';
