<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/site-reviews.php';
require_once __DIR__ . '/../includes/site-about.php';

$pageTitle = site_format_page_title('Отзывы');
$currentNav = 'reviews';
$reviewsAll = site_reviews_all();
$reviewsPreview = site_reviews_preview($reviewsAll);
$platforms = site_reviews_platforms();

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner reviews-page reviews-page--dashboard" id="main">
    <div class="container reviews-page__container">
        <header class="reviews-page__header">
            <p class="reviews-page__eyebrow">Мнение клиентов</p>
            <h1 class="page-main__heading reviews-page__title">Отзывы</h1>
            <p class="page-main__lead reviews-page__lead">Репутация команды <?php echo htmlspecialchars(site_brand_full(), ENT_QUOTES, 'UTF-8'); ?> на ведущих площадках — отзывы о работе агентства и специалистов.</p>
        </header>

        <?php site_render_reviews_demo_notice(); ?>

        <?php require __DIR__ . '/../includes/review-agent-profile.php'; ?>

        <?php if (count($reviewsPreview) > 0) { ?>
            <section class="reviews-feed" aria-labelledby="reviews-feed-title">
                <header class="reviews-feed__header">
                    <h2 class="reviews-feed__title" id="reviews-feed-title">Последние отзывы</h2>
                    <p class="reviews-feed__lead">Нажмите на карточку, чтобы открыть полный текст на площадке.</p>
                </header>
                <div class="reviews-page__list reviews-feed__grid">
                    <?php foreach ($reviewsPreview as $review) {
                        site_render_review_card($review);
                    } ?>
                </div>
            </section>
        <?php } elseif (count($platforms) === 0) { ?>
            <p class="reviews-page__empty">Отзывы скоро появятся здесь. Добавьте ссылки на площадки в <code>public/.env</code>.</p>
        <?php } ?>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
