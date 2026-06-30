<?php

declare(strict_types=1);

/**
 * Площадки с отзывами: ссылки и (на странице /reviews/) iframe-виджеты.
 *
 * @var bool $reviewPlatformsCompact Только ссылки в одну строку (главная)
 * @var bool $reviewPlatformsWidgets Показать iframe-виджеты (страница отзывов)
 */
$reviewPlatformsCompact = $reviewPlatformsCompact ?? true;
$reviewPlatformsWidgets = $reviewPlatformsWidgets ?? false;

$platforms = site_reviews_platforms();
if (count($platforms) === 0) {
    return;
}
?>
<div class="review-platforms-wrap<?php echo $reviewPlatformsCompact ? ' review-platforms-wrap--compact' : ''; ?>">
    <?php if (!$reviewPlatformsCompact) { ?>
        <h2 class="review-platforms-wrap__heading">Отзывы на площадках</h2>
        <p class="review-platforms-wrap__lead">Читайте и оставляйте отзывы там, где вам удобно.</p>
    <?php } else { ?>
        <p class="review-platforms-wrap__label">Нас рекомендуют на площадках</p>
    <?php } ?>

    <?php site_render_review_platform_links($platforms); ?>

    <?php if ($reviewPlatformsWidgets && count($platforms) > 0) {
        site_render_review_platform_invites($platforms);
    } ?>
</div>
