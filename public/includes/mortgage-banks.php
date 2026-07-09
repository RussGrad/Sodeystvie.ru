<?php

declare(strict_types=1);

/**
 * Список банков-партнёров для ипотечного калькулятора.
 * Редактируйте public/data/mortgage-banks.json и логотипы в public/assets/banks/.
 *
 * @return list<array{id: string, name: string, abbr: string, logo: string, rateDelta: float, enabled: bool}>
 */
function sodeystvie_mortgage_banks(): array
{
    $path = dirname(__DIR__) . '/data/mortgage-banks.json';
    if (!is_readable($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }

    $out = [];
    foreach ($data as $row) {
        if (!is_array($row) || empty($row['enabled'])) {
            continue;
        }
        $id = isset($row['id']) ? trim((string) $row['id']) : '';
        $name = isset($row['name']) ? trim((string) $row['name']) : '';
        if ($id === '' || $name === '' || !preg_match('/^[a-zA-Z0-9_-]{1,32}$/', $id)) {
            continue;
        }
        if (count($out) >= 40) {
            break;
        }
        $logo = isset($row['logo']) ? trim((string) $row['logo']) : '';
        if ($logo !== '' && preg_match('/^[a-zA-Z0-9._-]+\\.(png|svg|webp|jpe?g)$/i', $logo) !== 1) {
            $logo = '';
        }
        $out[] = [
            'id' => $id,
            'name' => $name,
            'abbr' => isset($row['abbr']) ? trim((string) $row['abbr']) : mb_substr($name, 0, 1),
            'logo' => $logo,
            'rateDelta' => isset($row['rateDelta']) ? (float) $row['rateDelta'] : 0.0,
            'enabled' => true,
        ];
    }

    return $out;
}

/**
 * JSON для встраивания в страницу (безопасно для script type=application/json).
 */
function sodeystvie_mortgage_banks_json(): string
{
    return site_json_for_html_script(sodeystvie_mortgage_banks(), '[]');
}
