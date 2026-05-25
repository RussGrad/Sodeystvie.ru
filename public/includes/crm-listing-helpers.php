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

function site_listing_card_title(
    ?string $objectType,
    ?int $rooms,
    ?float $areaTotal,
    string $fallback,
): string {
    $t = trim((string) $objectType);
    $area = site_fmt_area_short($areaTotal);
    $areaSuffix = $area !== null ? ' ' . $area . ' м²' : '';

    if ($t === 'house') {
        return 'Дом' . $areaSuffix;
    }
    if ($t === 'plot' || $t === 'land') {
        return 'Участок' . $areaSuffix;
    }
    if ($t === 'commercial') {
        return 'Коммерческая недвижимость' . $areaSuffix;
    }
    if ($rooms !== null && $rooms > 0 && $area !== null) {
        return $rooms . '-комнатная квартира ' . $area . ' м²';
    }
    if ($area !== null) {
        return 'Квартира ' . $area . ' м²';
    }

    return $fallback !== '' ? $fallback : 'Объект';
}

/**
 * Текст описания из CRM (кэш + fallback на полную карточку).
 *
 * @return non-empty-string|null
 */
function site_crm_fetch_listing_description(string $id): ?string
{
    $id = trim($id);
    if ($id === '' || !site_validate_crm_object_id($id)) {
        return null;
    }

    $descUrl = site_crm_listings_url($id) . '/description';
    $data = site_http_get_json_cached($descUrl, 6, 900);
    if (is_array($data) && !isset($data['_error'])) {
        $desc = isset($data['description']) ? trim((string) $data['description']) : '';
        if ($desc !== '') {
            return $desc;
        }
    }

    $detail = site_http_get_json_cached(site_crm_listings_url($id), 8, 900);
    if (!is_array($detail) || isset($detail['_error'])) {
        return null;
    }
    $desc = isset($detail['description']) ? trim((string) $detail['description']) : '';

    return $desc !== '' ? $desc : null;
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

/**
 * Адрес для карточки каталога — только из CRM (addressLine / city + address), без подстановки title.
 *
 * @param array<string, mixed> $row
 */
function site_listing_address_line(array $row): string
{
    if (isset($row['addressLine']) && is_string($row['addressLine'])) {
        $line = trim($row['addressLine']);
        if ($line !== '') {
            return $line;
        }
    }

    $city = isset($row['city']) ? trim((string) $row['city']) : '';
    $address = isset($row['address']) ? trim((string) $row['address']) : '';
    if ($address !== '') {
        if ($city !== '' && mb_stripos($address, $city, 0, 'UTF-8') === false) {
            return $city . ', ' . $address;
        }

        return $address;
    }

    return $city;
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

/**
 * Догрузка карточки из GET /api/public/listings/:id (на проде список часто без photos[] и description).
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function site_crm_listing_enrich_row(array $row): array
{
    $id = trim((string) ($row['id'] ?? ''));
    if ($id === '') {
        return $row;
    }

    $photosCount = isset($row['photosCount']) && is_numeric($row['photosCount'])
        ? (int) $row['photosCount']
        : 0;
    $hasPhotosList = isset($row['photos']) && is_array($row['photos']) && count($row['photos']) > 0;
    $needPhotos = $photosCount > 0 && !$hasPhotosList;
    $needDesc = trim((string) ($row['description'] ?? '')) === '';
    $needAddress = trim((string) ($row['address'] ?? '')) === ''
        && trim((string) ($row['addressLine'] ?? '')) === '';

    if (!$needPhotos && !$needDesc && !$needAddress) {
        return $row;
    }

    // Один запрос на объект (кэш 15 мин), без отдельного /description (на старом API — 404).
    $detail = site_http_get_json_cached(site_crm_listings_url($id), 8, 900);
    if (!is_array($detail) || isset($detail['_error'])) {
        return $row;
    }

    if ($needDesc && isset($detail['description']) && is_string($detail['description'])) {
        $desc = trim($detail['description']);
        if ($desc !== '') {
            $row['description'] = $desc;
        }
    }
    if ($needAddress) {
        foreach (['addressLine', 'address', 'city'] as $key) {
            if (isset($detail[$key]) && is_string($detail[$key]) && trim($detail[$key]) !== '') {
                $row[$key] = trim($detail[$key]);
            }
        }
    }
    if ($needPhotos) {
        if (isset($detail['photos']) && is_array($detail['photos']) && count($detail['photos']) > 0) {
            $row['photos'] = $detail['photos'];
        }
        if (isset($detail['coverPhoto']) && is_string($detail['coverPhoto']) && trim($detail['coverPhoto']) !== '') {
            $row['coverPhoto'] = $detail['coverPhoto'];
        }
    }

    return $row;
}

/**
 * @param array<string, mixed> $row
 * @return list<string>
 */
function site_crm_listing_raw_photo_urls(array $row, int $max = 30): array
{
    $max = max(1, min(30, $max));
    $raw = [];
    if (isset($row['photos']) && is_array($row['photos'])) {
        foreach ($row['photos'] as $p) {
            if (!is_string($p)) {
                continue;
            }
            $u = trim($p);
            if ($u !== '') {
                $raw[] = $u;
            }
            if (count($raw) >= $max) {
                break;
            }
        }
    }
    if (count($raw) === 0) {
        $cover = isset($row['coverPhoto']) ? trim((string) $row['coverPhoto']) : '';
        if ($cover !== '') {
            $raw[] = $cover;
        }
    }

    return $raw;
}

/**
 * Для каталога: на сервере резолвим только 1–2 фото (обложка), остальное — в браузере через /api/crm-resolve-photo.php.
 *
 * @param array<string, mixed> $row
 * @return array{resolved: list<string>, raw: list<string>}
 */
function site_crm_listing_photo_bundle(array $row, int $resolveOnServer = 2): array
{
    $raw = site_crm_listing_raw_photo_urls($row, 30);
    $resolved = [];
    $limit = max(1, min(3, $resolveOnServer));
    foreach ($raw as $i => $u) {
        if ($i >= $limit) {
            break;
        }
        $src = site_crm_photo_src($u);
        if ($src !== '') {
            $resolved[] = $src;
        }
    }
    if (count($resolved) === 0 && count($raw) > 0) {
        $src = site_crm_photo_src($raw[0]);
        if ($src !== '') {
            $resolved[] = $src;
        }
    }

    return ['resolved' => $resolved, 'raw' => $raw];
}

/**
 * Все фото для страницы объекта (кэш URL в рамках запроса).
 *
 * @param array<string, mixed> $row
 * @return list<string>
 */
function site_crm_listing_resolved_photos(array $row, int $max = 12): array
{
    $max = max(1, min(30, $max));
    $urls = [];
    foreach (site_crm_listing_raw_photo_urls($row, $max) as $u) {
        $src = site_crm_photo_src($u);
        if ($src !== '') {
            $urls[] = $src;
        }
    }

    return $urls;
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

function site_listing_description_html(?string $text, int $maxLen = 600): string
{
    $s = trim((string) $text);
    if ($s === '') {
        return '';
    }
    $s = preg_replace("/\r\n?/", "\n", $s) ?? $s;
    $s = preg_replace("/[ \t]+/u", ' ', $s) ?? $s;
    $s = preg_replace("/\n{3,}/", "\n\n", $s) ?? $s;
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = rtrim(mb_substr($s, 0, $maxLen - 1, 'UTF-8')) . '…';
    }

    return nl2br(htmlspecialchars($s, ENT_QUOTES, 'UTF-8'), false);
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
    $objectType = isset($row['objectTypeValue']) ? (string) $row['objectTypeValue'] : null;
    $cardTitle = site_listing_card_title($objectType, $rooms, $areaTotal, $titleRaw);
    $dealLine = site_deal_line_public_label(isset($row['dealLineValue']) ? (string) $row['dealLineValue'] : null);
    $areaLiving = isset($row['areaLiving']) && is_numeric($row['areaLiving']) ? (float) $row['areaLiving'] : null;
    $areaKitchen = isset($row['areaKitchen']) && is_numeric($row['areaKitchen']) ? (float) $row['areaKitchen'] : null;
    $floor = isset($row['floor']) ? (string) $row['floor'] : null;
    $floorTotal = isset($row['floorTotal']) && is_numeric($row['floorTotal']) ? (int) $row['floorTotal'] : null;
    $yearBuilt = isset($row['yearBuilt']) && is_numeric($row['yearBuilt']) ? (int) $row['yearBuilt'] : null;
    $jk = isset($row['residentialComplex']) ? (string) $row['residentialComplex'] : null;
    $district = isset($row['districtValue']) ? trim((string) $row['districtValue']) : '';
    $priceRaw = isset($row['price']) ? (string) $row['price'] : null;
    $contactPhone = isset($row['contactPhone']) ? (string) $row['contactPhone'] : null;
    $photosCount = isset($row['photosCount']) && is_numeric($row['photosCount']) ? (int) $row['photosCount'] : 0;

    $photoBundle = site_crm_listing_photo_bundle($row, 2);
    $photoUrls = $photoBundle['resolved'];
    $photosB64 = base64_encode(json_encode($photoUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]');
    $photosRawB64 = base64_encode(json_encode($photoBundle['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]');

    $priceText = site_fmt_rub($priceRaw);
    $priceM2 = site_fmt_m2($areaTotal, $priceRaw);
    $floorText = site_listing_floor_label($floor, $floorTotal);
    $buildingText = site_listing_building_label($yearBuilt, $jk);
    $addressLine = site_listing_address_line($row);
    $phoneDisplay = site_mask_phone_display($contactPhone);
    $phoneTel = site_mask_phone_tel($contactPhone);
    $areaLand = isset($row['areaLand']) && is_numeric($row['areaLand']) ? (float) $row['areaLand'] : null;
    $tone = site_tone_from_id($id);
    $totalPhotos = max(count($photoUrls), $photosCount, 1);

    $specParts = [];
    if ($areaTotal !== null && $areaTotal > 0) {
        $specParts[] = site_fmt_area_short($areaTotal) . ' м²';
    }
    $living = site_fmt_area_short($areaLiving);
    $kitchen = site_fmt_area_short($areaKitchen);
    if ($living !== null) {
        $specParts[] = 'жилая ' . $living;
    }
    if ($kitchen !== null) {
        $specParts[] = 'кухня ' . $kitchen;
    }
    if ($areaLand !== null && $areaLand > 0) {
        $land = site_fmt_area_short($areaLand);
        if ($land !== null) {
            $specParts[] = $land . ' сот.';
        }
    }
    if ($floorText !== null) {
        $specParts[] = $floorText;
    }
    if ($buildingText !== null) {
        $specParts[] = $buildingText;
    }
    $specLine = implode(' · ', $specParts);
    ?>
    <li class="catalog-list__item">
        <article class="listing-card listing-card--compact" data-listing-card data-listing-id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <div
                class="listing-card__media listing-card__media--tone-<?php echo (int) $tone; ?>"
                data-listing-gallery
                data-photos-b64="<?php echo htmlspecialchars($photosB64, ENT_QUOTES, 'UTF-8'); ?>"
                data-photos-raw-b64="<?php echo htmlspecialchars($photosRawB64, ENT_QUOTES, 'UTF-8'); ?>"
            >
                <?php if (count($photoUrls) > 0) {
                    echo site_crm_photo_img($photoUrls[0], $cardTitle, 'listing-card__photo', 'data-listing-gallery-img');
                } ?>
                <?php if ($totalPhotos > 1) { ?>
                    <span class="listing-card__count" data-listing-gallery-count>1/<?php echo (int) $totalPhotos; ?></span>
                    <button type="button" class="listing-card__nav listing-card__nav--prev" data-listing-gallery-prev aria-label="Предыдущее фото"></button>
                    <button type="button" class="listing-card__nav listing-card__nav--next" data-listing-gallery-next aria-label="Следующее фото"></button>
                <?php } ?>
                <a class="listing-card__media-link" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Открыть объект"></a>
            </div>
            <div class="listing-card__body">
                <h3 class="listing-card__title">
                    <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8'); ?></a>
                </h3>
                <?php if ($dealLine !== '') { ?>
                    <p class="listing-card__deal"><?php echo htmlspecialchars($dealLine, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
                <?php if ($specLine !== '') { ?>
                    <p class="listing-card__spec-line"><?php echo htmlspecialchars($specLine, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
                <?php if ($addressLine !== '') { ?>
                    <p class="listing-card__address"><?php echo htmlspecialchars($addressLine, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
                <?php if ($district !== '') { ?>
                    <p class="listing-card__district"><?php echo htmlspecialchars($district, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
            </div>
            <div class="listing-card__side">
                <p class="listing-card__price"><?php echo htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($priceM2 !== null) { ?>
                    <p class="listing-card__price-m2"><?php echo htmlspecialchars($priceM2, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php } ?>
                <div class="listing-card__actions">
                    <a class="listing-card__more" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">Подробнее</a>
                    <a class="listing-card__phone" href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $phoneTel) ?? $phoneTel, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="listing-card__phone-icon" aria-hidden="true"></span>
                        <span class="visually-hidden"><?php echo htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                    <button type="button" class="listing-card__fav" data-listing-fav aria-pressed="false" aria-label="В избранное">
                        <svg class="listing-card__fav-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="none" stroke="currentColor" stroke-width="1.8" d="M12 21s-7-4.6-9.5-9C.5 7.5 3.4 4.5 7 4.5c2 0 3.7 1.1 5 2.7 1.3-1.6 3-2.7 5-2.7 3.6 0 6.5 3 4.5 7.5C19 16.4 12 21 12 21Z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </article>
    </li>
    <?php
}

/**
 * @return array<string, string>
 */
function site_catalog_filters_from_request(): array
{
    $str = static function (string $key): string {
        $v = $_GET[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    };

    return [
        'q' => $str('q'),
        'region' => $str('region'),
        'city' => $str('city'),
        'objectType' => $str('type') !== '' ? $str('type') : $str('objectType'),
        'rooms' => $str('rooms'),
        'price' => $str('price'),
        'area_min' => $str('area_min'),
        'area_max' => $str('area_max'),
        'price_min' => $str('price_min'),
        'price_max' => $str('price_max'),
    ];
}

function site_catalog_region_to_city(string $region): ?string
{
    $map = [
        'irkutsk' => 'Иркутск',
        'angarsk' => 'Ангарск',
        'bratsk' => 'Братск',
        'shelekhov' => 'Шелехов',
        'moscow' => 'Москва',
        'mo' => 'Московск',
    ];

    return $map[$region] ?? null;
}

function site_listing_price_number(?string $raw): ?float
{
    if ($raw === null || trim($raw) === '') {
        return null;
    }
    $n = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $raw));

    return $n > 0 ? $n : null;
}

/**
 * @param array<string, string> $filters
 */
function site_catalog_filters_has_local(array $filters): bool
{
    foreach (['rooms', 'price', 'area_min', 'area_max', 'price_min', 'price_max'] as $key) {
        if (($filters[$key] ?? '') !== '') {
            return true;
        }
    }

    return false;
}

/**
 * @param array<string, mixed> $row
 * @param array<string, string> $filters
 */
function site_catalog_row_matches_filters(array $row, array $filters): bool
{
    $roomsFilter = $filters['rooms'] ?? '';
    if ($roomsFilter !== '') {
        $rooms = isset($row['rooms']) && is_numeric($row['rooms']) ? (int) $row['rooms'] : null;
        if ($roomsFilter === 'studio') {
            if ($rooms !== null && $rooms > 0) {
                return false;
            }
        } elseif ($roomsFilter === '4plus') {
            if ($rooms === null || $rooms < 4) {
                return false;
            }
        } else {
            $want = (int) $roomsFilter;
            if ($rooms === null || $rooms !== $want) {
                return false;
            }
        }
    }

    $area = isset($row['areaTotal']) && is_numeric($row['areaTotal']) ? (float) $row['areaTotal'] : null;
    $areaMin = ($filters['area_min'] ?? '') !== '' ? (float) $filters['area_min'] : null;
    $areaMax = ($filters['area_max'] ?? '') !== '' ? (float) $filters['area_max'] : null;
    if ($areaMin !== null && ($area === null || $area < $areaMin)) {
        return false;
    }
    if ($areaMax !== null && ($area === null || $area > $areaMax)) {
        return false;
    }

    $price = site_listing_price_number(isset($row['price']) ? (string) $row['price'] : null);
    $priceMin = ($filters['price_min'] ?? '') !== '' ? (float) $filters['price_min'] : null;
    $priceMax = ($filters['price_max'] ?? '') !== '' ? (float) $filters['price_max'] : null;
    if ($priceMin !== null && ($price === null || $price < $priceMin)) {
        return false;
    }
    if ($priceMax !== null && ($price === null || $price > $priceMax)) {
        return false;
    }

    $pricePreset = $filters['price'] ?? '';
    if ($pricePreset !== '' && $price !== null) {
        $ok = match ($pricePreset) {
            '0-3' => $price <= 3_000_000,
            '3-5' => $price > 3_000_000 && $price <= 5_000_000,
            '5-10' => $price > 5_000_000 && $price <= 10_000_000,
            '10-20' => $price > 10_000_000 && $price <= 20_000_000,
            '20+' => $price >= 20_000_000,
            default => true,
        };
        if (!$ok) {
            return false;
        }
    }

    return true;
}

/**
 * @param array<string, string> $filters
 * @return array{items: list<array<string, mixed>>, total: ?int, error: ?string, filtered: bool}
 */
function site_crm_fetch_listings_catalog(array $filters, int $displayLimit = 48): array
{
    $apiQuery = [];
    if (($filters['q'] ?? '') !== '') {
        $apiQuery['q'] = $filters['q'];
    }
    if (($filters['objectType'] ?? '') !== '') {
        $apiQuery['objectType'] = $filters['objectType'];
    }
    $city = ($filters['city'] ?? '') !== ''
        ? $filters['city']
        : (site_catalog_region_to_city($filters['region'] ?? '') ?? '');
    if ($city !== '') {
        $apiQuery['city'] = $city;
    }

    $needsLocal = site_catalog_filters_has_local($filters);
    $fetchLimit = $needsLocal ? 100 : max($displayLimit, 24);

    $crm = site_crm_fetch_listings($fetchLimit, 0, $apiQuery);
    if ($crm['error'] !== null) {
        return ['items' => [], 'total' => null, 'error' => $crm['error'], 'filtered' => false];
    }

    $items = $crm['items'];
    if ($needsLocal) {
        $filtered = [];
        foreach ($items as $row) {
            if (is_array($row) && site_catalog_row_matches_filters($row, $filters)) {
                $filtered[] = $row;
            }
        }
        $items = array_slice($filtered, 0, $displayLimit);
        $total = count($filtered);

        return ['items' => $items, 'total' => $total, 'error' => null, 'filtered' => true];
    }

    return [
        'items' => array_slice($items, 0, $displayLimit),
        'total' => $crm['total'],
        'error' => null,
        'filtered' => count($apiQuery) > 0,
    ];
}

/**
 * @param array<string, string> $apiQuery
 * @return array{items: list<array<string, mixed>>, total: ?int, error: ?string}
 */
function site_crm_fetch_listings(int $limit = 24, int $offset = 0, array $apiQuery = []): array
{
    $params = array_merge(
        ['limit' => $limit, 'offset' => $offset],
        array_filter($apiQuery, static fn ($v) => $v !== null && $v !== ''),
    );
    $crm = site_http_get_json_cached(site_crm_listings_url() . '?' . http_build_query($params), 10, 300);

    $items = (isset($crm['items']) && is_array($crm['items'])) ? $crm['items'] : [];
    $total = (isset($crm['total']) && is_numeric($crm['total'])) ? (int) $crm['total'] : null;
    $error = isset($crm['_error']) ? (string) $crm['_error'] : null;

    if ($error === null && count($items) > 0) {
        $enriched = [];
        foreach ($items as $row) {
            $enriched[] = is_array($row) ? site_crm_listing_enrich_row($row) : $row;
        }
        $items = $enriched;
    }

    return ['items' => $items, 'total' => $total, 'error' => $error];
}

/**
 * Объекты для карты: те же фильтры, что в каталоге, до 100 шт.
 *
 * @param array<string, string> $filters
 * @return array{items: list<array<string, mixed>>, total: ?int, error: ?string, withCoords: int}
 */
function site_crm_fetch_listings_for_map(array $filters): array
{
    $result = site_crm_fetch_listings_catalog($filters, 100);
    if ($result['error'] !== null) {
        return ['items' => [], 'total' => null, 'error' => $result['error'], 'withCoords' => 0];
    }

    $withCoords = 0;
    foreach ($result['items'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $lat = $row['latitude'] ?? null;
        $lng = $row['longitude'] ?? null;
        if (is_numeric($lat) && is_numeric($lng) && (abs((float) $lat) > 0.0001 || abs((float) $lng) > 0.0001)) {
            $withCoords++;
        }
    }

    return [
        'items' => $result['items'],
        'total' => $result['total'],
        'error' => null,
        'withCoords' => $withCoords,
    ];
}

/**
 * @param list<array<string, mixed>> $items
 * @return list<array{id: string, lat: float, lng: float, title: string, price: string, href: string}>
 */
function site_catalog_map_markers_from_items(array $items): array
{
    $markers = [];
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = isset($row['id']) ? trim((string) $row['id']) : '';
        if ($id === '') {
            continue;
        }
        $lat = $row['latitude'] ?? null;
        $lng = $row['longitude'] ?? null;
        if (!is_numeric($lat) || !is_numeric($lng)) {
            continue;
        }
        $latF = (float) $lat;
        $lngF = (float) $lng;
        if ($latF < -90 || $latF > 90 || $lngF < -180 || $lngF > 180) {
            continue;
        }
        if (abs($latF) < 0.0001 && abs($lngF) < 0.0001) {
            continue;
        }

        $rooms = isset($row['rooms']) && is_numeric($row['rooms']) ? (int) $row['rooms'] : null;
        $areaTotal = isset($row['areaTotal']) && is_numeric($row['areaTotal']) ? (float) $row['areaTotal'] : null;
        $objectType = isset($row['objectTypeValue']) ? (string) $row['objectTypeValue'] : null;
        $titleRaw = isset($row['title']) ? (string) $row['title'] : 'Объект';
        $title = site_listing_card_title($objectType, $rooms, $areaTotal, $titleRaw);
        $priceRaw = isset($row['price']) ? (string) $row['price'] : null;

        $markers[] = [
            'id' => $id,
            'lat' => $latF,
            'lng' => $lngF,
            'title' => $title,
            'price' => site_fmt_rub($priceRaw),
            'href' => '/catalog/object/?id=' . rawurlencode($id),
        ];
    }

    return $markers;
}
