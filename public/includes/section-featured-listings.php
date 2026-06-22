<?php

declare(strict_types=1);

require_once __DIR__ . '/crm-listing-helpers.php';

$featured = site_crm_fetch_featured_listings(8);
$featuredItems = $featured['items'];
$featuredError = $featured['error'];
$useCrm = $featuredError === null && count($featuredItems) > 0;

?>
<section class="featured" aria-labelledby="featured-heading">
    <div class="container">
        <header class="featured__header">
            <div class="featured__intro">
                <h2 class="featured__title" id="featured-heading">Лучшие предложения</h2>
                <?php if ($useCrm) { ?>
                    <p class="featured__lead">Отобранные объекты со скидкой и лучшие предложения агентства. Полный список — в каталоге.</p>
                <?php } elseif ($featuredError !== null) { ?>
                    <p class="featured__lead">Сейчас не удалось загрузить объекты из CRM. Смотрите <a href="/catalog/">каталог</a> или попробуйте позже.</p>
                <?php } else { ?>
                    <p class="featured__lead">Пока нет объектов в блоке «Лучшие предложения». Отметьте нужные в CRM или смотрите <a href="/catalog/">весь каталог</a>.</p>
                <?php } ?>
            </div>
            <a class="featured__to-catalog" href="/catalog/">Весь каталог</a>
        </header>

        <?php if ($useCrm) { ?>
        <ul class="featured__grid">
            <?php foreach ($featuredItems as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = isset($row['id']) ? (string) $row['id'] : '';
                if ($id === '') {
                    continue;
                }
                $titleRaw = isset($row['title']) ? (string) $row['title'] : 'Объект';
                $rooms = isset($row['rooms']) && is_numeric($row['rooms']) ? (int) $row['rooms'] : null;
                $areaTotal = isset($row['areaTotal']) && is_numeric($row['areaTotal']) ? (float) $row['areaTotal'] : null;
                $floor = isset($row['floor']) ? (string) $row['floor'] : '—';
                $priceRaw = isset($row['price']) ? (string) $row['price'] : null;
                $priceOldRaw = isset($row['priceOld']) ? (string) $row['priceOld'] : null;
                $objectType = isset($row['objectTypeValue']) ? (string) $row['objectTypeValue'] : null;
                $coverPhotoRaw = isset($row['coverPhoto']) ? (string) $row['coverPhoto'] : '';
                $coverPhoto = $coverPhotoRaw !== '' ? site_crm_photo_src($coverPhotoRaw) : '';
                $tone = site_tone_from_id($id);
                $cardTitle = site_listing_card_title($objectType, $rooms, $areaTotal, $titleRaw);
                $addressLine = site_listing_address_line($row);
                $meta = site_object_meta_label($objectType, $rooms);
                $areaText = $areaTotal ? rtrim(rtrim(number_format($areaTotal, 2, '.', ''), '0'), '.') . ' м²' : '—';
                $href = '/catalog/object/?id=' . rawurlencode($id);
                $hasDiscount = site_listing_has_discount($priceOldRaw, $priceRaw);
                $discountPct = site_listing_discount_percent($priceOldRaw, $priceRaw);
                $badgeClass = $hasDiscount ? 'featured-card__badge--sale' : 'featured-card__badge--new';
                $badgeText = $hasDiscount
                    ? ($discountPct !== null ? '−' . $discountPct . '%' : 'Скидка')
                    : 'Топ';
                ?>
                <li class="featured__cell">
                    <article class="featured-card">
                        <a class="featured-card__link" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="featured-card__media featured-card__media--tone-<?php echo (int) $tone; ?>" aria-hidden="true">
                                <?php if ($coverPhoto !== '') {
                                    echo site_crm_photo_img($coverPhoto, $cardTitle, 'featured-card__photo', '', 'featured');
                                } ?>
                                <span class="featured-card__badge <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="featured-card__body">
                                <h3 class="featured-card__title"><?php echo htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="featured-card__address"><?php echo htmlspecialchars($addressLine !== '' ? $addressLine : $titleRaw, ENT_QUOTES, 'UTF-8'); ?></p>
                                <ul class="featured-card__meta">
                                    <li><?php echo htmlspecialchars($meta, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><?php echo htmlspecialchars($areaText, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><?php echo htmlspecialchars($floor, ENT_QUOTES, 'UTF-8'); ?></li>
                                </ul>
                                <div class="featured-card__pricing">
                                    <?php if ($hasDiscount && $priceOldRaw !== null) { ?>
                                        <p class="featured-card__price-old"><?php echo htmlspecialchars(site_fmt_rub($priceOldRaw), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php } ?>
                                    <p class="featured-card__price"><?php echo htmlspecialchars(site_fmt_rub($priceRaw), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                        </a>
                    </article>
                </li>
            <?php } ?>
        </ul>
        <?php } ?>
    </div>
</section>
