<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/crm-listing-description.php';

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

function site_fmt_rub_from(?string $raw): string
{
    $value = site_fmt_rub($raw);

    return $value === '—' ? $value : 'от ' . $value;
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

/** Краткая подпись комнат: «1-к», «2-к» … */
function site_rooms_short_label(?int $rooms): ?string
{
    if ($rooms === null || $rooms <= 0) {
        return null;
    }

    return $rooms . '-к';
}

/** «1-к квартира», «2-к квартира» или «Квартира». */
function site_flat_short_label(?int $rooms): string
{
    $short = site_rooms_short_label($rooms);

    return $short !== null ? $short . ' квартира' : 'Квартира';
}

function site_object_meta_label(?string $objectType, ?int $rooms): string
{
    $t = $objectType ? trim($objectType) : '';
    if ($t === 'flat') {
        $short = site_rooms_short_label($rooms);

        return $short !== null ? $short . ' кв.' : 'Квартира';
    }
    if ($t === 'newbuilding') {
        $short = site_rooms_short_label($rooms);

        return $short !== null ? $short . ' кв.' : 'Новостройка';
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
    $exts = ['webp', 'WEBP', 'jpg', 'jpeg', 'JPG', 'JPEG'];

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
/**
 * Кэш слайдов hero в рамках одного HTTP-запроса (главная + preload).
 *
 * @return list<array{src: string, alt: string}>
 */
function site_hero_slides_cached(int $max = 5): array
{
    static $cache = [];
    $max = max(1, min(8, $max));
    if (!isset($cache[$max])) {
        $cache[$max] = site_hero_slides_resolve($max);
    }

    return $cache[$max];
}

function site_hero_lcp_preload_href(): string
{
    $slides = site_hero_slides_cached(5);
    if (count($slides) === 0) {
        return '';
    }
    $src = trim((string) ($slides[0]['src'] ?? ''));
    if ($src === '') {
        return '';
    }
    if (str_starts_with($src, '/')) {
        return $src;
    }

    return $src;
}

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
    $crm = site_crm_fetch_featured_listings(max(1, min($max, 8)));
    $items = $crm['items'];
    if (($crm['error'] !== null || count($items) === 0) && $max > 0) {
        $fallback = site_crm_fetch_listings(max(1, min($max, 8)), 0);
        if ($fallback['error'] === null && count($fallback['items']) > 0) {
            $crm = $fallback;
            $items = $fallback['items'];
        }
    }
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
        $src = site_crm_photo_display_src(site_crm_photo_src($coverRaw), 'hero');
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
                $src = site_crm_photo_display_src(site_crm_photo_src(trim($p)), 'hero');
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
    if ($t === 'flat' || $t === 'newbuilding' || ($rooms !== null && $rooms > 0)) {
        $flatLabel = site_flat_short_label($rooms);
        if ($area !== null) {
            return $flatLabel . ' ' . $area . ' м²';
        }

        return $flatLabel;
    }
    if ($area !== null) {
        return 'Квартира ' . $area . ' м²';
    }

    return $fallback !== '' ? $fallback : 'Объект';
}

/**
 * @return list<array{label: string, value: string}>
 */
function site_listing_object_param_rows(array $row): array
{
    $sections = site_listing_object_spec_sections($row);
    $rows = [];
    foreach ($sections as $section) {
        foreach ($section['rows'] as $item) {
            $rows[] = $item;
        }
    }

    return $rows;
}

function site_listing_housing_type_label(?string $objectType): ?string
{
    $t = trim((string) $objectType);
    return match ($t) {
        'newbuilding' => 'Новостройка',
        'flat' => 'Вторичка',
        'house' => 'Дом',
        'plot', 'land' => 'Участок',
        'commercial' => 'Коммерция',
        default => $t !== '' ? 'Объект' : null,
    };
}

function site_listing_documents_ready_label(mixed $value): ?string
{
    if ($value === true || $value === 'true' || $value === 1 || $value === '1') {
        return 'Да';
    }
    if ($value === false || $value === 'false' || $value === 0 || $value === '0') {
        return 'Нет';
    }

    return null;
}

/**
 * Нормализованный тип объекта для блока характеристик.
 *
 * @param array<string, mixed> $row
 */
function site_listing_object_spec_kind(array $row): string
{
    $t = isset($row['objectTypeValue']) ? trim((string) $row['objectTypeValue']) : '';
    if ($t === 'land') {
        return 'plot';
    }
    if (in_array($t, ['flat', 'newbuilding', 'house', 'plot', 'commercial'], true)) {
        return $t;
    }

    $rooms = isset($row['rooms']) && is_numeric($row['rooms']) ? (int) $row['rooms'] : null;
    $areaLand = isset($row['areaLand']) && is_numeric($row['areaLand']) ? (float) $row['areaLand'] : null;
    if ($areaLand !== null && $areaLand > 0 && ($rooms === null || $rooms <= 0)) {
        return 'plot';
    }
    if ($rooms !== null && $rooms > 0) {
        return 'flat';
    }

    return 'flat';
}

/**
 * @param array<string, mixed> $row
 * @return array{
 *   objectType: string,
 *   kind: string,
 *   rooms: ?int,
 *   areaTotal: ?float,
 *   areaLiving: ?float,
 *   areaKitchen: ?float,
 *   areaLand: ?float,
 *   floor: ?string,
 *   floorTotal: ?int,
 *   yearBuilt: ?int,
 *   deal: string,
 *   housingType: ?string,
 *   ownership: string,
 *   documents: ?string,
 *   jk: string,
 *   district: string,
 *   city: string,
 *   cadastral: string
 * }
 */
function site_listing_spec_context(array $row): array
{
    $objectType = isset($row['objectTypeValue']) ? trim((string) $row['objectTypeValue']) : '';
    $rooms = isset($row['rooms']) && is_numeric($row['rooms']) ? (int) $row['rooms'] : null;

    return [
        'objectType' => $objectType,
        'kind' => site_listing_object_spec_kind($row),
        'rooms' => $rooms !== null && $rooms > 0 ? $rooms : null,
        'areaTotal' => isset($row['areaTotal']) && is_numeric($row['areaTotal']) ? (float) $row['areaTotal'] : null,
        'areaLiving' => isset($row['areaLiving']) && is_numeric($row['areaLiving']) ? (float) $row['areaLiving'] : null,
        'areaKitchen' => isset($row['areaKitchen']) && is_numeric($row['areaKitchen']) ? (float) $row['areaKitchen'] : null,
        'areaLand' => isset($row['areaLand']) && is_numeric($row['areaLand']) ? (float) $row['areaLand'] : null,
        'floor' => isset($row['floor']) ? trim((string) $row['floor']) : null,
        'floorTotal' => isset($row['floorTotal']) && is_numeric($row['floorTotal']) ? (int) $row['floorTotal'] : null,
        'yearBuilt' => isset($row['yearBuilt']) && is_numeric($row['yearBuilt']) ? (int) $row['yearBuilt'] : null,
        'deal' => site_deal_line_public_label(isset($row['dealLineValue']) ? (string) $row['dealLineValue'] : null),
        'housingType' => site_listing_housing_type_label($objectType !== '' ? $objectType : site_listing_object_spec_kind($row)),
        'ownership' => isset($row['ownershipType']) ? trim((string) $row['ownershipType']) : '',
        'documents' => site_listing_documents_ready_label($row['documentsReady'] ?? null),
        'jk' => isset($row['residentialComplex']) ? trim((string) $row['residentialComplex']) : '',
        'district' => isset($row['districtValue']) ? trim((string) $row['districtValue']) : '',
        'city' => isset($row['city']) ? trim((string) $row['city']) : '',
        'cadastral' => isset($row['cadastral']) ? trim((string) $row['cadastral']) : '',
    ];
}

/**
 * @return list<array{label: string, value: string}>
 */
function site_listing_spec_rows_from_map(array $map): array
{
    $rows = [];
    foreach ($map as $label => $value) {
        $v = trim((string) $value);
        if ($v !== '') {
            $rows[] = ['label' => (string) $label, 'value' => $v];
        }
    }

    return $rows;
}

/**
 * @param array<string, mixed> $ctx
 * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
 */
function site_listing_flat_spec_sections(array $ctx): array
{
    $floorText = site_listing_floor_label($ctx['floor'] ?: null, $ctx['floorTotal']);
    $area = site_fmt_area_short($ctx['areaTotal']);
    $living = site_fmt_area_short($ctx['areaLiving']);
    $kitchen = site_fmt_area_short($ctx['areaKitchen']);

    $unit = site_listing_spec_rows_from_map([
        'Тип жилья' => $ctx['housingType'] ?? '',
        'Сделка' => $ctx['deal'],
        'Комнат' => $ctx['rooms'] !== null ? (string) $ctx['rooms'] : '',
        'Общая площадь' => $area !== null ? $area . ' м²' : '',
        'Жилая площадь' => $living !== null ? $living . ' м²' : '',
        'Площадь кухни' => $kitchen !== null ? $kitchen . ' м²' : '',
        'Этаж' => $floorText ?? '',
        'Собственность' => $ctx['ownership'],
        'Документы готовы' => $ctx['documents'] ?? '',
    ]);

    $building = site_listing_spec_rows_from_map([
        'Год постройки' => ($ctx['yearBuilt'] ?? 0) > 0 ? (string) $ctx['yearBuilt'] : '',
        'Этажность' => ($ctx['floorTotal'] ?? 0) > 0 ? (string) $ctx['floorTotal'] : '',
        'Жилой комплекс' => $ctx['jk'],
        'Район' => $ctx['district'],
        'Город' => $ctx['city'],
    ]);

    $sections = [];
    if (count($unit) > 0) {
        $sections[] = ['title' => $ctx['objectType'] === 'newbuilding' ? 'О новостройке' : 'О квартире', 'rows' => $unit];
    }
    if (count($building) > 0) {
        $sections[] = ['title' => 'О доме', 'rows' => $building];
    }

    return $sections;
}

/**
 * @param array<string, mixed> $ctx
 * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
 */
function site_listing_house_spec_sections(array $ctx): array
{
    $area = site_fmt_area_short($ctx['areaTotal']);
    $land = site_fmt_area_short($ctx['areaLand']);
    $living = site_fmt_area_short($ctx['areaLiving']);
    $kitchen = site_fmt_area_short($ctx['areaKitchen']);
    $floorText = site_listing_floor_label($ctx['floor'] ?: null, $ctx['floorTotal']);

    $house = site_listing_spec_rows_from_map([
        'Тип объекта' => 'Дом',
        'Сделка' => $ctx['deal'],
        'Комнат' => $ctx['rooms'] !== null ? (string) $ctx['rooms'] : '',
        'Площадь дома' => $area !== null ? $area . ' м²' : '',
        'Жилая площадь' => $living !== null ? $living . ' м²' : '',
        'Площадь кухни' => $kitchen !== null ? $kitchen . ' м²' : '',
        'Этажей в доме' => $floorText ?? (($ctx['floorTotal'] ?? 0) > 0 ? (string) $ctx['floorTotal'] : ''),
        'Год постройки' => ($ctx['yearBuilt'] ?? 0) > 0 ? (string) $ctx['yearBuilt'] : '',
        'Собственность' => $ctx['ownership'],
        'Документы готовы' => $ctx['documents'] ?? '',
    ]);

    $landSection = site_listing_spec_rows_from_map([
        'Площадь участка' => $land !== null && ($ctx['areaLand'] ?? 0) > 0 ? $land . ' сот.' : '',
        'Кадастровый номер' => $ctx['cadastral'],
        'Район' => $ctx['district'],
        'Город' => $ctx['city'],
    ]);

    $sections = [];
    if (count($house) > 0) {
        $sections[] = ['title' => 'О доме', 'rows' => $house];
    }
    if (count($landSection) > 0) {
        $sections[] = ['title' => 'Участок и расположение', 'rows' => $landSection];
    }

    return $sections;
}

/**
 * @param array<string, mixed> $ctx
 * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
 */
function site_listing_plot_spec_sections(array $ctx): array
{
    $land = site_fmt_area_short($ctx['areaLand']);
    $area = site_fmt_area_short($ctx['areaTotal']);

    $plot = site_listing_spec_rows_from_map([
        'Тип объекта' => 'Земельный участок',
        'Сделка' => $ctx['deal'],
        'Площадь участка' => $land !== null && ($ctx['areaLand'] ?? 0) > 0
            ? $land . ' сот.'
            : ($area !== null ? $area . ' м²' : ''),
        'Кадастровый номер' => $ctx['cadastral'],
        'Собственность' => $ctx['ownership'],
        'Документы готовы' => $ctx['documents'] ?? '',
    ]);

    $location = site_listing_spec_rows_from_map([
        'Район' => $ctx['district'],
        'Город' => $ctx['city'],
    ]);

    $sections = [];
    if (count($plot) > 0) {
        $sections[] = ['title' => 'Об участке', 'rows' => $plot];
    }
    if (count($location) > 0) {
        $sections[] = ['title' => 'Расположение', 'rows' => $location];
    }

    return $sections;
}

/**
 * @param array<string, mixed> $ctx
 * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
 */
function site_listing_commercial_spec_sections(array $ctx): array
{
    $area = site_fmt_area_short($ctx['areaTotal']);
    $floorText = site_listing_floor_label($ctx['floor'] ?: null, $ctx['floorTotal']);

    $unit = site_listing_spec_rows_from_map([
        'Тип объекта' => 'Коммерческая недвижимость',
        'Сделка' => $ctx['deal'],
        'Площадь' => $area !== null ? $area . ' м²' : '',
        'Этаж' => $floorText ?? '',
        'Собственность' => $ctx['ownership'],
        'Документы готовы' => $ctx['documents'] ?? '',
    ]);

    $building = site_listing_spec_rows_from_map([
        'Год постройки' => ($ctx['yearBuilt'] ?? 0) > 0 ? (string) $ctx['yearBuilt'] : '',
        'Этажность здания' => ($ctx['floorTotal'] ?? 0) > 0 ? (string) $ctx['floorTotal'] : '',
        'Жилой комплекс / БЦ' => $ctx['jk'],
        'Район' => $ctx['district'],
        'Город' => $ctx['city'],
        'Кадастровый номер' => $ctx['cadastral'],
    ]);

    $sections = [];
    if (count($unit) > 0) {
        $sections[] = ['title' => 'О помещении', 'rows' => $unit];
    }
    if (count($building) > 0) {
        $sections[] = ['title' => 'О здании', 'rows' => $building];
    }

    return $sections;
}

/**
 * @param array<string, mixed> $ctx
 * @param array<string, mixed> $row
 * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
 */
function site_listing_newbuilding_complex_spec_sections(array $ctx, array $row): array
{
    $offersTotal = site_developer_offers_total($row);
    $unit = site_listing_spec_rows_from_map([
        'Тип жилья' => $ctx['housingType'] ?? '',
        'Сделка' => $ctx['deal'],
        'Предложений от застройщика' => $offersTotal > 0 ? (string) $offersTotal : '',
    ]);
    $building = site_listing_spec_rows_from_map([
        'Жилой комплекс' => $ctx['jk'],
        'Район' => $ctx['district'],
        'Город' => $ctx['city'],
    ]);
    $sections = [];
    if (count($unit) > 0) {
        $sections[] = ['title' => 'О жилом комплексе', 'rows' => $unit];
    }
    if (count($building) > 0) {
        $sections[] = ['title' => 'Расположение', 'rows' => $building];
    }

    return $sections;
}

/**
 * @param array<string, mixed> $row
 */
function site_newbuilding_page_title(array $row): string
{
    $jk = trim((string) ($row['residentialComplex'] ?? ''));
    if ($jk !== '') {
        return $jk;
    }
    $title = trim((string) ($row['title'] ?? ''));
    if ($title !== '') {
        return $title;
    }

    return 'Жилой комплекс';
}

/**
 * @param array<string, mixed> $row
 */
function site_developer_offers_total(array $row): int
{
    $total = isset($row['developerOffersTotal']) && is_numeric($row['developerOffersTotal'])
        ? (int) $row['developerOffersTotal']
        : 0;
    $count = count(site_developer_offers_entries($row));

    return max($total, $count);
}

/**
 * @param array<string, mixed> $row
 * @return list<array<string, mixed>>
 */
function site_developer_buildings_entries(array $row): array
{
    $raw = $row['developerBuildings'] ?? null;
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $buildingId = isset($item['buildingId']) && is_numeric($item['buildingId'])
            ? (int) $item['buildingId']
            : null;
        $name = isset($item['name']) ? trim((string) $item['name']) : '';
        if ($buildingId === null || $name === '') {
            continue;
        }
        $out[] = [
            'buildingId' => $buildingId,
            'name' => $name,
            'isReady' => !empty($item['isReady']),
            'completionYear' => isset($item['completionYear']) && is_numeric($item['completionYear'])
                ? (int) $item['completionYear']
                : null,
            'completionQuarter' => isset($item['completionQuarter']) && is_numeric($item['completionQuarter'])
                ? (int) $item['completionQuarter']
                : null,
            'floors' => isset($item['floors']) && is_numeric($item['floors']) ? (int) $item['floors'] : null,
        ];
    }

    return $out;
}

/**
 * @param array<string, mixed> $row
 * @return list<array<string, mixed>>
 */
function site_developer_offers_entries(array $row): array
{
    $raw = $row['developerOffers'] ?? null;
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $layoutId = isset($item['layoutId']) ? trim((string) $item['layoutId']) : '';
        if ($layoutId === '') {
            continue;
        }
        $out[] = [
            'layoutId' => $layoutId,
            'offerId' => isset($item['offerId']) && is_numeric($item['offerId']) ? (int) $item['offerId'] : null,
            'buildingId' => isset($item['buildingId']) && is_numeric($item['buildingId'])
                ? (int) $item['buildingId']
                : null,
            'buildingName' => isset($item['buildingName']) ? trim((string) $item['buildingName']) : null,
            'rooms' => isset($item['rooms']) && is_numeric($item['rooms']) ? (int) $item['rooms'] : null,
            'area' => isset($item['area']) && is_numeric($item['area']) ? (float) $item['area'] : null,
            'price' => isset($item['price']) && is_numeric($item['price']) ? (int) $item['price'] : null,
            'floor' => isset($item['floor']) && is_numeric($item['floor']) ? (int) $item['floor'] : null,
            'floorMax' => isset($item['floorMax']) && is_numeric($item['floorMax']) ? (int) $item['floorMax'] : null,
            'floorsTotal' => isset($item['floorsTotal']) && is_numeric($item['floorsTotal'])
                ? (int) $item['floorsTotal']
                : null,
            'planImageUrl' => isset($item['planImageUrl']) ? trim((string) $item['planImageUrl']) : null,
            'flatsCount' => isset($item['flatsCount']) && is_numeric($item['flatsCount'])
                ? (int) $item['flatsCount']
                : 1,
            'completionYear' => isset($item['completionYear']) && is_numeric($item['completionYear'])
                ? (int) $item['completionYear']
                : null,
            'completionQuarter' => isset($item['completionQuarter']) && is_numeric($item['completionQuarter'])
                ? (int) $item['completionQuarter']
                : null,
            'sourceUrl' => isset($item['sourceUrl']) ? trim((string) $item['sourceUrl']) : null,
        ];
    }

    return $out;
}

function site_developer_completion_label(?int $quarter, ?int $year, bool $isReady = false): string
{
    if ($isReady) {
        return 'Сдан';
    }
    if ($quarter !== null && $quarter > 0 && $year !== null && $year > 0) {
        return 'Сдача в ' . site_construction_quarter_label($quarter) . ' ' . $year . ' г.';
    }
    if ($year !== null && $year > 0) {
        return 'Сдача в ' . $year . ' г.';
    }

    return '';
}

/**
 * @param array<string, mixed> $row
 */
function site_render_developer_offers_section(array $row): void
{
    $offers = site_developer_offers_entries($row);
    if (count($offers) === 0) {
        return;
    }
    $buildings = site_developer_buildings_entries($row);
    $complexTitle = site_newbuilding_page_title($row);
    $offersTotal = site_developer_offers_total($row);
    $sourceUrl = isset($row['sourceUrl']) ? trim((string) $row['sourceUrl']) : '';
    ?>
    <section
        class="listing-object__section listing-object__section--developer-offers"
        aria-labelledby="listing-developer-offers-title"
        data-developer-offers
    >
        <div class="developer-offers__head">
            <h2 class="listing-object__section-title" id="listing-developer-offers-title">
                <?php echo (int) $offersTotal; ?> предложений от застройщика в <?php echo htmlspecialchars($complexTitle, ENT_QUOTES, 'UTF-8'); ?>
            </h2>
        </div>
        <?php if (count($buildings) > 0) { ?>
            <div class="developer-offers__tabs" role="tablist" aria-label="Корпуса">
                <button
                    type="button"
                    class="developer-offers__tab is-active"
                    role="tab"
                    aria-selected="true"
                    data-building-filter="all"
                >Все корпуса</button>
                <?php foreach ($buildings as $building) {
                    $bid = (int) $building['buildingId'];
                    $status = site_developer_completion_label(
                        $building['completionQuarter'] ?? null,
                        $building['completionYear'] ?? null,
                        !empty($building['isReady']),
                    );
                    ?>
                    <button
                        type="button"
                        class="developer-offers__tab"
                        role="tab"
                        aria-selected="false"
                        data-building-filter="<?php echo $bid; ?>"
                    >
                        <span class="developer-offers__tab-name"><?php echo htmlspecialchars((string) $building['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($status !== '') { ?>
                            <span class="developer-offers__tab-status<?php echo !empty($building['isReady']) ? ' developer-offers__tab-status--ready' : ''; ?>">
                                <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php } ?>
                    </button>
                <?php } ?>
            </div>
        <?php } ?>
        <div class="developer-offers__list">
            <?php foreach ($offers as $offer) {
                $buildingId = $offer['buildingId'] ?? null;
                $rooms = $offer['rooms'] ?? null;
                $roomLabel = $rooms !== null && $rooms > 0 ? $rooms . '-комн.' : 'Квартира';
                $area = isset($offer['area']) && is_numeric($offer['area'])
                    ? rtrim(rtrim(number_format((float) $offer['area'], 2, '.', ''), '0'), '.')
                    : null;
                $floor = $offer['floor'] ?? null;
                $floorMax = $offer['floorMax'] ?? null;
                $floorsTotal = $offer['floorsTotal'] ?? null;
                $floorText = '';
                if ($floor !== null && $floorsTotal !== null && $floorsTotal > 0) {
                    $floorText = $floorMax !== null && $floorMax > $floor
                        ? $floor . '–' . $floorMax . ' / ' . $floorsTotal
                        : $floor . ' / ' . $floorsTotal;
                }
                $completion = site_developer_completion_label(
                    $offer['completionQuarter'] ?? null,
                    $offer['completionYear'] ?? null,
                );
                $priceText = isset($offer['price']) ? site_fmt_rub((string) $offer['price']) : '—';
                $planUrl = isset($offer['planImageUrl']) ? trim((string) $offer['planImageUrl']) : '';
                $href = isset($offer['sourceUrl']) ? trim((string) $offer['sourceUrl']) : '';
                $flatsCount = (int) ($offer['flatsCount'] ?? 1);
                ?>
                <article
                    class="developer-offers__row"
                    data-building-id="<?php echo $buildingId !== null ? (int) $buildingId : ''; ?>"
                >
                    <div class="developer-offers__plan">
                        <?php if ($planUrl !== '') { ?>
                            <img
                                class="developer-offers__plan-img"
                                src="<?php echo htmlspecialchars($planUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="Планировка"
                                loading="lazy"
                                decoding="async"
                                referrerpolicy="no-referrer"
                            >
                        <?php } else { ?>
                            <div class="developer-offers__plan-placeholder" aria-hidden="true"></div>
                        <?php } ?>
                    </div>
                    <div class="developer-offers__meta">
                        <p class="developer-offers__rooms"><?php echo htmlspecialchars($roomLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($offer['buildingName'])) { ?>
                            <p class="developer-offers__building"><?php echo htmlspecialchars((string) $offer['buildingName'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php } ?>
                    </div>
                    <div class="developer-offers__col developer-offers__col--area">
                        <span class="developer-offers__col-label">Площадь</span>
                        <span class="developer-offers__col-value"><?php echo $area !== null ? htmlspecialchars($area . ' м²', ENT_QUOTES, 'UTF-8') : '—'; ?></span>
                    </div>
                    <div class="developer-offers__col developer-offers__col--floor">
                        <span class="developer-offers__col-label">Этаж</span>
                        <span class="developer-offers__col-value"><?php echo $floorText !== '' ? htmlspecialchars($floorText, ENT_QUOTES, 'UTF-8') : '—'; ?></span>
                    </div>
                    <div class="developer-offers__col developer-offers__col--completion">
                        <span class="developer-offers__col-label">Сдача</span>
                        <span class="developer-offers__col-value"><?php echo $completion !== '' ? htmlspecialchars($completion, ENT_QUOTES, 'UTF-8') : '—'; ?></span>
                    </div>
                    <div class="developer-offers__price-wrap">
                        <p class="developer-offers__price"><?php echo htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ($href !== '') { ?>
                            <a class="developer-offers__link" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo (int) $flatsCount; ?> <?php echo $flatsCount === 1 ? 'предложение' : 'предложения'; ?>
                            </a>
                        <?php } ?>
                    </div>
                </article>
            <?php } ?>
        </div>
        <?php if ($sourceUrl !== '' && $offersTotal > count($offers)) { ?>
            <a class="developer-offers__more" href="<?php echo htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                Смотреть все
            </a>
        <?php } ?>
    </section>
    <?php
}

/**
 * Две колонки характеристик для карточки объекта (набор полей зависит от типа).
 *
 * @param array<string, mixed> $row
 * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
 */
function site_listing_object_spec_sections(array $row): array
{
    $ctx = site_listing_spec_context($row);
    if (
        $ctx['kind'] === 'newbuilding'
        && count(site_developer_offers_entries($row)) > 0
    ) {
        return site_listing_newbuilding_complex_spec_sections($ctx, $row);
    }

    return match ($ctx['kind']) {
        'house' => site_listing_house_spec_sections($ctx),
        'plot' => site_listing_plot_spec_sections($ctx),
        'commercial' => site_listing_commercial_spec_sections($ctx),
        'newbuilding', 'flat' => site_listing_flat_spec_sections($ctx),
        default => site_listing_flat_spec_sections($ctx),
    };
}

/**
 * @param list<array{title: string, rows: list<array{label: string, value: string}>}> $sections
 */
function site_render_listing_object_specs(array $sections): void
{
    if (count($sections) === 0) {
        return;
    }
    $gridClass = 'listing-object__specs-grid' . (count($sections) === 1 ? ' listing-object__specs-grid--one' : '');
    ?>
    <section class="listing-object__specs" aria-labelledby="listing-specs-title">
        <h2 class="visually-hidden" id="listing-specs-title">Характеристики объекта</h2>
        <div class="<?php echo htmlspecialchars($gridClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ($sections as $section) {
                if (count($section['rows']) === 0) {
                    continue;
                }
                ?>
                <div class="listing-object__specs-col">
                    <h3 class="listing-object__specs-col-title"><?php echo htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <dl class="listing-specs">
                        <?php foreach ($section['rows'] as $item) { ?>
                            <div class="listing-specs__row">
                                <dt class="listing-specs__label"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></dt>
                                <dd class="listing-specs__value"><?php echo htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                        <?php } ?>
                    </dl>
                </div>
            <?php } ?>
        </div>
    </section>
    <?php
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
    $objectType = strtolower(trim((string) ($row['objectTypeValue'] ?? '')));
    $isNewbuilding = $objectType === 'newbuilding';
    $hasConstruction = isset($row['constructionProgress'])
        && is_array($row['constructionProgress'])
        && count($row['constructionProgress']) > 0;
    $hasDeveloperOffers = isset($row['developerOffers'])
        && is_array($row['developerOffers'])
        && count($row['developerOffers']) > 0;
    $needConstruction = $isNewbuilding && !$hasConstruction;
    $needSimilar = false;
    $needDeveloperOffers = $isNewbuilding && !$hasDeveloperOffers;

    if (!$needPhotos && !$needDesc && !$needAddress && !$needConstruction && !$needSimilar && !$needDeveloperOffers) {
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
    if ($needConstruction && isset($detail['constructionProgress']) && is_array($detail['constructionProgress'])) {
        $row['constructionProgress'] = $detail['constructionProgress'];
    }
    if ($needDeveloperOffers && isset($detail['developerOffers']) && is_array($detail['developerOffers'])) {
        $row['developerOffers'] = $detail['developerOffers'];
    }
    if ($needDeveloperOffers && isset($detail['developerBuildings']) && is_array($detail['developerBuildings'])) {
        $row['developerBuildings'] = $detail['developerBuildings'];
    }
    if ($needDeveloperOffers && isset($detail['developerOffersTotal']) && is_numeric($detail['developerOffersTotal'])) {
        $row['developerOffersTotal'] = (int) $detail['developerOffersTotal'];
    }
    if ($needPhotos) {
        if (isset($detail['photos']) && is_array($detail['photos']) && count($detail['photos']) > 0) {
            $row['photos'] = $detail['photos'];
        }
        if (isset($detail['coverPhoto']) && is_string($detail['coverPhoto']) && trim($detail['coverPhoto']) !== '') {
            $row['coverPhoto'] = $detail['coverPhoto'];
        }
    }
    if (!isset($row['constructionProgress']) || !is_array($row['constructionProgress'])) {
        if (isset($detail['constructionProgress']) && is_array($detail['constructionProgress'])) {
            $row['constructionProgress'] = $detail['constructionProgress'];
        }
    }
    if (!isset($row['developerOffers']) || !is_array($row['developerOffers'])) {
        if (isset($detail['developerOffers']) && is_array($detail['developerOffers'])) {
            $row['developerOffers'] = $detail['developerOffers'];
        }
    }
    if (!isset($row['developerBuildings']) || !is_array($row['developerBuildings'])) {
        if (isset($detail['developerBuildings']) && is_array($detail['developerBuildings'])) {
            $row['developerBuildings'] = $detail['developerBuildings'];
        }
    }
    if (!isset($row['developerOffersTotal']) && isset($detail['developerOffersTotal']) && is_numeric($detail['developerOffersTotal'])) {
        $row['developerOffersTotal'] = (int) $detail['developerOffersTotal'];
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
            $resolved[] = site_crm_photo_display_src($src, 'card');
        }
    }
    if (count($resolved) === 0 && count($raw) > 0) {
        $src = site_crm_photo_src($raw[0]);
        if ($src !== '') {
            $resolved[] = site_crm_photo_display_src($src, 'card');
        }
    }

    return ['resolved' => $resolved, 'raw' => $raw];
}

/**
 * Галерея объекта: на сервере только 1-е фото (LCP), остальные — lazy в браузере.
 *
 * @param array<string, mixed> $row
 * @return array{first: string, raw: list<string>, total: int}
 */
function site_crm_listing_gallery_bundle(array $row, int $max = 30): array
{
    $raw = site_crm_listing_raw_photo_urls($row, $max);
    $first = '';
    if (count($raw) > 0) {
        $resolved = site_crm_photo_src($raw[0]);
        if ($resolved !== '') {
            $first = site_crm_photo_display_src($resolved, 'gallery');
        }
    }

    return [
        'first' => $first,
        'raw' => $raw,
        'total' => count($raw),
    ];
}

/**
 * @deprecated Используйте site_crm_listing_gallery_bundle — не резолвить все фото на сервере.
 *
 * @param array<string, mixed> $row
 * @return list<string>
 */
function site_crm_listing_resolved_photos(array $row, int $max = 12): array
{
    $bundle = site_crm_listing_gallery_bundle($row, $max);
    if ($bundle['first'] === '') {
        return [];
    }

    return [$bundle['first']];
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
    $photosCount = isset($row['photosCount']) && is_numeric($row['photosCount']) ? (int) $row['photosCount'] : 0;

    $photoBundle = site_crm_listing_photo_bundle($row, 1);
    $photoUrls = $photoBundle['resolved'];
    $photosB64 = base64_encode(json_encode($photoUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]');
    $photosRawB64 = base64_encode(json_encode($photoBundle['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]');

    $priceText = site_fmt_rub($priceRaw);
    $priceM2 = site_fmt_m2($areaTotal, $priceRaw);
    $floorText = site_listing_floor_label($floor, $floorTotal);
    $buildingText = site_listing_building_label($yearBuilt, $jk);
    $addressLine = site_listing_address_line($row);
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
    $descRaw = isset($row['description']) ? trim((string) $row['description']) : '';
    $descExcerpt = site_excerpt_text($descRaw, 280);
    $hasDesc = $descExcerpt !== '';
    ?>
    <li class="catalog-list__item">
        <article class="listing-card listing-card--compact<?php echo $hasDesc ? '' : ' listing-card--no-desc'; ?>" data-listing-card data-listing-id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <div
                class="listing-card__media listing-card__media--tone-<?php echo (int) $tone; ?>"
                data-listing-gallery
                data-photos-b64="<?php echo htmlspecialchars($photosB64, ENT_QUOTES, 'UTF-8'); ?>"
                data-photos-raw-b64="<?php echo htmlspecialchars($photosRawB64, ENT_QUOTES, 'UTF-8'); ?>"
            >
                <?php if (count($photoUrls) > 0) {
                    echo site_crm_photo_img($photoUrls[0], $cardTitle, 'listing-card__photo', 'data-listing-gallery-img', 'card');
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
            <?php if ($hasDesc) { ?>
                <div class="listing-card__desc">
                    <p class="listing-card__desc-text"><?php echo htmlspecialchars($descExcerpt, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            <?php } ?>
            <div class="listing-card__side">
                <div class="listing-card__actions">
                    <a class="listing-card__more" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">Подробнее</a>
                    <button type="button" class="listing-card__fav" data-listing-fav aria-pressed="false" aria-label="В избранное">
                        <svg class="listing-card__fav-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="none" stroke="currentColor" stroke-width="1.8" d="M12 21s-7-4.6-9.5-9C.5 7.5 3.4 4.5 7 4.5c2 0 3.7 1.1 5 2.7 1.3-1.6 3-2.7 5-2.7 3.6 0 6.5 3 4.5 7.5C19 16.4 12 21 12 21Z"/>
                        </svg>
                    </button>
                </div>
                <div class="listing-card__pricing">
                    <p class="listing-card__price"><?php echo htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($priceM2 !== null) { ?>
                        <p class="listing-card__price-m2"><?php echo htmlspecialchars($priceM2, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
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

function site_listing_has_discount(?string $oldRaw, ?string $currentRaw): bool
{
    $old = site_listing_price_number($oldRaw);
    $current = site_listing_price_number($currentRaw);

    return $old !== null && $current !== null && $old > $current;
}

function site_listing_discount_percent(?string $oldRaw, ?string $currentRaw): ?int
{
    if (!site_listing_has_discount($oldRaw, $currentRaw)) {
        return null;
    }
    $old = site_listing_price_number($oldRaw);
    $current = site_listing_price_number($currentRaw);
    if ($old === null || $current === null || $old <= 0) {
        return null;
    }

    return (int) round((1 - $current / $old) * 100);
}

function site_listing_discount_badge_text(?string $oldRaw, ?string $currentRaw): string
{
    $pct = site_listing_discount_percent($oldRaw, $currentRaw);
    if ($pct !== null && $pct > 0) {
        return 'Цена ниже на ' . $pct . '%';
    }

    return 'Снижена';
}

/**
 * Объекты для блока «Лучшие предложения» на главной (featuredOnSite в CRM).
 *
 * @return array{items: list<array<string, mixed>>, total: ?int, error: ?string}
 */
function site_crm_fetch_featured_listings(int $limit = 8): array
{
    return site_crm_fetch_listings(max(1, min($limit, 48)), 0, ['featured' => '1']);
}

/** newbuild | resale | commercial */
function site_listing_featured_group(?string $objectType): ?string
{
    $t = strtolower(trim((string) $objectType));
    if ($t === 'newbuilding') {
        return 'newbuild';
    }
    if ($t === 'commercial') {
        return 'commercial';
    }
    if (in_array($t, ['flat', 'house', 'plot', 'land'], true)) {
        return 'resale';
    }

    return null;
}

/**
 * @param array<string, mixed> $row
 */
function site_listing_in_irkutsk(array $row): bool
{
    $city = mb_strtolower(trim((string) ($row['city'] ?? '')), 'UTF-8');
    if ($city !== '' && mb_strpos($city, 'иркутск', 0, 'UTF-8') !== false) {
        return true;
    }
    $line = mb_strtolower(site_listing_address_line($row), 'UTF-8');

    return $line !== '' && mb_strpos($line, 'иркутск', 0, 'UTF-8') !== false;
}

/**
 * Featured-объекты, сгруппированные для главной: новостройки / вторичка / коммерция.
 *
 * @return array{
 *   groups: array{newbuild: list<array<string, mixed>>, resale: list<array<string, mixed>>, commercial: list<array<string, mixed>>},
 *   error: ?string
 * }
 */
function site_crm_fetch_featured_listing_groups(int $limitPerGroup = 8): array
{
    $limitPerGroup = max(1, min($limitPerGroup, 12));
    $crm = site_crm_fetch_featured_listings(48);
    $groups = [
        'newbuild' => [],
        'resale' => [],
        'commercial' => [],
    ];

    if ($crm['error'] !== null) {
        return ['groups' => $groups, 'error' => $crm['error']];
    }

    foreach ($crm['items'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $group = site_listing_featured_group(
            isset($row['objectTypeValue']) ? (string) $row['objectTypeValue'] : null,
        );
        if ($group === null) {
            continue;
        }
        if ($group === 'newbuild' && !site_listing_in_irkutsk($row)) {
            continue;
        }
        if (count($groups[$group]) >= $limitPerGroup) {
            continue;
        }
        $groups[$group][] = $row;
    }

    return ['groups' => $groups, 'error' => null];
}

/**
 * @param array<string, mixed> $row
 */
function site_render_featured_listing_card(array $row): void
{
    $id = isset($row['id']) ? (string) $row['id'] : '';
    if ($id === '') {
        return;
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
    $badgeClass = $hasDiscount ? 'featured-card__badge--sale' : 'featured-card__badge--new';
    $badgeText = $hasDiscount
        ? site_listing_discount_badge_text($priceOldRaw, $priceRaw)
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
    <?php
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
 * URL миниатюры объекта для метки на карте.
 *
 * @param array<string, mixed> $row
 */
function site_map_marker_photo_url(array $row): string
{
    $coverRaw = isset($row['coverPhoto']) ? trim((string) $row['coverPhoto']) : '';
    if ($coverRaw === '' && isset($row['photos']) && is_array($row['photos'])) {
        foreach ($row['photos'] as $p) {
            if (is_string($p) && trim($p) !== '') {
                $coverRaw = trim($p);
                break;
            }
        }
    }
    if ($coverRaw === '') {
        return '';
    }

    $display = site_crm_photo_display_src(site_crm_photo_src($coverRaw), 96);
    if ($display === '') {
        return '';
    }
    if (str_starts_with($display, '/')) {
        return site_absolute_url($display);
    }

    return $display;
}

/**
 * @param list<array<string, mixed>> $items
 * @return list<array{id: string, lat: float, lng: float, title: string, price: string, href: string, photo: string}>
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
            'photo' => site_map_marker_photo_url($row),
        ];
    }

    return $markers;
}

function site_construction_quarter_label(int $quarter): string
{
    return match ($quarter) {
        1 => '1 кв.',
        2 => '2 кв.',
        3 => '3 кв.',
        4 => '4 кв.',
        default => $quarter > 0 ? (string) $quarter . ' кв.' : '',
    };
}

/**
 * @param array<string, mixed> $row
 * @return list<array<string, mixed>>
 */
function site_construction_progress_entries(array $row): array
{
    $raw = $row['constructionProgress'] ?? null;
    if (!is_array($raw)) {
        return [];
    }
    $entries = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $photos = [];
        if (isset($item['photos']) && is_array($item['photos'])) {
            foreach ($item['photos'] as $p) {
                if (is_string($p) && trim($p) !== '') {
                    $photos[] = trim($p);
                }
            }
        }
        $videos = [];
        if (isset($item['videos']) && is_array($item['videos'])) {
            foreach ($item['videos'] as $v) {
                if (is_string($v) && trim($v) !== '') {
                    $videos[] = trim($v);
                }
            }
        }
        if (count($photos) === 0 && count($videos) === 0) {
            continue;
        }
        $year = isset($item['year']) && is_numeric($item['year']) ? (int) $item['year'] : 0;
        $quarter = isset($item['quarter']) && is_numeric($item['quarter']) ? (int) $item['quarter'] : 0;
        $buildingId = isset($item['buildingId']) && is_numeric($item['buildingId'])
            ? (int) $item['buildingId']
            : null;
        $buildingName = isset($item['buildingName']) ? trim((string) $item['buildingName']) : '';
        $lastUpdate = isset($item['lastUpdate']) ? trim((string) $item['lastUpdate']) : '';
        $entries[] = [
            'buildingId' => $buildingId,
            'buildingName' => $buildingName !== '' ? $buildingName : null,
            'year' => $year,
            'quarter' => $quarter,
            'lastUpdate' => $lastUpdate !== '' ? $lastUpdate : null,
            'photos' => $photos,
            'videos' => $videos,
        ];
    }
    usort($entries, static function (array $a, array $b): int {
        $da = isset($a['lastUpdate']) ? strtotime((string) $a['lastUpdate']) : false;
        $db = isset($b['lastUpdate']) ? strtotime((string) $b['lastUpdate']) : false;
        if ($da !== false && $db !== false && $da !== $db) {
            return $db <=> $da;
        }
        $ya = ((int) ($a['year'] ?? 0)) * 10 + (int) ($a['quarter'] ?? 0);
        $yb = ((int) ($b['year'] ?? 0)) * 10 + (int) ($b['quarter'] ?? 0);

        return $yb <=> $ya;
    });

    return $entries;
}

/**
 * @param list<array<string, mixed>> $entries
 */
function site_construction_progress_last_update(array $entries): ?string
{
    foreach ($entries as $entry) {
        $lu = isset($entry['lastUpdate']) ? trim((string) $entry['lastUpdate']) : '';
        if ($lu !== '') {
            $ts = strtotime($lu);
            if ($ts !== false) {
                return date('j', $ts) . ' ' . site_month_name_ru((int) date('n', $ts)) . ' ' . date('Y', $ts);
            }

            return $lu;
        }
    }

    return null;
}

function site_month_name_ru(int $month): string
{
    static $names = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    ];

    return $names[$month] ?? '';
}

/**
 * @param list<array<string, mixed>> $entries
 * @return list<string>
 */
function site_construction_progress_preview_photos(array $entries, int $limit = 9): array
{
    $limit = max(1, min(20, $limit));
    $out = [];
    foreach ($entries as $entry) {
        if (!isset($entry['photos']) || !is_array($entry['photos'])) {
            continue;
        }
        foreach ($entry['photos'] as $url) {
            if (!is_string($url) || trim($url) === '') {
                continue;
            }
            $out[] = trim($url);
            if (count($out) >= $limit) {
                return $out;
            }
        }
    }

    return $out;
}

/**
 * @param list<array<string, mixed>> $entries
 */
function site_construction_progress_total_photos(array $entries): int
{
    $n = 0;
    foreach ($entries as $entry) {
        if (isset($entry['photos']) && is_array($entry['photos'])) {
            $n += count($entry['photos']);
        }
    }

    return $n;
}

/**
 * @param array<string, mixed> $row
 * @return list<array<string, mixed>>
 */
function site_similar_complexes_entries(array $row): array
{
    $raw = $row['similarComplexes'] ?? null;
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $catalogObjectId = isset($item['catalogObjectId']) ? trim((string) $item['catalogObjectId']) : '';
        if ($catalogObjectId === '' || !site_validate_crm_object_id($catalogObjectId)) {
            continue;
        }
        $name = isset($item['name']) ? trim((string) $item['name']) : '';
        if ($name === '') {
            continue;
        }
        $minPrice = isset($item['minPrice']) && is_numeric($item['minPrice']) ? (float) $item['minPrice'] : null;
        $offersCount = isset($item['offersCount']) && is_numeric($item['offersCount'])
            ? (int) $item['offersCount']
            : null;
        $photoUrl = isset($item['photoUrl']) ? trim((string) $item['photoUrl']) : '';
        $address = isset($item['address']) ? trim((string) $item['address']) : '';
        $out[] = [
            'name' => $name,
            'minPrice' => $minPrice,
            'offersCount' => $offersCount,
            'photoUrl' => $photoUrl !== '' ? $photoUrl : null,
            'sourceUrl' => '/catalog/object/?id=' . rawurlencode($catalogObjectId),
            'address' => $address !== '' ? $address : null,
        ];
    }

    return $out;
}

/**
 * @param list<array<string, mixed>> $entries
 * @return list<array{key: string, label: string, buildingId: int|null, year: int, quarter: int, photos: list<string>, videos: list<string>, lastUpdate: string|null}>
 */
function site_construction_progress_period_groups(array $entries): array
{
    $map = [];
    foreach ($entries as $entry) {
        $buildingId = $entry['buildingId'] ?? null;
        $year = (int) ($entry['year'] ?? 0);
        $quarter = (int) ($entry['quarter'] ?? 0);
        $key = ($buildingId !== null ? (string) $buildingId : 'all') . ':' . $year . ':' . $quarter;
        if (!isset($map[$key])) {
            $periodLabel = site_construction_quarter_label($quarter);
            $label = trim($periodLabel . ($year > 0 ? ' ' . $year : ''));
            $buildingName = isset($entry['buildingName']) ? trim((string) $entry['buildingName']) : '';
            if ($buildingName !== '') {
                $label = $buildingName . ' · ' . $label;
            }
            $map[$key] = [
                'key' => $key,
                'label' => $label,
                'buildingId' => is_int($buildingId) ? $buildingId : null,
                'buildingName' => $buildingName !== '' ? $buildingName : null,
                'year' => $year,
                'quarter' => $quarter,
                'photos' => [],
                'videos' => [],
                'lastUpdate' => $entry['lastUpdate'] ?? null,
            ];
        }
        if (isset($entry['photos']) && is_array($entry['photos'])) {
            foreach ($entry['photos'] as $p) {
                if (is_string($p) && trim($p) !== '') {
                    $map[$key]['photos'][] = trim($p);
                }
            }
        }
        if (isset($entry['videos']) && is_array($entry['videos'])) {
            foreach ($entry['videos'] as $v) {
                if (is_string($v) && trim($v) !== '') {
                    $map[$key]['videos'][] = trim($v);
                }
            }
        }
    }
    $groups = array_values($map);
    usort($groups, static function (array $a, array $b): int {
        $ya = ((int) ($a['year'] ?? 0)) * 10 + (int) ($a['quarter'] ?? 0);
        $yb = ((int) ($b['year'] ?? 0)) * 10 + (int) ($b['quarter'] ?? 0);

        return $yb <=> $ya;
    });

    return $groups;
}

/**
 * @param list<array<string, mixed>> $entries
 * @return list<array{id: int|null, name: string}>
 */
function site_construction_progress_buildings(array $entries): array
{
    $map = [];
    foreach ($entries as $entry) {
        $id = isset($entry['buildingId']) && is_numeric($entry['buildingId'])
            ? (int) $entry['buildingId']
            : null;
        $name = isset($entry['buildingName']) ? trim((string) $entry['buildingName']) : '';
        if ($name === '') {
            continue;
        }
        $mapKey = $id !== null ? (string) $id : $name;
        if (!isset($map[$mapKey])) {
            $map[$mapKey] = ['id' => $id, 'name' => $name];
        }
    }

    return array_values($map);
}
