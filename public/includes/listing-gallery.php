<?php

declare(strict_types=1);

/**
 * Разметка галереи объекта: LCP — первое фото, остальные — отложенная загрузка.
 *
 * @param array{first: string, raw: list<string>, total: int} $bundle
 */
function site_render_listing_gallery(array $bundle, string $title): void
{
    $raw = $bundle['raw'];
    $total = max($bundle['total'], count($raw));
    $firstDisplay = $bundle['first'];
    ?>
    <div class="listing-gallery">
        <div class="listing-gallery__track" data-gallery-track>
            <?php if ($total > 0) { ?>
                <?php foreach ($raw as $idx => $rawUrl) {
                    if (!is_string($rawUrl) || trim($rawUrl) === '') {
                        continue;
                    }
                    ?>
                    <figure class="listing-gallery__slide" data-gallery-slide>
                        <?php if ($idx === 0 && $firstDisplay !== '') {
                            echo site_crm_photo_img(
                                $firstDisplay,
                                $title,
                                'listing-gallery__img',
                                'fetchpriority="high"',
                                'gallery'
                            );
                        } else {
                            ?>
                            <img
                                class="listing-gallery__img"
                                alt="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                                width="960"
                                height="600"
                                decoding="async"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                                data-gallery-lazy-raw="<?php echo htmlspecialchars(trim($rawUrl), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        <?php } ?>
                    </figure>
                <?php } ?>
            <?php } else { ?>
                <div class="listing-gallery__placeholder" aria-label="Фото отсутствует"></div>
            <?php } ?>
        </div>

        <button class="listing-gallery__nav listing-gallery__nav--prev" type="button" data-gallery-prev aria-label="Предыдущее фото">
            <span aria-hidden="true">‹</span>
        </button>
        <button class="listing-gallery__nav listing-gallery__nav--next" type="button" data-gallery-next aria-label="Следующее фото">
            <span aria-hidden="true">›</span>
        </button>

        <div class="listing-gallery__counter" data-gallery-counter aria-live="polite"></div>
    </div>
    <?php
}
