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

function site_fmt_rub_for_listing(?string $raw, ?string $objectType): string
{
    if ($objectType !== null && strtolower(trim($objectType)) === 'newbuilding') {
        return site_fmt_rub_from($raw);
    }

    return site_fmt_rub($raw);
}

function site_fmt_m2_for_listing(?float $areaTotal, ?string $priceRaw, ?string $objectType): ?string
{
    $value = site_fmt_m2($areaTotal, $priceRaw);
    if ($value === null) {
        return null;
    }
    if ($objectType !== null && strtolower(trim($objectType)) === 'newbuilding') {
        return 'от ' . $value;
    }

    return $value;
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
    $meta = site_complex_meta_entries($row);
    $buildings = site_developer_buildings_entries($row);
    $floorsLabel = site_complex_floors_label($row);
    $finishing = count($meta['finishingOptions']) > 0
        ? implode(', ', $meta['finishingOptions'])
        : '';

    $unit = site_listing_spec_rows_from_map([
        'Тип жилья' => $ctx['housingType'] ?? '',
        'Этажность' => $floorsLabel,
        'Варианты отделки' => $finishing,
        'Количество корпусов' => count($buildings) > 0 ? (string) count($buildings) : '',
        'Тип стен' => $meta['wallType'] ?? '',
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
 * @return array{
 *   complexClass: ?string,
 *   isVerified: bool,
 *   developerName: ?string,
 *   developerRegisteredYear: ?int,
 *   developerLogoUrl: ?string,
 *   finishingOptions: list<string>,
 *   wallType: ?string,
 *   mortgageMinRate: ?float
 * }
 */
function site_complex_meta_entries(array $row): array
{
    $empty = [
        'complexClass' => null,
        'isVerified' => false,
        'developerName' => null,
        'developerRegisteredYear' => null,
        'developerLogoUrl' => null,
        'finishingOptions' => [],
        'wallType' => null,
        'mortgageMinRate' => null,
        'monitoring' => null,
        'priceHistory' => [],
        'infrastructures' => [],
        'reviews' => [],
        'reviewsSummary' => null,
        'calculatorDefaults' => null,
    ];
    $raw = $row['complexMeta'] ?? null;
    if (!is_array($raw)) {
        return $empty;
    }
    $finishing = [];
    if (is_array($raw['finishingOptions'] ?? null)) {
        foreach ($raw['finishingOptions'] as $item) {
            if (is_string($item) && trim($item) !== '') {
                $finishing[] = trim($item);
            }
        }
    }

    return [
        'complexClass' => isset($raw['complexClass']) && is_string($raw['complexClass']) && trim($raw['complexClass']) !== ''
            ? trim($raw['complexClass'])
            : null,
        'isVerified' => !empty($raw['isVerified']),
        'developerName' => isset($raw['developerName']) && is_string($raw['developerName']) && trim($raw['developerName']) !== ''
            ? trim($raw['developerName'])
            : null,
        'developerRegisteredYear' => isset($raw['developerRegisteredYear']) && is_numeric($raw['developerRegisteredYear'])
            ? (int) $raw['developerRegisteredYear']
            : null,
        'developerLogoUrl' => isset($raw['developerLogoUrl']) && is_string($raw['developerLogoUrl']) && trim($raw['developerLogoUrl']) !== ''
            ? trim($raw['developerLogoUrl'])
            : null,
        'finishingOptions' => $finishing,
        'wallType' => isset($raw['wallType']) && is_string($raw['wallType']) && trim($raw['wallType']) !== ''
            ? trim($raw['wallType'])
            : null,
        'mortgageMinRate' => isset($raw['mortgageMinRate']) && is_numeric($raw['mortgageMinRate'])
            ? (float) $raw['mortgageMinRate']
            : null,
        'monitoring' => is_array($raw['monitoring'] ?? null) ? $raw['monitoring'] : null,
        'priceHistory' => is_array($raw['priceHistory'] ?? null) ? array_values($raw['priceHistory']) : [],
        'infrastructures' => is_array($raw['infrastructures'] ?? null) ? array_values($raw['infrastructures']) : [],
        'reviews' => is_array($raw['reviews'] ?? null) ? array_values($raw['reviews']) : [],
        'reviewsSummary' => is_array($raw['reviewsSummary'] ?? null) ? $raw['reviewsSummary'] : null,
        'calculatorDefaults' => is_array($raw['calculatorDefaults'] ?? null) ? $raw['calculatorDefaults'] : null,
    ];
}

function site_complex_reviews_rating_stars(?float $rating): string
{
    if ($rating === null || $rating <= 0) {
        return '';
    }
    $full = (int) round($rating);
    $full = max(0, min(5, $full));
    $stars = str_repeat('★', $full) . str_repeat('☆', 5 - $full);

    return $stars;
}

function site_developer_offers_flats_label(int $count): string
{
    $n = max(0, $count);
    $mod10 = $n % 10;
    $mod100 = $n % 100;
    if ($mod10 === 1 && $mod100 !== 11) {
        return $n . ' предложение';
    }
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
        return $n . ' предложения';
    }

    return $n . ' предложений';
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
            'kitchenArea' => isset($item['kitchenArea']) && is_numeric($item['kitchenArea'])
                ? (float) $item['kitchenArea']
                : null,
            'price' => isset($item['price']) && is_numeric($item['price']) ? (int) $item['price'] : null,
            'priceMax' => isset($item['priceMax']) && is_numeric($item['priceMax']) ? (int) $item['priceMax'] : null,
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

function site_developer_offer_room_label(?int $rooms): string
{
    if ($rooms === 0) {
        return 'Студия';
    }
    if ($rooms !== null && $rooms > 0) {
        return $rooms . '-комн.';
    }

    return 'Квартира';
}

function site_developer_offer_floor_text(?int $floor, ?int $floorMax, ?int $floorsTotal): string
{
    if ($floor === null || $floorsTotal === null || $floorsTotal <= 0) {
        return '';
    }
    if ($floorMax !== null && $floorMax > $floor) {
        return $floor . '–' . $floorMax . ' / ' . $floorsTotal;
    }

    return $floor . ' / ' . $floorsTotal;
}

function site_developer_offer_area_text(?float $area): ?string
{
    if ($area === null || $area <= 0) {
        return null;
    }

    return rtrim(rtrim(number_format($area, 2, '.', ''), '0'), '.') . ' м²';
}

function site_developer_offer_area_text_ru(?float $area): ?string
{
    if ($area === null || $area <= 0) {
        return null;
    }

    $formatted = number_format($area, 2, ',', '');
    $formatted = rtrim(rtrim($formatted, '0'), ',');

    return $formatted . ' м²';
}

/**
 * @param int|null $price
 * @param int|null $priceMax
 */
function site_developer_offer_price_text(?int $price, ?int $priceMax, int $flatsCount): string
{
    if ($price === null) {
        return '—';
    }
    if ($flatsCount > 1 || ($priceMax !== null && $priceMax > $price)) {
        return site_fmt_rub_from((string) $price);
    }

    return site_fmt_rub((string) $price);
}

/**
 * @param array<string, mixed> $row
 */
function site_developer_offers_client_payload(array $row): string
{
    $offers = site_developer_offers_entries($row);
    $complexTitle = site_newbuilding_page_title($row);
    $payload = [
        'complexTitle' => $complexTitle,
        'city' => trim((string) ($row['city'] ?? '')),
        'district' => trim((string) ($row['district'] ?? '')),
        'address' => trim((string) ($row['addressLine'] ?? $row['address'] ?? '')),
        'offers' => array_map(
            static function (array $offer) use ($complexTitle): array {
                $rooms = $offer['rooms'] ?? null;
                $areaText = site_developer_offer_area_text(
                    isset($offer['area']) && is_numeric($offer['area']) ? (float) $offer['area'] : null,
                );
                $kitchenText = site_developer_offer_area_text(
                    isset($offer['kitchenArea']) && is_numeric($offer['kitchenArea'])
                        ? (float) $offer['kitchenArea']
                        : null,
                );
                $floorText = site_developer_offer_floor_text(
                    $offer['floor'] ?? null,
                    $offer['floorMax'] ?? null,
                    $offer['floorsTotal'] ?? null,
                );
                $completion = site_developer_completion_label(
                    $offer['completionQuarter'] ?? null,
                    $offer['completionYear'] ?? null,
                );
                $roomLabel = site_developer_offer_room_label(is_int($rooms) ? $rooms : null);
                $titleParts = array_filter([$roomLabel, $areaText], static fn (string $v): bool => $v !== '');
                if ($floorText !== '') {
                    $titleParts[] = $floorText . ' эт.';
                }
                if ($complexTitle !== '') {
                    $titleParts[] = 'в ' . $complexTitle;
                }

                return [
                    'layoutId' => (string) ($offer['layoutId'] ?? ''),
                    'title' => implode(', ', $titleParts),
                    'roomLabel' => $roomLabel,
                    'area' => $areaText,
                    'kitchenArea' => $kitchenText,
                    'floor' => $floorText !== '' ? $floorText : null,
                    'buildingName' => $offer['buildingName'] ?? null,
                    'completion' => $completion !== '' ? $completion : null,
                    'price' => isset($offer['price']) && is_numeric($offer['price'])
                        ? (int) $offer['price']
                        : null,
                    'priceMax' => isset($offer['priceMax']) && is_numeric($offer['priceMax'])
                        ? (int) $offer['priceMax']
                        : null,
                    'flatsCount' => (int) ($offer['flatsCount'] ?? 1),
                    'planImageUrl' => $offer['planImageUrl'] ?? null,
                ];
            },
            $offers,
        ),
    ];

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
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
 * @return array{min: ?int, max: ?int}
 */
function site_developer_offers_price_bounds(array $row): array
{
    $offers = site_developer_offers_entries($row);
    $min = null;
    $max = null;
    foreach ($offers as $offer) {
        if (isset($offer['price']) && is_numeric($offer['price'])) {
            $price = (int) $offer['price'];
            $min = $min === null ? $price : min($min, $price);
            $max = $max === null ? $price : max($max, $price);
        }
        if (isset($offer['priceMax']) && is_numeric($offer['priceMax'])) {
            $priceMax = (int) $offer['priceMax'];
            $max = $max === null ? $priceMax : max($max, $priceMax);
        }
    }
    if ($min === null && isset($row['price']) && is_numeric($row['price'])) {
        $min = (int) $row['price'];
        $max = $max ?? $min;
    }

    return ['min' => $min, 'max' => $max];
}

function site_fmt_millions_rub(?int $amount, int $decimals = 1): ?string
{
    if ($amount === null || $amount <= 0) {
        return null;
    }
    $millions = $amount / 1_000_000;
    if ($millions >= 10) {
        $decimals = 0;
    } elseif ($millions < 1) {
        return number_format($amount, 0, '.', ' ');
    }
    $formatted = number_format($millions, $decimals, ',', ' ');
    if ($decimals > 0) {
        $formatted = rtrim(rtrim($formatted, '0'), ',');
    }

    return $formatted;
}

function site_fmt_price_range_millions(?int $min, ?int $max): string
{
    $minLabel = site_fmt_millions_rub($min);
    if ($minLabel === null) {
        return '—';
    }
    $maxLabel = site_fmt_millions_rub($max);
    if ($maxLabel === null || $max === null || $max <= $min) {
        return 'от ' . $minLabel . ' млн ₽';
    }

    return 'от ' . $minLabel . ' до ' . $maxLabel . ' млн ₽';
}

/**
 * @param array<string, mixed> $row
 * @return array{min: ?float, max: ?float}
 */
function site_developer_offers_area_bounds(array $row): array
{
    $offers = site_developer_offers_entries($row);
    $min = null;
    $max = null;
    foreach ($offers as $offer) {
        if (!isset($offer['area']) || !is_numeric($offer['area'])) {
            continue;
        }
        $area = (float) $offer['area'];
        $min = $min === null ? $area : min($min, $area);
        $max = $max === null ? $area : max($max, $area);
    }

    return ['min' => $min, 'max' => $max];
}

function site_fmt_area_range(?float $min, ?float $max): string
{
    if ($min === null) {
        return '';
    }
    $fmt = static function (float $value): string {
        $text = number_format($value, 2, ',', '');
        $text = rtrim(rtrim($text, '0'), ',');

        return $text;
    };
    if ($max === null || abs($max - $min) < 0.01) {
        return $fmt($min) . ' м²';
    }

    return $fmt($min) . '–' . $fmt($max) . ' м²';
}

/**
 * @param array<string, mixed> $row
 */
function site_complex_floors_label(array $row): string
{
    $buildings = site_developer_buildings_entries($row);
    $floors = [];
    foreach ($buildings as $building) {
        if (isset($building['floors']) && is_numeric($building['floors'])) {
            $floors[] = (int) $building['floors'];
        }
    }
    if (count($floors) === 0) {
        $offers = site_developer_offers_entries($row);
        foreach ($offers as $offer) {
            if (isset($offer['floorMax']) && is_numeric($offer['floorMax'])) {
                $floors[] = (int) $offer['floorMax'];
            } elseif (isset($offer['floor']) && is_numeric($offer['floor'])) {
                $floors[] = (int) $offer['floor'];
            }
        }
    }
    if (count($floors) === 0) {
        return '';
    }
    $min = min($floors);
    $max = max($floors);

    return $min === $max ? (string) $min : $min . '–' . $max;
}

/**
 * @param array<string, mixed> $row
 */
function site_complex_readiness_summary(array $row): string
{
    $buildings = site_developer_buildings_entries($row);
    if (count($buildings) === 0) {
        return '';
    }
    $readyCount = 0;
    foreach ($buildings as $building) {
        if (!empty($building['isReady'])) {
            $readyCount++;
        }
    }
    if ($readyCount === count($buildings)) {
        return 'Сдан';
    }
    if ($readyCount > 0) {
        return 'Частично сдан';
    }

    return '';
}

/**
 * @param array<string, mixed> $row
 */
function site_render_complex_sidebar(array $row, string $fallbackPriceText): void
{
    $title = site_newbuilding_page_title($row);
    $bounds = site_developer_offers_price_bounds($row);
    $priceRange = site_fmt_price_range_millions($bounds['min'], $bounds['max']);
    if ($priceRange === '—' && $fallbackPriceText !== '') {
        $priceRange = $fallbackPriceText;
    }
    $readiness = site_complex_readiness_summary($row);
    $offersTotal = site_developer_offers_total($row);
    $hasOffers = $offersTotal > 0;
    $meta = site_complex_meta_entries($row);
    $finishing = count($meta['finishingOptions']) > 0
        ? implode(', ', $meta['finishingOptions'])
        : '';
    $siteOfferBadge = isset($row['siteOfferBadge']) ? trim((string) $row['siteOfferBadge']) : '';
    ?>
    <div class="complex-sidebar" data-complex-sidebar>
        <div class="complex-sidebar__actions">
            <button
                type="button"
                class="listing-object__fav"
                data-listing-fav
                aria-label="Добавить в избранное"
                aria-pressed="false"
            >
                <svg class="listing-object__fav-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 21s-6.7-4.35-9.33-8.1C.5 9.5 2.5 5.5 6.5 5.5c2 0 3.2 1.2 4 2.3 0.8-1.1 2-2.3 4-2.3 4 0 6 4 3.83 7.4C18.7 16.65 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </button>
        </div>
        <?php if ($meta['complexClass'] !== null || $meta['isVerified']) { ?>
            <div class="complex-sidebar__badges">
                <?php if ($meta['complexClass'] !== null) { ?>
                    <span class="complex-badge complex-badge--class"><?php echo htmlspecialchars($meta['complexClass'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php } ?>
                <?php if ($meta['isVerified']) { ?>
                    <span class="complex-badge complex-badge--verified">Проверено</span>
                <?php } ?>
            </div>
        <?php } ?>
        <h1 class="complex-sidebar__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($siteOfferBadge !== '') { ?>
            <p class="listing-object__offer-status"><?php echo htmlspecialchars($siteOfferBadge, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
        <p class="complex-sidebar__price" data-testid="priceRange"><?php echo htmlspecialchars($priceRange, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($readiness !== '') { ?>
            <p class="complex-sidebar__status">
                <span class="complex-sidebar__status-icon" aria-hidden="true">✓</span>
                <?php echo htmlspecialchars($readiness, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php } ?>
        <?php if ($hasOffers) { ?>
            <p class="complex-sidebar__offers-count">
                <a class="complex-sidebar__offers-link" href="#complex-offers"><?php echo site_developer_offers_flats_label($offersTotal); ?></a>
            </p>
        <?php } ?>
        <?php if ($finishing !== '') { ?>
            <div class="complex-sidebar__finishing">
                <span class="complex-sidebar__finishing-label">Варианты отделки</span>
                <span class="complex-sidebar__finishing-value"><?php echo htmlspecialchars($finishing, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php } ?>
        <?php if ($meta['developerName'] !== null) { ?>
            <div class="complex-sidebar__developer">
                <?php if ($meta['developerLogoUrl'] !== null) { ?>
                    <img
                        class="complex-sidebar__developer-logo"
                        src="<?php echo htmlspecialchars($meta['developerLogoUrl'], ENT_QUOTES, 'UTF-8'); ?>"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        referrerpolicy="no-referrer"
                    >
                <?php } ?>
                <div class="complex-sidebar__developer-text">
                    <span class="complex-sidebar__developer-name"><?php echo htmlspecialchars($meta['developerName'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($meta['developerRegisteredYear'] !== null) { ?>
                        <span class="complex-sidebar__developer-year">Основан в <?php echo (int) $meta['developerRegisteredYear']; ?> г.</span>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
        <p class="complex-sidebar__note">Подробности о ЖК уточняйте у менеджера</p>
        <div class="complex-sidebar__cta">
            <button type="button" class="listing-object__lead-btn complex-sidebar__lead-btn" data-lead-open>
                Оставить заявку
            </button>
            <a class="complex-sidebar__more" href="#complex-about">Подробнее</a>
        </div>
    </div>
    <?php
}

/**
 * @param array<string, mixed> $row
 * @param array{hasOffers: bool, hasConstruction: bool} $sections
 */
function site_render_complex_nav(array $row, array $sections): void
{
    $tabs = [];
    if ($sections['hasOffers']) {
        $tabs[] = ['id' => 'complex-offers', 'label' => 'Квартиры'];
    }
    $tabs[] = ['id' => 'complex-mortgage', 'label' => 'Ипотека'];
    $tabs[] = ['id' => 'complex-about', 'label' => 'О жилом комплексе'];
    $tabs[] = ['id' => 'complex-map', 'label' => 'Инфраструктура'];
    if ($sections['hasConstruction']) {
        $tabs[] = ['id' => 'complex-construction', 'label' => 'Ход строительства'];
    }
    if (!empty($sections['hasMonitoring'])) {
        $tabs[] = ['id' => 'complex-monitoring', 'label' => 'Мониторинг'];
    }
    if (count($tabs) === 0) {
        return;
    }
    ?>
    <nav class="complex-nav" data-complex-nav aria-label="Разделы страницы ЖК">
        <div class="complex-nav__track">
            <?php foreach ($tabs as $i => $tab) { ?>
                <a
                    class="complex-nav__tab<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    href="#<?php echo htmlspecialchars($tab['id'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-complex-nav-link
                ><?php echo htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php } ?>
        </div>
    </nav>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function site_complex_default_mortgage_price(array $row): ?int
{
    $meta = site_complex_meta_entries($row);
    $calc = $meta['calculatorDefaults'];
    if (is_array($calc) && isset($calc['price']) && is_numeric($calc['price'])) {
        return (int) $calc['price'];
    }
    $bounds = site_developer_offers_price_bounds($row);

    return $bounds['min'];
}

/**
 * @param array<string, mixed> $row
 */
function site_render_complex_mortgage_calculator(array $row): void
{
    $meta = site_complex_meta_entries($row);
    $defaultPrice = site_complex_default_mortgage_price($row);
    $defaultDeposit = null;
    $defaultTerm = 30;
    $calc = $meta['calculatorDefaults'];
    if (is_array($calc)) {
        if (isset($calc['deposit']) && is_numeric($calc['deposit'])) {
            $defaultDeposit = (int) $calc['deposit'];
        }
        if (isset($calc['termYears']) && is_numeric($calc['termYears'])) {
            $defaultTerm = max(1, min(40, (int) $calc['termYears']));
        }
    }
    $rate = $meta['mortgageMinRate'];
    if ($rate === null || $rate <= 0) {
        $rate = 16.9;
    }
    $depositPercent = 20;
    if ($defaultPrice !== null && $defaultPrice > 0 && $defaultDeposit !== null) {
        $depositPercent = (int) round(($defaultDeposit / $defaultPrice) * 100);
    }
    ?>
    <section class="listing-object__section listing-object__section--mortgage" id="complex-mortgage" aria-labelledby="complex-mortgage-title">
        <h2 class="listing-object__section-title" id="complex-mortgage-title">Ипотека</h2>
        <div class="complex-mortgage-calc" data-complex-mortgage-calc>
            <?php if ($meta['mortgageMinRate'] !== null && $meta['mortgageMinRate'] > 0) { ?>
                <p class="complex-mortgage-calc__hint">Ставка от <?php echo htmlspecialchars(number_format($meta['mortgageMinRate'], 1, ',', ''), ENT_QUOTES, 'UTF-8'); ?>%</p>
            <?php } ?>
            <div class="complex-mortgage-calc__grid">
                <label class="complex-mortgage-calc__field">
                    <span class="complex-mortgage-calc__label">Стоимость квартиры</span>
                    <input
                        class="complex-mortgage-calc__input"
                        type="text"
                        inputmode="numeric"
                        data-mortgage-price
                        value="<?php echo $defaultPrice !== null ? htmlspecialchars(number_format($defaultPrice, 0, '.', ' '), ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                </label>
                <label class="complex-mortgage-calc__field">
                    <span class="complex-mortgage-calc__label">Первоначальный взнос, %</span>
                    <input
                        class="complex-mortgage-calc__input"
                        type="number"
                        min="0"
                        max="100"
                        data-mortgage-down-percent
                        value="<?php echo (int) $depositPercent; ?>"
                    >
                </label>
                <label class="complex-mortgage-calc__field">
                    <span class="complex-mortgage-calc__label">Срок, лет</span>
                    <input
                        class="complex-mortgage-calc__input"
                        type="number"
                        min="1"
                        max="40"
                        data-mortgage-years
                        value="<?php echo (int) $defaultTerm; ?>"
                    >
                </label>
                <label class="complex-mortgage-calc__field">
                    <span class="complex-mortgage-calc__label">Ставка, %</span>
                    <input
                        class="complex-mortgage-calc__input"
                        type="number"
                        min="0"
                        step="0.1"
                        data-mortgage-rate
                        value="<?php echo htmlspecialchars(number_format($rate, 1, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </label>
            </div>
            <div class="complex-mortgage-calc__result">
                <p class="complex-mortgage-calc__monthly">от <span data-mortgage-monthly>—</span> / мес.</p>
                <p class="complex-mortgage-calc__loan">Сумма кредита: <span data-mortgage-loan>—</span></p>
            </div>
            <div class="complex-mortgage-calc__actions">
                <button type="button" class="listing-object__lead-btn" data-lead-open data-lead-topic="mortgage">Оставить заявку</button>
                <a class="complex-mortgage-calc__more" href="/mortgage/">Все программы</a>
            </div>
        </div>
    </section>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function site_render_complex_price_history(array $row): void
{
    $meta = site_complex_meta_entries($row);
    $history = is_array($meta['priceHistory']) ? $meta['priceHistory'] : [];
    ?>
    <section class="listing-object__section" id="complex-price-history" aria-labelledby="complex-price-history-title">
        <h2 class="listing-object__section-title" id="complex-price-history-title">История цен в ЖК</h2>
        <?php if (count($history) > 0) { ?>
            <div class="complex-price-history">
                <table class="complex-price-history__table">
                    <thead>
                        <tr>
                            <th>Период</th>
                            <th>Цена</th>
                            <th>Цена за м²</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $point) {
                            if (!is_array($point)) {
                                continue;
                            }
                            $date = isset($point['date']) ? trim((string) $point['date']) : '';
                            $price = isset($point['price']) && is_numeric($point['price'])
                                ? site_fmt_rub_from((string) (int) $point['price'])
                                : '—';
                            $priceM2 = isset($point['pricePerM2']) && is_numeric($point['pricePerM2'])
                                ? site_fmt_rub_from((string) (int) $point['pricePerM2'])
                                : '—';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($priceM2, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="complex-empty-state">
                <p>История изменения цен по этому ЖК пока недоступна.</p>
            </div>
        <?php } ?>
    </section>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function site_render_complex_reviews(array $row): void
{
    $meta = site_complex_meta_entries($row);
    $reviews = is_array($meta['reviews']) ? $meta['reviews'] : [];
    $summary = is_array($meta['reviewsSummary'] ?? null) ? $meta['reviewsSummary'] : null;
    $title = site_newbuilding_page_title($row);
    $sectionTitle = isset($summary['title']) && is_string($summary['title']) && trim($summary['title']) !== ''
        ? trim($summary['title'])
        : 'Отзывы о ' . $title;
    $summaryRating = isset($summary['rating']) && is_numeric($summary['rating']) ? (float) $summary['rating'] : null;
    $summaryCount = isset($summary['count']) && is_numeric($summary['count']) ? (int) $summary['count'] : count($reviews);
    ?>
    <section class="listing-object__section" id="complex-reviews" aria-labelledby="complex-reviews-title">
        <h2 class="listing-object__section-title" id="complex-reviews-title"><?php echo htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if (count($reviews) > 0) { ?>
            <?php if ($summaryRating !== null || $summaryCount > 0) { ?>
                <div class="complex-reviews__summary">
                    <?php if ($summaryRating !== null) { ?>
                        <div class="complex-reviews__summary-grade"><?php echo htmlspecialchars(number_format($summaryRating, 1, ',', ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="complex-reviews__summary-stars" aria-hidden="true"><?php echo htmlspecialchars(site_complex_reviews_rating_stars($summaryRating), ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php } ?>
                    <?php if ($summaryCount > 0) { ?>
                        <div class="complex-reviews__summary-count"><?php echo (int) $summaryCount; ?> оценок</div>
                    <?php } ?>
                </div>
            <?php } ?>
            <div class="complex-reviews">
                <?php foreach ($reviews as $review) {
                    if (!is_array($review)) {
                        continue;
                    }
                    $text = isset($review['text']) ? trim((string) $review['text']) : '';
                    if ($text === '') {
                        continue;
                    }
                    $author = isset($review['author']) ? trim((string) $review['author']) : '';
                    $rating = isset($review['rating']) && is_numeric($review['rating']) ? (float) $review['rating'] : null;
                    $date = isset($review['date']) ? trim((string) $review['date']) : '';
                    $reply = isset($review['reply']) && is_array($review['reply']) ? $review['reply'] : null;
                    ?>
                    <article class="complex-reviews__item">
                        <header class="complex-reviews__head">
                            <?php if ($author !== '') { ?>
                                <strong class="complex-reviews__author"><?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php } ?>
                            <?php if ($rating !== null) { ?>
                                <span class="complex-reviews__rating" aria-label="Оценка <?php echo htmlspecialchars(number_format($rating, 1, ',', ''), ENT_QUOTES, 'UTF-8'); ?> из 5"><?php echo htmlspecialchars(site_complex_reviews_rating_stars($rating), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php } ?>
                            <?php if ($date !== '') { ?>
                                <time class="complex-reviews__date"><?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></time>
                            <?php } ?>
                        </header>
                        <p class="complex-reviews__text"><?php echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php if (is_array($reply) && !empty($reply['text'])) {
                            $replyAuthor = isset($reply['author']) ? trim((string) $reply['author']) : '';
                            $replyDate = isset($reply['date']) ? trim((string) $reply['date']) : '';
                            ?>
                            <div class="complex-reviews__reply">
                                <?php if ($replyAuthor !== '') { ?>
                                    <strong class="complex-reviews__reply-author"><?php echo htmlspecialchars($replyAuthor, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php } ?>
                                <?php if ($replyDate !== '') { ?>
                                    <span class="complex-reviews__reply-date"><?php echo htmlspecialchars($replyDate, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php } ?>
                                <p class="complex-reviews__reply-text"><?php echo nl2br(htmlspecialchars((string) $reply['text'], ENT_QUOTES, 'UTF-8')); ?></p>
                            </div>
                        <?php } ?>
                    </article>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="complex-empty-state">
                <p>Пока нет отзывов об этом жилом комплексе.</p>
                <button type="button" class="listing-object__lead-btn" data-lead-open>Оставить отзыв</button>
            </div>
        <?php } ?>
    </section>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function site_render_complex_sber_monitoring(array $row): void
{
    $meta = site_complex_meta_entries($row);
    $monitoring = is_array($meta['monitoring']) ? $meta['monitoring'] : null;
    if ($monitoring === null) {
        return;
    }
    $active = isset($monitoring['isMonitoringActive']) ? (int) $monitoring['isMonitoringActive'] : 0;
    if ($active <= 0 && empty($monitoring['buildingsReady'])) {
        return;
    }
    ?>
    <section class="listing-object__section" id="complex-monitoring" aria-labelledby="complex-monitoring-title">
        <h2 class="listing-object__section-title" id="complex-monitoring-title">Мониторинг строительства от СберБанка</h2>
        <div class="complex-monitoring">
            <div class="complex-monitoring__grid">
                <?php if ($active > 0) { ?>
                    <div class="complex-monitoring__stat">
                        <span class="complex-monitoring__value"><?php echo (int) $active; ?></span>
                        <span class="complex-monitoring__label">корпусов на мониторинге</span>
                    </div>
                <?php } ?>
                <?php if (isset($monitoring['buildingsReady']) && is_numeric($monitoring['buildingsReady'])) { ?>
                    <div class="complex-monitoring__stat">
                        <span class="complex-monitoring__value"><?php echo (int) $monitoring['buildingsReady']; ?></span>
                        <span class="complex-monitoring__label">корпусов сдано</span>
                    </div>
                <?php } ?>
                <?php if (isset($monitoring['buildingsReadyInTime']) && is_numeric($monitoring['buildingsReadyInTime'])) { ?>
                    <div class="complex-monitoring__stat">
                        <span class="complex-monitoring__value"><?php echo (int) $monitoring['buildingsReadyInTime']; ?></span>
                        <span class="complex-monitoring__label">сдано в срок</span>
                    </div>
                <?php } ?>
                <?php if (isset($monitoring['buildingsReadyWithMinorDelay']) && is_numeric($monitoring['buildingsReadyWithMinorDelay'])) { ?>
                    <div class="complex-monitoring__stat">
                        <span class="complex-monitoring__value"><?php echo (int) $monitoring['buildingsReadyWithMinorDelay']; ?></span>
                        <span class="complex-monitoring__label">с небольшой задержкой</span>
                    </div>
                <?php } ?>
            </div>
            <?php if (!empty($meta['isVerified'])) { ?>
                <p class="complex-monitoring__verified">Жилой комплекс проверен ДомКлик</p>
            <?php } ?>
        </div>
    </section>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function site_complex_poi_categories(array $row): array
{
    $meta = site_complex_meta_entries($row);
    $categories = [];
    foreach ($meta['infrastructures'] as $poi) {
        if (!is_array($poi)) {
            continue;
        }
        $cat = isset($poi['category']) ? trim((string) $poi['category']) : '';
        if ($cat === '') {
            $cat = 'other';
        }
        $categories[$cat] = true;
    }

    return array_keys($categories);
}

/**
 * @param array<string, mixed> $row
 */
function site_render_complex_main_intro(array $row, string $addressText, bool $hasMapCoords = false): void
{
    $areaBounds = site_developer_offers_area_bounds($row);
    $areaLabel = site_fmt_area_range($areaBounds['min'], $areaBounds['max']);
    $floorsLabel = site_complex_floors_label($row);
    $buildings = site_developer_buildings_entries($row);
    $buildingCount = count($buildings);
    $meta = site_complex_meta_entries($row);
    $finishing = count($meta['finishingOptions']) > 0
        ? implode(', ', $meta['finishingOptions'])
        : '';
    $facts = [];
    if ($areaLabel !== '') {
        $facts[] = ['label' => 'Площади квартир', 'value' => $areaLabel];
    }
    $facts[] = ['label' => 'Тип жилья', 'value' => 'квартиры'];
    if ($floorsLabel !== '') {
        $facts[] = ['label' => 'Этажность', 'value' => $floorsLabel];
    }
    if ($finishing !== '') {
        $facts[] = ['label' => 'Варианты отделки', 'value' => $finishing];
    }
    if ($buildingCount > 0) {
        $facts[] = [
            'label' => 'Количество корпусов',
            'value' => $buildingCount . ' корпусов',
        ];
    }
    if ($meta['wallType'] !== null) {
        $facts[] = ['label' => 'Тип стен', 'value' => $meta['wallType']];
    }
    ?>
    <section class="complex-main-intro" aria-label="О жилом комплексе">
        <?php if ($addressText !== '') { ?>
            <div class="complex-main-intro__address-row">
                <?php if ($hasMapCoords) { ?>
                    <a class="complex-main-intro__map-pin" href="#complex-map" aria-label="Показать на карте">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" fill-rule="evenodd" d="M12 22c2.25 0 7-8.86 7-13.04C19 5.12 16.14 2 12 2S5 5.12 5 8.96C5 13.14 9.75 22 12 22Zm0-10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" clip-rule="evenodd"/></svg>
                    </a>
                <?php } ?>
                <div class="complex-main-intro__address">
                    <span class="complex-main-intro__address-text"><?php echo htmlspecialchars($addressText, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        <?php } ?>
        <?php if (count($facts) > 0) { ?>
            <dl class="complex-main-intro__facts">
                <?php foreach ($facts as $fact) { ?>
                    <div class="complex-main-intro__fact">
                        <dt><?php echo htmlspecialchars($fact['label'], ENT_QUOTES, 'UTF-8'); ?></dt>
                        <dd><?php echo htmlspecialchars($fact['value'], ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                <?php } ?>
            </dl>
            <a class="complex-main-intro__more" href="#complex-about">Подробнее о ЖК</a>
        <?php } ?>
    </section>
    <?php
}

/**
 * @param array<string, mixed> $row
 */
function site_render_developer_offers_section(array $row): void
{
    $offers = site_developer_offers_entries($row);
    $buildings = site_developer_buildings_entries($row);
    $complexTitle = site_newbuilding_page_title($row);
    $offersTotal = site_developer_offers_total($row);
    $offersJson = site_developer_offers_client_payload($row);
    $hasOffers = count($offers) > 0;
    ?>
    <section
        class="listing-object__section listing-object__section--developer-offers"
        id="complex-offers"
        aria-labelledby="listing-developer-offers-title"
        data-developer-offers
    >
        <div class="developer-offers__head">
            <h2 class="listing-object__section-title" id="listing-developer-offers-title">
                <?php if ($offersTotal > 0) { ?>
                    <?php echo (int) $offersTotal; ?> предложений от застройщика в <?php echo htmlspecialchars($complexTitle, ENT_QUOTES, 'UTF-8'); ?>
                <?php } else { ?>
                    Предложения от застройщика в <?php echo htmlspecialchars($complexTitle, ENT_QUOTES, 'UTF-8'); ?>
                <?php } ?>
            </h2>
        </div>
        <?php if (!$hasOffers) { ?>
            <div class="complex-empty-state">
                <p>Планировки и цены от застройщика скоро появятся. Оставьте заявку — менеджер уточнит наличие и актуальные предложения.</p>
                <button type="button" class="listing-object__lead-btn" data-lead-open data-lead-topic="newbuilding">Узнать наличие</button>
            </div>
        <?php } else { ?>
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
        <div class="developer-offers__layout" data-testid="room-layout">
            <?php foreach ($offers as $offer) {
                $buildingId = $offer['buildingId'] ?? null;
                $rooms = $offer['rooms'] ?? null;
                $roomLabel = site_developer_offer_room_label(is_int($rooms) ? $rooms : null);
                $areaValue = isset($offer['area']) && is_numeric($offer['area']) ? (float) $offer['area'] : null;
                $area = site_developer_offer_area_text_ru($areaValue);
                $floorText = site_developer_offer_floor_text(
                    $offer['floor'] ?? null,
                    $offer['floorMax'] ?? null,
                    $offer['floorsTotal'] ?? null,
                );
                $completion = site_developer_completion_label(
                    $offer['completionQuarter'] ?? null,
                    $offer['completionYear'] ?? null,
                );
                $price = isset($offer['price']) && is_numeric($offer['price']) ? (int) $offer['price'] : null;
                $priceMax = isset($offer['priceMax']) && is_numeric($offer['priceMax']) ? (int) $offer['priceMax'] : null;
                $flatsCount = (int) ($offer['flatsCount'] ?? 1);
                $priceText = site_developer_offer_price_text($price, $priceMax, $flatsCount);
                $planUrl = isset($offer['planImageUrl']) ? trim((string) $offer['planImageUrl']) : '';
                $layoutId = (string) ($offer['layoutId'] ?? '');
                $flatsLabel = site_developer_offers_flats_label($flatsCount);
                $floorParts = $floorText !== '' ? explode(' / ', $floorText, 2) : [];
                ?>
                <a
                    class="developer-offers__card"
                    href="#complex-offers"
                    data-building-id="<?php echo $buildingId !== null ? (int) $buildingId : ''; ?>"
                    data-layout-id="<?php echo htmlspecialchars($layoutId, ENT_QUOTES, 'UTF-8'); ?>"
                    data-developer-offer-open
                >
                    <div class="developer-offers__card-body">
                        <div class="developer-offers__card-plan">
                            <?php if ($planUrl !== '') { ?>
                                <picture>
                                    <img
                                        class="developer-offers__card-plan-img"
                                        src="<?php echo htmlspecialchars($planUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="Фото планировки"
                                        loading="lazy"
                                        decoding="async"
                                        referrerpolicy="no-referrer"
                                    >
                                </picture>
                            <?php } else { ?>
                                <div class="developer-offers__card-plan-placeholder" aria-hidden="true"></div>
                            <?php } ?>
                        </div>
                        <div class="developer-offers__card-info">
                            <div class="developer-offers__card-grid">
                                <div class="developer-offers__card-row developer-offers__card-row--head">
                                    <div class="developer-offers__card-value developer-offers__card-value--rooms" data-testid="rooms">
                                        <?php echo htmlspecialchars($roomLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($offer['buildingName'])) { ?>
                                        <div class="developer-offers__card-muted"><?php echo htmlspecialchars((string) $offer['buildingName'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php } ?>
                                </div>
                                <div class="developer-offers__card-row">
                                    <div class="developer-offers__card-muted">Площадь</div>
                                    <div class="developer-offers__card-value"><?php echo $area !== null ? htmlspecialchars($area, ENT_QUOTES, 'UTF-8') : '—'; ?></div>
                                </div>
                                <div class="developer-offers__card-row">
                                    <div class="developer-offers__card-muted">Этаж</div>
                                    <div class="developer-offers__card-value" data-testid="floorsInfo">
                                        <?php if (count($floorParts) === 2) { ?>
                                            <?php echo htmlspecialchars($floorParts[0], ENT_QUOTES, 'UTF-8'); ?>
                                            <span class="developer-offers__card-floor-total"> / <?php echo htmlspecialchars($floorParts[1], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php } else { ?>
                                            <?php echo $floorText !== '' ? htmlspecialchars($floorText, ENT_QUOTES, 'UTF-8') : '—'; ?>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="developer-offers__card-row">
                                    <div class="developer-offers__card-muted">Сдача</div>
                                    <div class="developer-offers__card-value"><?php echo $completion !== '' ? htmlspecialchars($completion, ENT_QUOTES, 'UTF-8') : '—'; ?></div>
                                </div>
                                <div class="developer-offers__card-price">
                                    <p class="developer-offers__card-price-value" data-testid="price"><?php echo htmlspecialchars($priceText, ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <div class="developer-offers__card-flats" data-testid="not-mobile-flats">
                                <?php echo htmlspecialchars($flatsLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php } ?>
        </div>
        <?php if ($offersTotal > count($offers)) { ?>
            <p class="developer-offers__note">
                Показаны <?php echo count($offers); ?> из <?php echo (int) $offersTotal; ?> предложений. Уточните наличие и цену у менеджера.
            </p>
        <?php } ?>
        <script type="application/json" id="developer-offers-data"><?php echo $offersJson; ?></script>
        <div class="developer-offer-modal" data-developer-offer-modal hidden aria-hidden="true">
            <div class="developer-offer-modal__backdrop" data-developer-offer-close tabindex="-1" aria-hidden="true"></div>
            <div
                class="developer-offer-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="developer-offer-modal-title"
                tabindex="-1"
            >
                <button type="button" class="developer-offer-modal__close" data-developer-offer-close aria-label="Закрыть">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="developer-offer-modal__layout">
                    <div class="developer-offer-modal__media">
                        <img class="developer-offer-modal__plan" data-offer-plan alt="" decoding="async" referrerpolicy="no-referrer" hidden>
                        <div class="developer-offer-modal__plan-placeholder" data-offer-plan-placeholder aria-hidden="true"></div>
                    </div>
                    <div class="developer-offer-modal__body">
                        <h2 class="developer-offer-modal__title" id="developer-offer-modal-title" data-offer-title></h2>
                        <p class="developer-offer-modal__price" data-offer-price></p>
                        <p class="developer-offer-modal__price-m2" data-offer-price-m2 hidden></p>
                        <dl class="developer-offer-modal__specs" data-offer-specs></dl>
                        <p class="developer-offer-modal__location" data-offer-location hidden></p>
                        <button
                            type="button"
                            class="developer-offer-modal__cta"
                            data-lead-open
                            data-lead-topic="newbuilding"
                            data-developer-offer-close
                        >Оставить заявку</button>
                    </div>
                </div>
            </div>
        </div>
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
    if ($ctx['kind'] === 'newbuilding') {
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
function site_render_listing_object_specs(array $sections, ?string $sectionId = null): void
{
    if (count($sections) === 0) {
        return;
    }
    $gridClass = 'listing-object__specs-grid' . (count($sections) === 1 ? ' listing-object__specs-grid--one' : '');
    $idAttr = $sectionId !== null && $sectionId !== ''
        ? ' id="' . htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8') . '"'
        : '';
    ?>
    <section class="listing-object__specs" aria-labelledby="listing-specs-title"<?php echo $idAttr; ?>>
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

    // Один запрос на объект (кэш 1 мин для быстрого появления предложений после импорта в CRM).
    $detail = site_http_get_json_cached(site_crm_listings_url($id), 8, 60);
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

    $priceText = site_fmt_rub_for_listing($priceRaw, $objectType);
    $priceM2 = site_fmt_m2_for_listing($areaTotal, $priceRaw, $objectType);
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
        'operation' => $str('operation'),
        'rent_mode' => $str('rent_mode'),
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

/**
 * @param array<string, mixed> $row
 */
function site_listing_is_rent_deal(array $row): bool
{
    $dealText = mb_strtolower(trim((string) ($row['dealLineValue'] ?? '')), 'UTF-8');

    return $dealText !== '' && mb_strpos($dealText, 'аренд', 0, 'UTF-8') !== false;
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
 * Вторичка и коммерция разделены на продажу и аренду.
 *
 * @return array{
 *   groups: array{
 *     newbuild: list<array<string, mixed>>,
 *     resale: array{sale: list<array<string, mixed>>, rent: list<array<string, mixed>>},
 *     commercial: array{sale: list<array<string, mixed>>, rent: list<array<string, mixed>>}
 *   },
 *   error: ?string
 * }
 */
function site_crm_fetch_featured_listing_groups(int $limitPerGroup = 8): array
{
    $limitPerGroup = max(1, min($limitPerGroup, 12));
    $crm = site_crm_fetch_featured_listings(48);
    $groups = [
        'newbuild' => [],
        'resale' => ['sale' => [], 'rent' => []],
        'commercial' => ['sale' => [], 'rent' => []],
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
        if ($group === 'newbuild') {
            if (!site_listing_in_irkutsk($row)) {
                continue;
            }
            if (count($groups['newbuild']) >= $limitPerGroup) {
                continue;
            }
            $groups['newbuild'][] = $row;
            continue;
        }

        $dealKey = site_listing_is_rent_deal($row) ? 'rent' : 'sale';
        if (count($groups[$group][$dealKey]) >= $limitPerGroup) {
            continue;
        }
        $groups[$group][$dealKey][] = $row;
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
    $siteOfferBadge = isset($row['siteOfferBadge']) ? trim((string) $row['siteOfferBadge']) : '';
    $badgeSub = '';
    if ($siteOfferBadge !== '') {
        $badgeClass = $objectType === 'newbuilding'
            ? 'featured-card__badge--reserved'
            : 'featured-card__badge--deposit';
        $badgeText = $siteOfferBadge;
    } elseif ($hasDiscount) {
        $badgeClass = 'featured-card__badge--exclusive';
        $badgeText = 'Эксклюзивное предложение';
    $discountPct = site_listing_discount_percent($priceOldRaw, $priceRaw);
        if ($discountPct !== null && $discountPct > 0) {
            $badgeSub = 'Цена ниже на ' . $discountPct . '%';
        }
    } else {
        $badgeClass = 'featured-card__badge--top';
        $badgeText = 'Топ';
    }
    ?>
    <li class="featured__cell">
        <article class="featured-card">
            <a class="featured-card__link" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="featured-card__media featured-card__media--tone-<?php echo (int) $tone; ?>" aria-hidden="true">
                    <?php if ($coverPhoto !== '') {
                        echo site_crm_photo_img($coverPhoto, $cardTitle, 'featured-card__photo', '', 'featured');
                    } ?>
                    <span class="featured-card__badge <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="featured-card__badge-main"><?php echo htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($badgeSub !== '') { ?>
                            <span class="featured-card__badge-sub"><?php echo htmlspecialchars($badgeSub, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php } ?>
                    </span>
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
                        <p class="featured-card__price"><?php echo htmlspecialchars(site_fmt_rub_for_listing($priceRaw, $objectType), ENT_QUOTES, 'UTF-8'); ?></p>
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
    foreach (['operation', 'rent_mode', 'rooms', 'price', 'area_min', 'area_max', 'price_min', 'price_max'] as $key) {
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
    $dealRaw = isset($row['dealLineValue']) ? trim((string) $row['dealLineValue']) : '';
    $dealText = mb_strtolower($dealRaw, 'UTF-8');
    $descText = mb_strtolower(trim((string) ($row['description'] ?? '')), 'UTF-8');
    $op = $filters['operation'] ?? '';
    if ($op !== '') {
        $isSale = $dealText === '' || mb_strpos($dealText, 'продаж', 0, 'UTF-8') !== false;
        $isBuy = mb_strpos($dealText, 'покуп', 0, 'UTF-8') !== false;
        $isRent = mb_strpos($dealText, 'аренд', 0, 'UTF-8') !== false;
        if ($op === 'sale' && !$isSale) {
            return false;
        }
        if ($op === 'buy' && !$isBuy) {
            return false;
        }
        if ($op === 'rent' && !$isRent) {
            return false;
        }
    }

    $rentMode = $filters['rent_mode'] ?? '';
    if ($rentMode !== '') {
        $rentHaystack = $dealText . ' ' . $descText;
        $isDaily = mb_strpos($rentHaystack, 'посуточ', 0, 'UTF-8') !== false
            || mb_strpos($rentHaystack, 'сутк', 0, 'UTF-8') !== false;
        $isRent = mb_strpos($dealText, 'аренд', 0, 'UTF-8') !== false;
        if ($rentMode === 'daily' && !($isRent && $isDaily)) {
            return false;
        }
        if ($rentMode === 'long' && !($isRent && !$isDaily)) {
            return false;
        }
    }

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
            'price' => site_fmt_rub_for_listing($priceRaw, $objectType),
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
