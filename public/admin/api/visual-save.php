<?php

declare(strict_types=1);

/**
 * API визуального редактора: точечное сохранение settings, датасетов и логотипа.
 * Только для залогиненного администратора + CSRF.
 */

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/site-admin.php';
require_once dirname(__DIR__, 2) . '/includes/visual-editor.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

site_admin_session_start();

if (!site_admin_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Требуется вход администратора'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

$json = null;
$csrf = '';
if (isset($_POST['csrf']) && is_string($_POST['csrf'])) {
    $csrf = $_POST['csrf'];
} else {
    $raw = file_get_contents('php://input');
    $json = is_string($raw) ? json_decode($raw, true) : null;
    if (is_array($json) && isset($json['csrf']) && is_string($json['csrf'])) {
        $csrf = $json['csrf'];
    }
}

if (!site_admin_verify_csrf($csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Неверный CSRF-токен'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = '';
if (isset($_POST['action']) && is_string($_POST['action'])) {
    $action = $_POST['action'];
} elseif (is_array($json) && isset($json['action']) && is_string($json['action'])) {
    $action = $json['action'];
}

if ($action === 'save_field') {
    $field = '';
    $value = '';
    if (isset($_POST['field'], $_POST['value']) && is_string($_POST['field'])) {
        $field = $_POST['field'];
        $value = (string) $_POST['value'];
    } elseif (is_array($json)) {
        $field = isset($json['field']) && is_string($json['field']) ? $json['field'] : '';
        $value = isset($json['value']) ? (string) $json['value'] : '';
    }

    $allowed = site_visual_editor_settings_fields();
    if ($field === '' || !isset($allowed[$field])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Неизвестное поле'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ok = site_visual_editor_patch_settings([$field => $value]);
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Не удалось сохранить'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'field' => $field,
        'value' => mb_substr(trim($value), 0, $allowed[$field]),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save_item') {
    $dataset = '';
    $itemId = '';
    $field = '';
    $value = '';
    if (
        isset($_POST['dataset'], $_POST['id'], $_POST['field'])
        && is_string($_POST['dataset'])
        && is_string($_POST['id'])
        && is_string($_POST['field'])
    ) {
        $dataset = $_POST['dataset'];
        $itemId = $_POST['id'];
        $field = $_POST['field'];
        $value = (string) ($_POST['value'] ?? '');
    } elseif (is_array($json)) {
        $dataset = isset($json['dataset']) && is_string($json['dataset']) ? $json['dataset'] : '';
        $itemId = isset($json['id']) && is_string($json['id']) ? $json['id'] : '';
        $field = isset($json['field']) && is_string($json['field']) ? $json['field'] : '';
        $value = isset($json['value']) ? (string) $json['value'] : '';
    }

    try {
        $result = site_ve_save_dataset_field($dataset, $itemId, $field, $value);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'dataset' => $dataset,
        'id' => $itemId,
        'field' => $field,
        'value' => $result['value'],
        'warning' => $result['warning'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'upload_logo') {
    $file = $_FILES['logo'] ?? null;
    if (!is_array($file)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Файл не передан'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = site_visual_editor_handle_logo_upload($file);
    if (!$result['ok']) {
        http_response_code(400);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'upload_image') {
    $dataset = isset($_POST['dataset']) && is_string($_POST['dataset']) ? $_POST['dataset'] : '';
    $itemId = isset($_POST['id']) && is_string($_POST['id']) ? $_POST['id'] : '';
    $file = $_FILES['file'] ?? $_FILES['logo'] ?? null;
    if (!is_array($file)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Файл не передан'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($dataset === 'deal-cards') {
        $result = site_visual_editor_handle_deal_card_image_upload($itemId, $file);
    } elseif ($dataset === 'cases') {
        $result = site_visual_editor_handle_case_image_upload($itemId, $file);
    } elseif ($dataset === 'services') {
        $result = site_visual_editor_handle_service_image_upload($itemId, $file);
    } elseif ($dataset === 'magazine') {
        $result = site_visual_editor_handle_magazine_asset_upload($itemId, $file);
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Загрузка для этого блока не поддерживается'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$result['ok']) {
        http_response_code(400);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete_image') {
    $dataset = '';
    $itemId = '';
    if (isset($_POST['dataset'], $_POST['id']) && is_string($_POST['dataset']) && is_string($_POST['id'])) {
        $dataset = $_POST['dataset'];
        $itemId = $_POST['id'];
    } elseif (is_array($json)) {
        $dataset = isset($json['dataset']) && is_string($json['dataset']) ? $json['dataset'] : '';
        $itemId = isset($json['id']) && is_string($json['id']) ? $json['id'] : '';
    }

    if ($dataset === 'magazine') {
        $result = site_visual_editor_delete_magazine_asset($itemId);
    } else {
        $result = site_visual_editor_delete_item_image($dataset, $itemId);
    }
    if (!$result['ok']) {
        http_response_code(400);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'create_item') {
    $dataset = '';
    $title = '';
    if (isset($_POST['dataset']) && is_string($_POST['dataset'])) {
        $dataset = $_POST['dataset'];
        $title = isset($_POST['title']) ? (string) $_POST['title'] : '';
    } elseif (is_array($json)) {
        $dataset = isset($json['dataset']) && is_string($json['dataset']) ? $json['dataset'] : '';
        $title = isset($json['title']) ? (string) $json['title'] : '';
    }

    if ($dataset !== 'services') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Создание поддерживается только для услуг'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $created = site_ve_create_service($title);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => true, 'item' => $created], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete_item') {
    $dataset = '';
    $itemId = '';
    if (isset($_POST['dataset'], $_POST['id']) && is_string($_POST['dataset']) && is_string($_POST['id'])) {
        $dataset = $_POST['dataset'];
        $itemId = $_POST['id'];
    } elseif (is_array($json)) {
        $dataset = isset($json['dataset']) && is_string($json['dataset']) ? $json['dataset'] : '';
        $itemId = isset($json['id']) && is_string($json['id']) ? $json['id'] : '';
    }

    if ($dataset !== 'services') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Удаление поддерживается только для услуг'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        site_ve_delete_service($itemId);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => true, 'id' => $itemId], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
