<?php

declare(strict_types=1);

/**
 * Галерея объекта: крупный слайдер, стрелки, полоса миниатюр снизу.
 *
 * @param array{first: string, raw: list<string>, total: int} $bundle
 */
function site_render_listing_gallery(array $bundle, string $title): void
{
    $raw = $bundle['raw'];
    $total = max($bundle['total'], count($raw));
    $firstDisplay = $bundle['first'];
    $alt = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="listing-gallery" data-gallery>
        <div class="listing-gallery__viewport">
            <div class="listing-gallery__stage" data-gallery-stage>
                <?php if ($total > 0) { ?>
                    <?php foreach ($raw as $idx => $rawUrl) {
                        if (!is_string($rawUrl) || trim($rawUrl) === '') {
                            continue;
                        }
                        $active = $idx === 0 ? ' is-active' : '';
                        ?>
                        <figure
                            class="listing-gallery__slide<?php echo $active; ?>"
                            data-gallery-slide
                            data-gallery-index="<?php echo (int) $idx; ?>"
                            <?php echo $idx === 0 ? '' : ' hidden'; ?>
                        >
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
                                    alt="<?php echo $alt; ?>"
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

            <?php if ($total > 1) { ?>
                <button class="listing-gallery__nav listing-gallery__nav--prev" type="button" data-gallery-prev aria-label="Предыдущее фото">
                    <span aria-hidden="true">‹</span>
                </button>
                <button class="listing-gallery__nav listing-gallery__nav--next" type="button" data-gallery-next aria-label="Следующее фото">
                    <span aria-hidden="true">›</span>
                </button>
                <div class="listing-gallery__counter" data-gallery-counter aria-live="polite">1/<?php echo (int) $total; ?></div>
            <?php } ?>
        </div>

        <?php if ($total > 1) { ?>
            <div class="listing-gallery__thumbs" data-gallery-thumbs role="tablist" aria-label="Миниатюры фото">
                <?php foreach ($raw as $idx => $rawUrl) {
                    if (!is_string($rawUrl) || trim($rawUrl) === '') {
                        continue;
                    }
                    $thumbActive = $idx === 0 ? ' is-active' : '';
                    $thumbSrc = '';
                    if ($idx === 0 && $firstDisplay !== '') {
                        $resolved = site_crm_photo_src($rawUrl);
                        if ($resolved !== '') {
                            $thumbSrc = site_crm_photo_display_src($resolved, 'thumb');
                        }
                    }
                    ?>
                    <button
                        type="button"
                        class="listing-gallery__thumb<?php echo $thumbActive; ?>"
                        data-gallery-thumb="<?php echo (int) $idx; ?>"
                        role="tab"
                        aria-selected="<?php echo $idx === 0 ? 'true' : 'false'; ?>"
                        aria-label="Фото <?php echo (int) ($idx + 1); ?>"
                    >
                        <?php if ($thumbSrc !== '') { ?>
                            <img
                                class="listing-gallery__thumb-img"
                                src="<?php echo htmlspecialchars($thumbSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                alt=""
                                width="80"
                                height="60"
                                decoding="async"
                                loading="<?php echo $idx < 6 ? 'eager' : 'lazy'; ?>"
                                referrerpolicy="no-referrer"
                            >
                        <?php } else { ?>
                            <img
                                class="listing-gallery__thumb-img"
                                alt=""
                                width="80"
                                height="60"
                                decoding="async"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                                data-gallery-thumb-lazy-raw="<?php echo htmlspecialchars(trim($rawUrl), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        <?php } ?>
                    </button>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <?php
}
