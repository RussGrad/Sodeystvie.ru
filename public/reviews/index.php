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
<main class="page-main page-main--inner reviews-page" id="main">
    <div class="container">
        <header class="reviews-page__header">
            <h1 class="page-main__heading">Отзывы</h1>
            <p class="page-main__lead">Что говорят клиенты об работе с <?php echo htmlspecialchars(site_brand_full(), ENT_QUOTES, 'UTF-8'); ?>.</p>
        </header>

        <?php site_render_reviews_demo_notice(); ?>

        <?php require __DIR__ . '/../includes/review-agent-profile.php'; ?>

        <?php if (count($reviewsPreview) > 0) { ?>
            <h2 class="reviews-page__local-heading">Отзывы клиентов</h2>
            <p class="reviews-page__local-lead">
                Последние <?php echo (int) count($reviewsPreview); ?> отзыва — нажмите на карточку, чтобы открыть на площадке.
            </p>
            <div class="reviews-page__list">
                <?php foreach ($reviewsPreview as $review) {
                    site_render_review_card($review);
                } ?>
            </div>
            <?php site_render_reviews_platform_ctas('reviews-page__actions'); ?>
        <?php } elseif (count($platforms) === 0) { ?>
            <p class="reviews-page__empty">Отзывы скоро появятся здесь. Добавьте ссылки на площадки в <code>public/.env</code>.</p>
        <?php } ?>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
