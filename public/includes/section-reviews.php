<?php

declare(strict_types=1);

require_once __DIR__ . '/site-reviews.php';

$reviewsAll = site_reviews_all();
$summary = site_reviews_summary();
$reviewsHome = array_slice($reviewsAll, 0, 3);
$platforms = site_reviews_platforms();

if (count($reviewsHome) === 0 && count($platforms) === 0) {
    return;
}

?>
<section class="reviews" aria-labelledby="reviews-title">
    <div class="container">
        <header class="reviews__header">
            <div class="reviews__intro">
                <h2 class="reviews__title" id="reviews-title">Отзывы клиентов</h2>
                <p class="reviews__lead">Реальные истории покупки, продажи и аренды недвижимости в Иркутске.</p>
            </div>
            <div class="reviews__summary">
                <p class="reviews__rating" aria-label="Средняя оценка <?php echo htmlspecialchars((string) $summary['rating'], ENT_QUOTES, 'UTF-8'); ?> из 5">
                    <span class="reviews__rating-value"><?php echo htmlspecialchars(number_format($summary['rating'], 1, '.', ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php echo site_reviews_render_stars((int) round($summary['rating']), 'review-stars review-stars--summary'); ?>
                </p>
                <p class="reviews__count"><?php echo htmlspecialchars($summary['countLabel'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </header>

        <?php
        $reviewPlatformsCompact = true;
        $reviewPlatformsWidgets = false;
        require __DIR__ . '/review-platforms.php';
        ?>

        <?php if (count($reviewsHome) > 0) { ?>
            <div class="reviews__grid">
                <?php foreach ($reviewsHome as $review) {
                    site_render_review_card($review, true);
                } ?>
            </div>

            <div class="reviews__actions">
                <a class="reviews__all-link" href="/reviews/">Все отзывы</a>
            </div>
        <?php } elseif (count($platforms) > 0) { ?>
            <div class="reviews__actions">
                <a class="reviews__all-link" href="/reviews/">Страница отзывов</a>
            </div>
        <?php } ?>
    </div>
</section>
