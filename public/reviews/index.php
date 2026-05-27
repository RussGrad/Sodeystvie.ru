<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/site-reviews.php';

$pageTitle = site_format_page_title('Отзывы');
$currentNav = 'reviews';
$reviewsAll = site_reviews_all();
$summary = site_reviews_summary();
$platforms = site_reviews_platforms();

require __DIR__ . '/../includes/header.php';
?>
<main class="page-main page-main--inner reviews-page" id="main">
    <div class="container">
        <header class="reviews-page__header">
            <h1 class="page-main__heading">Отзывы</h1>
            <p class="page-main__lead">Что говорят клиенты об работе с <?php echo htmlspecialchars(site_brand_full(), ENT_QUOTES, 'UTF-8'); ?>.</p>
        </header>

        <section class="reviews-page__summary" aria-label="Сводная оценка">
            <div class="reviews-page__score">
                <span class="reviews-page__score-value"><?php echo htmlspecialchars(number_format($summary['rating'], 1, '.', ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php echo site_reviews_render_stars((int) round($summary['rating']), 'review-stars review-stars--lg'); ?>
                <p class="reviews-page__score-caption"><?php echo htmlspecialchars($summary['countLabel'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </section>

        <?php
        $reviewPlatformsCompact = false;
        $reviewPlatformsWidgets = true;
        require __DIR__ . '/../includes/review-platforms.php';
        ?>

        <?php if (count($reviewsAll) > 0) { ?>
            <h2 class="reviews-page__local-heading">Отзывы на сайте</h2>
            <div class="reviews-page__list">
                <?php foreach ($reviewsAll as $review) {
                    site_render_review_card($review, false);
                } ?>
            </div>
        <?php } elseif (count($platforms) === 0) { ?>
            <p class="reviews-page__empty">Отзывы скоро появятся здесь. Добавьте ссылки на площадки в <code>public/.env</code>.</p>
        <?php } ?>
    </div>
</main>
<?php
require __DIR__ . '/../includes/footer.php';
