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
                            <button type="button" class="listing-gallery__zoom-hit" data-gallery-open aria-label="Открыть фото на весь экран">
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
                            </button>
                            <span class="listing-gallery__zoom-hint" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14zM13 10h-2v2H9v-2H7V8h2V6h2v2h2v2z"/></svg>
                            </span>
                        </figure>
                    <?php } ?>
                <?php } else { ?>
                    <div class="listing-gallery__placeholder" aria-label="Фото отсутствует"></div>
                <?php } ?>
            </div>

            <?php if ($total > 1) { ?>
                <button class="listing-gallery__nav listing-gallery__nav--prev" type="button" data-gallery-prev aria-label="Предыдущее фото">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M14 6l-6 6 6 6"/></svg>
                </button>
                <button class="listing-gallery__nav listing-gallery__nav--next" type="button" data-gallery-next aria-label="Следующее фото">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M10 6l6 6-6 6"/></svg>
                </button>
                <div class="listing-gallery__counter" data-gallery-counter aria-live="polite">1/<?php echo (int) $total; ?></div>
            <?php } elseif ($total === 1) { ?>
                <div class="listing-gallery__counter" data-gallery-counter aria-live="polite">1/1</div>
            <?php } ?>
        </div>

        <div class="listing-gallery__lightbox" data-gallery-lightbox hidden aria-hidden="true">
            <div class="listing-gallery__lightbox-backdrop" data-gallery-lightbox-close></div>
            <div class="listing-gallery__lightbox-inner" role="dialog" aria-modal="true" aria-label="Просмотр фото">
                <button type="button" class="listing-gallery__lightbox-close" data-gallery-lightbox-close aria-label="Закрыть">
                    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
                <?php if ($total > 1) { ?>
                    <button type="button" class="listing-gallery__lightbox-nav listing-gallery__lightbox-nav--prev" data-gallery-lightbox-prev aria-label="Предыдущее фото">
                        <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M14 6l-6 6 6 6"/></svg>
                    </button>
                    <button type="button" class="listing-gallery__lightbox-nav listing-gallery__lightbox-nav--next" data-gallery-lightbox-next aria-label="Следующее фото">
                        <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M10 6l6 6-6 6"/></svg>
                    </button>
                <?php } ?>
                <div class="listing-gallery__lightbox-stage" data-gallery-lightbox-stage>
                    <img class="listing-gallery__lightbox-img" data-gallery-lightbox-img alt="<?php echo $alt; ?>" decoding="async" referrerpolicy="no-referrer">
                </div>
                <p class="listing-gallery__lightbox-hint" data-gallery-lightbox-hint>Нажмите на фото, чтобы увеличить · Esc — закрыть</p>
                <div class="listing-gallery__lightbox-counter" data-gallery-lightbox-counter></div>
            </div>
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
