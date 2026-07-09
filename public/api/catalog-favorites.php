<?php

declare(strict_types=1);

/**
 * Карточки избранных объектов для каталога (id из localStorage на клиенте).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/crm-listing-helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$rawIds = isset($_GET['ids']) && is_string($_GET['ids']) ? trim($_GET['ids']) : '';
if ($rawIds === '') {
    echo json_encode(['html' => '', 'count' => 0, 'requested' => 0], JSON_UNESCAPED_UNICODE);
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
    echo json_encode(['html' => '', 'count' => 0, 'requested' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

ob_start();
$rendered = 0;
foreach ($ids as $id) {
    $row = site_crm_fetch_listing_by_id($id);
    if ($row !== null) {
        site_render_catalog_listing_card($row);
        $rendered++;
    }
}
$html = ob_get_clean() ?: '';

echo json_encode(
    [
        'html' => $html,
        'count' => $rendered,
        'requested' => count($ids),
    ],
    JSON_UNESCAPED_UNICODE
);
