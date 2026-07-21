<?php

declare(strict_types=1);

require_once __DIR__ . '/site-content.php';

/**
 * Данные карточек «Продать / Купить / Сдать / Снять».
 */

/**
 * @return list<array{
 *   id: string,
 *   variant: string,
 *   title: string,
 *   subtitle: string,
 *   action: string,
 *   topic?: string,
 *   href?: string,
 *   aria: string,
 *   imageAlt: string
 * }>
 */
function site_deal_cards_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $rows = site_admin_read_dataset('deal-cards');
    $out = [];
    if (is_array($rows)) {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $clean = site_admin_sanitize_deal_card_row($row);
            if ($clean !== null) {
                $clean['variant'] = $clean['id'];
                $out[] = $clean;
            }
        }
    }

    if (count($out) === 0) {
        $out = site_deal_cards_defaults();
    }

    $cache = $out;

    return $cache;
}

/**
 * @return list<array<string, string>>
 */
function site_deal_cards_defaults(): array
{
    return [
        [
            'id' => 'sell',
            'variant' => 'sell',
            'title' => 'Продать',
            'subtitle' => 'Квартиру',
            'action' => 'lead',
            'topic' => 'sell-evaluation',
            'aria' => 'Оставить заявку на продажу квартиры',
            'imageAlt' => 'Консультация по продаже квартиры',
        ],
        [
            'id' => 'buy',
            'variant' => 'buy',
            'title' => 'Купить',
            'subtitle' => 'Квартиру',
            'action' => 'link',
            'href' => '/catalog/?operation=buy&type=flat',
            'aria' => 'Перейти в каталог покупки квартир',
            'imageAlt' => 'Подбор квартиры с риэлтором',
        ],
        [
            'id' => 'rent-out',
            'variant' => 'rent-out',
            'title' => 'Сдать',
            'subtitle' => 'Квартиру',
            'action' => 'lead',
            'topic' => 'rent-out',
            'aria' => 'Оставить заявку на сдачу квартиры',
            'imageAlt' => 'Сдача квартиры в аренду',
        ],
        [
            'id' => 'rent-in',
            'variant' => 'rent-in',
            'title' => 'Снять',
            'subtitle' => 'Квартиру',
            'action' => 'link',
            'href' => '/catalog/?operation=rent&type=flat',
            'aria' => 'Перейти в каталог аренды квартир',
            'imageAlt' => 'Просмотр квартиры в аренду',
        ],
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, string>|null
 */
function site_admin_sanitize_deal_card_row(array $row): ?array
{
    $id = preg_replace('/[^a-z-]/', '', (string) ($row['id'] ?? $row['variant'] ?? '')) ?? '';
    if ($id === '') {
        return null;
    }

    $action = trim((string) ($row['action'] ?? 'lead'));
    if (!in_array($action, ['lead', 'link'], true)) {
        $action = 'lead';
    }

    $out = [
        'id' => $id,
        'title' => mb_substr(trim((string) ($row['title'] ?? '')), 0, 80),
        'subtitle' => mb_substr(trim((string) ($row['subtitle'] ?? '')), 0, 80),
        'action' => $action,
        'aria' => mb_substr(trim((string) ($row['aria'] ?? '')), 0, 200),
        'imageAlt' => mb_substr(trim((string) ($row['imageAlt'] ?? '')), 0, 160),
    ];

    if ($action === 'lead') {
        $out['topic'] = mb_substr(trim((string) ($row['topic'] ?? '')), 0, 80);
    } else {
        $href = trim((string) ($row['href'] ?? '/catalog/'));
        if ($href !== '' && (str_starts_with($href, '/') || preg_match('#^https?://#i', $href))) {
            $out['href'] = mb_substr($href, 0, 300);
        } else {
            $out['href'] = '/catalog/';
        }
    }

    if ($out['title'] === '') {
        return null;
    }

    return $out;
}

function site_deal_card_image_path(string $variant): ?string
{
    $safe = preg_replace('/[^a-z-]/', '', $variant) ?: 'sell';
    $dir = dirname(__DIR__) . '/assets/deal-cards';
    $webBase = '/assets/deal-cards/' . $safe;

    foreach (['.jpg', '.jpeg', '.png', '.webp'] as $ext) {
        if (is_file($dir . '/' . $safe . $ext)) {
            return $webBase . $ext;
        }
    }

    return null;
}
