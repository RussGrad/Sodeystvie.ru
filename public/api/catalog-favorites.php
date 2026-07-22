<?php

declare(strict_types=1);

/**
 * Карточки избранных объектов для каталога (id из localStorage на клиенте).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/crm-listing-helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!function_exists('site_crm_fetch_listing_by_id')) {
    http_response_code(503);
    echo json_encode(
        [
            'html' => '',
            'count' => 0,
            'requested' => 0,
            'missing' => [],
            'error' => 'Сервис избранного временно недоступен',
        ],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$rawIds = isset($_GET['ids']) && is_string($_GET['ids']) ? trim($_GET['ids']) : '';
if ($rawIds === '') {
    echo json_encode(['html' => '', 'count' => 0, 'requested' => 0, 'missing' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$ids = [];
foreach (explode(',', $rawIds) as $part) {
    $id = trim($part);
    if ($id === '' || !site_validate_crm_object_id($id)) {
        continue;
    }
    if (!in_array($id, $ids, true)) {
        $ids[] = $id;
    }
    if (count($ids) >= 50) {
        break;
    }
}

if (count($ids) === 0) {
    echo json_encode(['html' => '', 'count' => 0, 'requested' => 0, 'missing' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

ob_start();
$found = [];
$missing = [];
foreach ($ids as $id) {
    $row = site_crm_fetch_listing_by_id($id);
    if ($row === null) {
        $missing[] = $id;
        continue;
    }
    site_render_catalog_listing_card($row);
    $found[] = $id;
}
$html = ob_get_clean() ?: '';

echo json_encode(
    [
        'html' => $html,
        'count' => count($found),
        'requested' => count($ids),
        'found' => $found,
        'missing' => $missing,
    ],
    JSON_UNESCAPED_UNICODE
);
