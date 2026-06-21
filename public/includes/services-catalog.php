<?php

declare(strict_types=1);

/**
 * Каталог услуг: public/data/services.json (редактируется в /admin/).
 *
 * @return list<array{
 *   id: string,
 *   title: string,
 *   short: string,
 *   text: string,
 *   icon: string,
 *   href?: string,
 *   hrefLabel?: string,
 *   bullets?: list<string>
 * }>
 */
function sodeystvie_services_catalog(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $path = dirname(__DIR__) . '/data/services.json';
    if (is_readable($path)) {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (is_array($decoded) && count($decoded) > 0) {
            $out = [];
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $id = isset($row['id']) ? trim((string) $row['id']) : '';
                $title = isset($row['title']) ? trim((string) $row['title']) : '';
                if ($id === '' || $title === '') {
                    continue;
                }
                $item = [
                    'id' => $id,
                    'title' => $title,
                    'short' => isset($row['short']) ? trim((string) $row['short']) : '',
                    'text' => isset($row['text']) ? trim((string) $row['text']) : '',
                    'icon' => isset($row['icon']) ? trim((string) $row['icon']) : 'realtor',
                    'bullets' => [],
                ];
                if (isset($row['bullets']) && is_array($row['bullets'])) {
                    foreach ($row['bullets'] as $b) {
                        $s = trim((string) $b);
                        if ($s !== '') {
                            $item['bullets'][] = $s;
                        }
                    }
                }
                $href = isset($row['href']) ? trim((string) $row['href']) : '';
                if ($href !== '') {
                    $item['href'] = $href;
                }
                $hrefLabel = isset($row['hrefLabel']) ? trim((string) $row['hrefLabel']) : '';
                if ($hrefLabel !== '') {
                    $item['hrefLabel'] = $hrefLabel;
                }
                $out[] = $item;
            }
            if (count($out) > 0) {
                $cache = $out;

                return $cache;
            }
        }
    }

    $cache = sodeystvie_services_catalog_defaults();

    return $cache;
}

/**
 * @return list<array{id: string, title: string, short: string, text: string, icon: string, href?: string, hrefLabel?: string, bullets?: list<string>}>
 */
function sodeystvie_services_catalog_defaults(): array
{
    return [
        [
            'id' => 'valuation',
            'title' => 'Оценка имущества',
            'short' => 'Рыночная стоимость квартир, домов и участков для сделки, ипотеки или отчётности',
            'text' => 'Определяем актуальную рыночную стоимость жилой и коммерческой недвижимости в Иркутске и области.',
            'icon' => 'valuation',
            'bullets' => ['Оценка перед продажей или покупкой', 'Подготовка к ипотеке', 'Консультация по цене'],
        ],
    ];
}
