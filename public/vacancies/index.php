<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/site-vacancies.php';

$pageTitle = site_format_page_title('Вакансии');
$currentNav = 'vacancies';

$vacancies = site_vacancies_all();
$perks = site_vacancies_perks();
$steps = site_vacancies_hiring_steps();

$phoneHref = preg_replace('/\D+/', '', SITE_PHONE_TEL) ?: SITE_PHONE_TEL;

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner page-main--vacancies" id="main">
    <div class="container">
        <header class="vacancies-page__intro">
            <h1 class="page-main__heading">Вакансии</h1>
            <p class="page-main__lead">
                Присоединяйтесь к команде <?php echo htmlspecialchars(site_brand_full(), ENT_QUOTES, 'UTF-8'); ?> —
                помогаем клиентам безопасно покупать и продавать недвижимость в Иркутске с <?php echo (int) SITE_FOUNDED_YEAR; ?> года.
            </p>
        </header>

        <?php if (count($vacancies) === 0) { ?>
            <p class="vacancies-page__empty">Сейчас открытых вакансий нет. Отправьте резюме на
                <a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_EMAIL, ENT_QUOTES, 'UTF-8'); ?></a>
                — свяжемся, когда появится подходящая позиция.
            </p>
        <?php } else { ?>
            <ul class="vacancies-page__list">
                <?php foreach ($vacancies as $vacancy) { ?>
                    <li class="vacancies-page__item">
                        <?php site_render_vacancy_card($vacancy); ?>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>

        <section class="vacancies-page__perks" aria-labelledby="vacancies-perks-title">
            <h2 class="vacancies-page__section-title" id="vacancies-perks-title">Почему работать у нас</h2>
            <ul class="vacancies-page__perks-grid">
                <?php foreach ($perks as $perk) { ?>
                    <li class="vacancies-page__perk">
                        <h3 class="vacancies-page__perk-title"><?php echo htmlspecialchars($perk['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="vacancies-page__perk-text"><?php echo htmlspecialchars($perk['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </li>
                <?php } ?>
            </ul>
        </section>

        <section class="vacancies-page__steps" aria-labelledby="vacancies-steps-title">
            <h2 class="vacancies-page__section-title" id="vacancies-steps-title">Как попасть в команду</h2>
            <ol class="vacancies-page__steps-list">
                <?php foreach ($steps as $step) { ?>
                    <li class="vacancies-page__step">
                        <span class="vacancies-page__step-num" aria-hidden="true"><?php echo htmlspecialchars($step['step'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="vacancies-page__step-body">
                            <h3 class="vacancies-page__step-title"><?php echo htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="vacancies-page__step-text"><?php echo htmlspecialchars($step['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </li>
                <?php } ?>
            </ol>
        </section>

        <section class="vacancies-page__bottom" aria-label="Отклик на вакансию">
            <h2 class="vacancies-page__bottom-title">Не нашли подходящую позицию?</h2>
            <p class="vacancies-page__bottom-text">
                Отправьте резюме на
                <a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_EMAIL, ENT_QUOTES, 'UTF-8'); ?></a>
                или позвоните
                <a href="tel:<?php echo htmlspecialchars($phoneHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(SITE_PHONE_DISPLAY, ENT_QUOTES, 'UTF-8'); ?></a>
                — рассмотрим кандидатуру на текущие или будущие вакансии.
            </p>
            <button
                type="button"
                class="vacancies-page__bottom-btn"
                data-lead-open
                data-lead-topic="vacancies"
                aria-haspopup="dialog"
                aria-controls="lead-modal"
            >
                Отправить резюме
            </button>
        </section>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
