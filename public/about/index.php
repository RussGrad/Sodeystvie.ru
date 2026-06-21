<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/site-about.php';
require_once __DIR__ . '/../includes/site-image.php';
require_once __DIR__ . '/../includes/site-team.php';

$pageTitle = site_format_page_title('О компании');
$currentNav = 'about';

$stats = site_about_stats();
$values = site_about_values();
$steps = site_about_work_steps();
$story = site_about_story_paragraphs();
$team = site_team_all();

$phoneHref = preg_replace('/\D+/', '', site_phone_tel()) ?: site_phone_tel();
$teamAlt = 'Команда ' . site_brand_full() . ' в офисе в ' . SITE_CITY_TAG;

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner page-main--about" id="main">
    <div class="container">
        <header class="about-page__hero">
            <div class="about-page__hero-text">
                <h1 class="page-main__heading">О компании</h1>
                <p class="page-main__lead"><?php echo htmlspecialchars(site_about_intro_text(), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="about-page__tagline"><?php echo htmlspecialchars(site_slogan_hero(), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <?php if (site_about_team_image_exists()) { ?>
                <figure class="about-page__figure">
                    <?php
                    echo site_render_static_picture(
                        site_about_team_image_path(),
                        $teamAlt,
                        'about-page__photo',
                        'width="960" height="640"'
                    );
                    ?>
                </figure>
            <?php } ?>
        </header>

        <ul class="about-page__stats" aria-label="Ключевые показатели">
            <?php foreach ($stats as $stat) { ?>
                <li class="about-page__stat">
                    <span class="about-page__stat-value"><?php echo htmlspecialchars($stat['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="about-page__stat-label"><?php echo htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </li>
            <?php } ?>
        </ul>

        <section class="about-page__story" aria-labelledby="about-story-title">
            <h2 class="about-page__section-title" id="about-story-title">Кто мы</h2>
            <?php foreach ($story as $paragraph) { ?>
                <p class="about-page__paragraph"><?php echo htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php } ?>
        </section>

        <?php if (count($team) > 0) { ?>
        <section class="about-page__team" id="team" aria-labelledby="about-team-title">
            <h2 class="about-page__section-title" id="about-team-title">Команда</h2>
            <p class="about-page__team-lead">Специалисты, которые ведут сделки от консультации до регистрации права собственности.</p>
            <div class="about-page__team-grid team__grid">
                <?php foreach ($team as $member) {
                    site_render_team_card($member);
                } ?>
            </div>
        </section>
        <?php } ?>

        <section class="about-page__values" aria-labelledby="about-values-title">
            <h2 class="about-page__section-title" id="about-values-title">Наши принципы</h2>
            <ul class="about-page__values-grid">
                <?php foreach ($values as $value) { ?>
                    <li class="about-page__value">
                        <h3 class="about-page__value-title"><?php echo htmlspecialchars($value['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="about-page__value-text"><?php echo htmlspecialchars($value['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </li>
                <?php } ?>
            </ul>
        </section>

        <section class="about-page__steps" aria-labelledby="about-steps-title">
            <h2 class="about-page__section-title" id="about-steps-title">Как мы работаем</h2>
            <ol class="about-page__steps-list">
                <?php foreach ($steps as $step) { ?>
                    <li class="about-page__step">
                        <span class="about-page__step-num" aria-hidden="true"><?php echo htmlspecialchars($step['step'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="about-page__step-body">
                            <h3 class="about-page__step-title"><?php echo htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="about-page__step-text"><?php echo htmlspecialchars($step['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </li>
                <?php } ?>
            </ol>
        </section>

        <section class="about-page__legal" aria-label="Реквизиты">
            <h2 class="about-page__section-title">Юридическая информация</h2>
            <dl class="about-page__legal-list">
                <div class="about-page__legal-row">
                    <dt>Наименование</dt>
                    <dd><?php echo htmlspecialchars(SITE_LEGAL_NAME, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="about-page__legal-row">
                    <dt>ИНН</dt>
                    <dd><?php echo htmlspecialchars(site_legal_inn_display(), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="about-page__legal-row">
                    <dt>ОГРН</dt>
                    <dd><?php echo htmlspecialchars(site_legal_ogrn_display(), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="about-page__legal-row">
                    <dt>Адрес офиса</dt>
                    <dd><?php echo htmlspecialchars(site_postal_address(), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="about-page__legal-row">
                    <dt>Режим работы</dt>
                    <dd><?php echo htmlspecialchars(site_office_hours(), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>
        </section>

        <section class="about-page__bottom" aria-label="Связаться с нами">
            <h2 class="about-page__bottom-title">Готовы обсудить вашу задачу?</h2>
            <p class="about-page__bottom-text">
                Позвоните
                <a href="tel:<?php echo htmlspecialchars($phoneHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(site_phone_display(), ENT_QUOTES, 'UTF-8'); ?></a>,
                напишите на
                <a href="mailto:<?php echo htmlspecialchars(site_email_address(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(site_email_address(), ENT_QUOTES, 'UTF-8'); ?></a>
                или оставьте заявку — перезвоним в рабочее время.
            </p>
            <div class="about-page__bottom-actions">
                <button
                    type="button"
                    class="about-page__btn about-page__btn--primary"
                    data-lead-open
                    data-lead-topic="about"
                    aria-haspopup="dialog"
                    aria-controls="lead-modal"
                >
                    Оставить заявку
                </button>
                <a class="about-page__btn about-page__btn--ghost" href="/contacts/">Контакты и карта</a>
                <a class="about-page__btn about-page__btn--ghost" href="/reviews/">Отзывы</a>
                <a class="about-page__btn about-page__btn--ghost" href="/vacancies/">Вакансии</a>
            </div>
        </section>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
