<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function site_fmt_rub(?string $raw): string
{
    if ($raw === null || $raw === '') {
        return '—';
    }
    $n = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $raw));
    if ($n <= 0) {
        return '—';
    }

    return number_format((int) round($n), 0, '.', ' ') . ' ₽';
}

function site_fmt_m2(?float $areaTotal, ?string $priceRaw): ?string
{
    if ($areaTotal === null || $areaTotal <= 0) {
        return null;
    }
    if ($priceRaw === null || $priceRaw === '') {
        return null;
    }
    $price = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $priceRaw));
    if ($price <= 0) {
        return null;
    }
    $v = (int) round($price / $areaTotal);

    return number_format($v, 0, '.', ' ') . ' ₽/м²';
}

function site_object_meta_label(?string $objectType, ?int $rooms): string
{
    $t = $objectType ? trim($objectType) : '';
    if ($t === 'flat') {
        if ($rooms !== null && $rooms > 0) {
            return $rooms . '-комн. кв.';
        }

        return 'Квартира';
    }
    if ($t === 'house') {
        return 'Дом';
    }
    if ($t === 'plot' || $t === 'land') {
        return 'Участок';
    }
    if ($t === 'commercial') {
        return 'Коммерция';
    }

    return 'Объект';
}

function site_tone_from_id(string $id): int
{
    return ((int) (crc32($id) % 8)) + 1;
}

/**
 * @return array{items: list<array<string, mixed>>, total: ?int, error: ?string}
 */
/**
 * Локальные кадры из public/assets/hero/ (hero-bg.jpg, hero-bg-2.jpeg и т.д.).
 *
 * @return list<array{src: string, alt: string}>
 */
function site_hero_slides_static(): array
{
    $dir = dirname(__DIR__) . '/assets/hero';
    if (!is_dir($dir)) {
        return [];
    }

    $bases = [
        ['base' => 'hero-bg', 'alt' => 'Недвижимость'],
        ['base' => 'hero-bg-2', 'alt' => 'Недвижимость'],
        ['base' => 'hero-bg-3', 'alt' => 'Недвижимость'],
    ];
    $exts = ['jpg', 'jpeg', 'webp', 'JPG', 'JPEG', 'WEBP'];

    $slides = [];
    foreach ($bases as $item) {
        foreach ($exts as $ext) {
            $abs = $dir . '/' . $item['base'] . '.' . $ext;
            if (!is_file($abs)) {
                continue;
            }
            $slides[] = [
                'src' => '/assets/hero/' . $item['base'] . '.' . $ext,
                'alt' => $item['alt'],
            ];
            break;
        }
    }

    return $slides;
}

/**
 * Источник слайдов hero: static (файлы в assets/hero), crm или auto (сначала static).
 *
 * @return list<array{src: string, alt: string}>
 */
function site_hero_slides_resolve(int $max = 5): array
{
    $mode = strtolower(trim(site_env('HERO_SLIDES_SOURCE', 'auto')));
    if ($mode === 'crm') {
        $crm = site_hero_slides_from_crm($max);

        return count($crm) > 0 ? $crm : site_hero_slides_static();
    }
    if ($mode === 'static') {
        $static = site_hero_slides_static();

        return count($static) > 0 ? $static : site_hero_slides_from_crm($max);
    }

    $static = site_hero_slides_static();
    if (count($static) > 0) {
        return $static;
    }

    return site_hero_slides_from_crm($max);
}

/**
 * Слайды для hero на главной: обложки активных объектов CRM (до $max штук).
 *
 * @return list<array{src: string, alt: string}>
 */
function site_hero_slides_from_crm(int $max = 5): array
{
    $crm = site_crm_fetch_listings(max(1, min($max, 8)), 0);
    $items = $crm['items'];
    if ($crm['error'] !== null || count($items) === 0) {
        return [];
    }

    $slides = [];
    $seen = [];
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }
        $coverRaw = isset($row['coverPhoto']) ? trim((string) $row['coverPhoto']) : '';
        if ($coverRaw === '') {
            continue;
        }
        $src = site_crm_photo_src($coverRaw);
        if ($src === '' || isset($seen[$src])) {
            continue;
        }
        $seen[$src] = true;
        $title = isset($row['title']) ? trim((string) $row['title']) : '';
        $slides[] = [
            'src' => $src,
            'alt' => $title !== '' ? $title : 'Объект недвижимости',
        ];
        if (count($slides) >= $max) {
            break;
        }
    }

    if (count($slides) < 2 && count($items) > 0) {
        $firstId = isset($items[0]['id']) ? trim((string) $items[0]['id']) : '';
        if ($firstId !== '') {
            $detail = site_http_get_json(site_crm_listings_url($firstId), 20);
            $title = isset($items[0]['title']) ? trim((string) $items[0]['title']) : 'Объект недвижимости';
            $photos = (isset($detail['photos']) && is_array($detail['photos'])) ? $detail['photos'] : [];
            foreach ($photos as $p) {
                if (!is_string($p)) {
                    continue;
                }
                $src = site_crm_photo_src(trim($p));
                if ($src === '' || isset($seen[$src])) {
                    continue;
                }
                $seen[$src] = true;
                $slides[] = ['src' => $src, 'alt' => $title];
                if (count($slides) >= $max) {
                    break;
                }
            }
        }
    }

    return $slides;
}

function site_fmt_area_short(?float $value): ?string
{
    if ($value === null || $value <= 0) {
        return null;
    }

    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
}

function site_listing_card_title(?int $rooms, ?float $areaTotal, string $fallback): string
{
    $area = site_fmt_area_short($areaTotal);
    if ($rooms !== null && $rooms > 0 && $area !== null) {
        return $rooms . '-комнатная квартира ' . $area . ' м²';
    }
    if ($area !== null) {
        return 'Квартира ' . $area . ' м²';
    }

    return $fallback !== '' ? $fallback : 'Объект';
}

function site_deal_line_public_label(?string $raw): string
{
    $s = trim((string) $raw);
    if ($s === '') {
        return 'прямая продажа';
    }
    if (mb_stripos($s, 'продаж', 0, 'UTF-8') !== false) {
        return 'прямая продажа';
    }

    return mb_strtolower($s, 'UTF-8');
}

function site_listing_floor_label(?string $floor, ?int $floorTotal): ?string
{
    $f = trim((string) $floor);
    if ($f === '') {
        return null;
    }
    if ($floorTotal !== null && $floorTotal > 0) {
        return $f . '/' . $floorTotal . ' этаж';
    }

    return $f . ' этаж';
}

function site_listing_building_label(?int $yearBuilt, ?string $residentialComplex): ?string
{
    $jk = trim((string) $residentialComplex);
    if ($jk !== '') {
        return $jk;
    }
    if ($yearBuilt !== null && $yearBuilt > 0) {
        return 'дом ' . $yearBuilt . ' г.';
    }

    return null;
}

function site_listing_address_line(?string $city, ?string $address, string $title): string
{
    $c = trim((string) $city);
    $a = trim((string) $address);
    if ($c !== '' && $a !== '') {
        return $c . ', ' . $a;
    }
    if ($a !== '') {
        return $a;
    }
    if ($c !== '') {
        return $c;
    }

    return $title;
}

function site_listing_updated_label(?string $iso): string
{
    $s = trim((string) $iso);
    if ($s === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($s);

        return $dt->format('d.m.Y');
    } catch (Throwable) {
        return '—';
    }
}

function site_listing_public_id(string $id): string
{
    $id = trim($id);
    if ($id === '') {
        return '—';
    }
    if (strlen($id) > 12) {
        return substr($id, 0, 8);
    }

    return $id;
}

function site_mask_phone_display(?string $raw): string
{
    $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';
    if (strlen($digits) < 10) {
        return SITE_PHONE_DISPLAY;
    }
    if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) {
        $d = substr($digits, -10);

        return '+7 (' . substr($d, 0, 3) . ') ' . substr($d, 3, 3) . '-' . substr($d, 6, 2) . '-' . substr($d, 8, 2);
    }

    return SITE_PHONE_DISPLAY;
}

function site_mask_phone_tel(?string $raw): string
{
    $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';
    if (strlen($digits) >= 10) {
        if (strlen($digits) === 10) {
            return '+7' . $digits;
        }
        if ($digits[0] === '8') {
            return '+7' . substr($digits, 1);
        }

        return '+' . ltrim($digits, '+');
    }

    return SITE_PHONE_TEL;
}

function site_excerpt_text(?string $text, int $maxLen = 280): string
{
    $s = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');
    if ($s === '') {
        return '';
    }
    if (mb_strlen($s, 'UTF-8') <= $maxLen) {
        return $s;
    }

    return rtrim(mb_substr($s, 0, $maxLen - 1, 'UTF-8')) . '…';
}

/**
 * @param array<string, mixed> $row
 */
function site_render_catalog_listing_card(array $row): void
{
    $id = isset($row['id']) ? trim((string) $row['id']) : '';
    if ($id === '') {
        return;
    }

    $href = '/catalog/object/?id=' . rawurlencode($id);
    $titleRaw = isset($row['title']) ? (string) $row['title'] : 'Объект';
    $rooms = isset($row['rooms']) && is_numeric($row['rooms']) ? (int) $row['rooms'] : null;
    $areaTotal = isset($row['areaTotal']) && is_numeric($row['areaTotal']) ? (float) $row['areaTotal'] : null;
    $cardTitle = site_listing_card_title($rooms, $areaTotal, $titleRaw);
    $dealLine = site_deal_line_public_label(isset($row['dealLineValue']) ? (string) $row['dealLineValue'] : null);
    $areaLiving = isset($row['areaLiving']) && is_numeric($row['areaLiving']) ? (float) $row['areaLiving'] : null;
    $areaKitchen = isset($row['areaKitchen']) && is_numeric($row['areaKitchen']) ? (float) $row['areaKitchen'] : null;
    $floor = isset($row['floor']) ? (string) $row['floor'] : null;
    $floorTotal = isset($row['floorTotal']) && is_numeric($row['floorTotal']) ? (int) $row['floorTotal'] : null;
    $yearBuilt = isset($row['yearBuilt']) && is_numeric($row['yearBuilt']) ? (int) $row['yearBuilt'] : null;
    $jk = isset($row['residentialComplex']) ? (string) $row['residentialComplex'] : null;
    $city = isset($row['city']) ? (string) $row['city'] : null;
    $address = isset($row['address']) ? (string) $row['address'] : null;
    $district = isset($row['districtValue']) ? trim((string) $row['districtValue']) : '';
    $priceRaw = isset($row['price']) ? (string) $row['price'] : null;
    $description = isset($row['description']) ? (string) $row['description'] : null;
    $contactPhone = isset($row['contactPhone']) ? (string) $row['contactPhone'] : null;
    $updatedAt = isset($row['updatedAt']) ? (string) $row['updatedAt'] : null;
    $photosCount = isset($row['photosCount']) && is_numeric($row['photosCount']) ? (int) $row['photosCount'] : 0;

    $photoUrls = [];
    if (isset($row['photos']) && is_array($row['photos'])) {
        foreach ($row['photos'] as $p) {
            if (!is_string($p)) {
                continue;
            }
            $src = site_crm_photo_src(trim($p));
            if ($src !== '') {
                $photoUrls[] = $src;
            }
        }
    }
    if (count($photoUrls) === 0) {
        $coverRaw = isset($row['coverPhoto']) ? trim((string) $row['coverPhoto']) : '';
        if ($coverRaw !== '') {
            $cover = site_crm_photo_src($coverRaw);
            if ($cover !== '') {
                $photoUrls[] = $cover;
            }
        }
    }
    $photosJson = htmlspecialchars(json_encode($photoUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]', ENT_QUOTES, 'UTF-8');

    $priceText = site_fmt_rub($priceRaw);
    $priceM2 = site_fmt_m2($areaTotal, $priceRaw);
    $floorText = site_listing_floor_label($floor, $floorTotal);
    $buildingText = site_listing_building_label($yearBuilt, $jk);
    $addressLine = site_listing_address_line($city, $address, $titleRaw);
    $updatedLabel = site_listing_updated_label($updatedAt);
    $publicId = site_listing_public_id($id);
    $phoneDisplay = site_mask_phone_display($contactPhone);
    $phoneTel = site_mask_phone_tel($contactPhone);
    $excerpt = site_excerpt_text($description);
    $tone = site_tone_from_id($id);
    $totalPhotos = max(count($photoUrls), $photosCount, 1);
    ?>
    <li class="catalog-list__item">
        <article class="listing-card" data-listing-card data-listing-id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <header class="listing-card__bar">
                <span class="listing-card__updated">Дата изменения: <?php echo htmlspecialchars($updatedLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="listing-card__id">id <?php echo htmlspecialchars($publicId, ENT_QUOTES, 'UTF-8'); ?></span>
            </header>
            <div class="listing-card__main">
                <div
                    class="listing-card__media listing-card__media--tone-<?php echo (int) $tone; ?>"
                    data-listing-gallery
                    data-photos="<?php echo $photosJson; ?>"
                >
                    <?php if (count($photoUrls) > 0) { ?>
                        <img
                            class="listing-card__photo"
                            src="<?php echo htmlspecialchars($photoUrls[0], ENT_QUOTES, 'UTF-8'); ?>"
                            alt=""
                            loading="lazy"
                            decoding="async"
                            data-listing-gallery-img
                        >
                    <?php } ?>
                    <span class="listing-card__count" data-listing-gallery-count>1/<?php echo (int) $totalPhotos; ?></span>
                    <?php if ($totalPhotos > 1) { ?>
                        <button type="button" class="listing-card__nav listing-card__nav--prev" data-listing-gallery-prev aria-label="Предыдущее фото"></button>
                        <button type="button" class="listing-card__nav listing-card__nav--next" data-listing-gallery-next aria-label="Следующее фото"></button>
                    <?php } ?>
                    <a class="listing-card__media-link" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Открыть объект"></a>
                </div>
                <div class="listing-card__info">
                    <h3 class="listing-card__title">
                        <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8'); ?></a>
                    </h3>
                    <p class="listing-card__deal"><?php echo htmlspecialchars($dealLine, ENT_QUOTES, 'UTF-8'); ?></p>
                    <ul class="listing-card__specs">
                        <?php
                        $living = site_fmt_area_short($areaLiving);
                        $kitchen = site_fmt_area_short($areaKitchen);
                        if ($living !== null) {
                            echo '<li><span class="listing-card__spec-val">' . htmlspecialchars($living, ENT_QUOTES, 'UTF-8') . '</span> жилая</li>';
                        }
                        if ($kitchen !== null) {
                            echo '<li><span class="listing-card__spec-val">' . htmlspecialchars($kitchen, ENT_QUOTES, 'UTF-8') . '</span> кухня</li>';
                        }
                        ?>
                    </ul>
                    <?php if ($floorText !== null || $buildingText !== null) { ?>
                        <p class="listing-card__building">
                            <?php if ($floorText !== null) { ?>
                                <span><?php echo htmlspecialchars($floorText, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php } ?>
                            <?php if ($floorText !== null && $buildingText !== null) { ?>
                                <span class="listing-card__sep" aria-hidden="true">·</span>
                            <?php } ?>
                            <?php if ($buildingText !== null) { ?>
                                <span><?php echo htmlspecialchars($buildingText, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php } ?>
                        </p>
                    <?php } ?>
                    <p class="listing-card__address"><?php echo htmlspecialchars($addressLine, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($district !== '') { ?>
                        <p class="listing-card__district"><?php echo htmlspecialchars($district, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                    <a class="listing-card__phone" href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $phoneTel) ?? $phoneTel, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="listing-card__phone-icon" aria-hidden="true"></span>
                        <?php echo htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
                <div class="listing-card__aside">
                    <a class="listing-card__shop" href="/catalog/">Магазин квартир</a>
                    <p class="listing-card__price"><?php echo htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($priceM2 !== null) { ?>
                        <p class="listing-card__price-m2"><?php echo htmlspecialchars($priceM2, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                    <?php if ($excerpt !== '') { ?>
                        <p class="listing-card__desc"><?php echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                </div>
            </div>
            <footer class="listing-card__footer">
                <a class="listing-card__more" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">подробнее →</a>
                <button type="button" class="listing-card__fav" data-listing-fav aria-pressed="false">
                    <span class="listing-card__fav-label">добавить в избранное</span>
                    <svg class="listing-card__fav-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="none" stroke="currentColor" stroke-width="1.8" d="M12 21s-7-4.6-9.5-9C.5 7.5 3.4 4.5 7 4.5c2 0 3.7 1.1 5 2.7 1.3-1.6 3-2.7 5-2.7 3.6 0 6.5 3 4.5 7.5C19 16.4 12 21 12 21Z"/>
                    </svg>
                </button>
            </footer>
        </article>
    </li>
    <?php
}

function site_crm_fetch_listings(int $limit = 24, int $offset = 0): array
{
    $crm = site_http_get_json(site_crm_listings_url() . '?' . http_build_query([
        'limit' => $limit,
        'offset' => $offset,
    ]), 25);

    $items = (isset($crm['items']) && is_array($crm['items'])) ? $crm['items'] : [];
    $total = (isset($crm['total']) && is_numeric($crm['total'])) ? (int) $crm['total'] : null;
    $error = isset($crm['_error']) ? (string) $crm['_error'] : null;

    return ['items' => $items, 'total' => $total, 'error' => $error];
}
