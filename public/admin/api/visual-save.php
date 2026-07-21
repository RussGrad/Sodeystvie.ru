<?php

declare(strict_types=1);

/**
 * API визуального редактора: точечное сохранение settings и логотипа.
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
} elseif (isset($json) && is_array($json) && isset($json['action']) && is_string($json['action'])) {
    $action = $json['action'];
}

if ($action === 'save_field') {
    $field = '';
    $value = '';
    if (isset($_POST['field'], $_POST['value']) && is_string($_POST['field'])) {
        $field = $_POST['field'];
        $value = (string) $_POST['value'];
    } elseif (isset($json) && is_array($json)) {
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

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
