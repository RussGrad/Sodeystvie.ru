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
