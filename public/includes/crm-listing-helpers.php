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
