<?php

declare(strict_types=1);

/**
 * Расширенные юридические услуги: public/data/legal-services.json.
 *
 * @return array{intro: string, items: list<array{
 *   id: string,
 *   title: string,
 *   short: string,
 *   text: string,
 *   bullets: list<string>
 * }>}
 */
function sodeystvie_legal_services_page(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $defaults = sodeystvie_legal_services_defaults();
    $path = dirname(__DIR__) . '/data/legal-services.json';
    if (!is_readable($path)) {
        $cache = $defaults;

        return $cache;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        $cache = $defaults;

        return $cache;
    }

    $intro = isset($decoded['intro']) ? trim((string) $decoded['intro']) : $defaults['intro'];
    $items = [];
    if (isset($decoded['items']) && is_array($decoded['items'])) {
        foreach ($decoded['items'] as $row) {
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
                'bullets' => [],
            ];
            if (isset($row['bullets']) && is_array($row['bullets'])) {
                foreach ($row['bullets'] as $bullet) {
                    $s = trim((string) $bullet);
                    if ($s !== '') {
                        $item['bullets'][] = $s;
                    }
                }
            }
            $items[] = $item;
        }
    }

    $cache = [
        'intro' => $intro !== '' ? $intro : $defaults['intro'],
        'items' => count($items) > 0 ? $items : $defaults['items'],
    ];

    return $cache;
}

/**
 * @return array{intro: string, items: list<array{id: string, title: string, short: string, text: string, bullets: list<string>}>}
 */
function sodeystvie_legal_services_defaults(): array
{
    return [
        'intro' => 'Юристы агентства сопровождают сделки с недвижимостью и помогают в сложных правовых ситуациях.',
        'items' => [],
    ];
}
