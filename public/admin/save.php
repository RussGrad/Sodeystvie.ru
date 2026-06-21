<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

site_admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/', true, 302);
    exit;
}

$section = isset($_POST['section']) ? trim((string) $_POST['section']) : '';
$token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';

if (!site_admin_verify_csrf($token)) {
    site_admin_flash_set('Ошибка безопасности (CSRF). Повторите сохранение.');
    header('Location: /admin/edit.php?section=' . rawurlencode($section), true, 302);
    exit;
}

if (!in_array($section, site_admin_editable_datasets(), true)) {
    site_admin_flash_set('Неизвестный раздел.');
    header('Location: /admin/', true, 302);
    exit;
}

if ($section === 'settings') {
    $payload = site_admin_sanitize_settings($_POST);
} else {
    $raw = $_POST['items'] ?? [];
    if (!is_array($raw)) {
        $raw = [];
    }
    $payload = site_admin_sanitize_dataset($section, array_values($raw));
}

if (!site_admin_write_dataset($section, $payload)) {
    site_admin_flash_set('Не удалось сохранить файл. Проверьте права на каталог data/.');
    header('Location: /admin/edit.php?section=' . rawurlencode($section), true, 302);
    exit;
}

// Загрузка фото команды (опционально, отдельные поля photo_file[id])
if ($section === 'team' && isset($_FILES['photo_file']) && is_array($_FILES['photo_file'])) {
    $names = $_FILES['photo_file']['name'] ?? [];
    if (is_array($names)) {
        foreach (array_keys($names) as $memberId) {
            if (!is_string($memberId) || $memberId === '') {
                continue;
            }
            $single = [
                'name' => $_FILES['photo_file']['name'][$memberId] ?? '',
                'type' => $_FILES['photo_file']['type'][$memberId] ?? '',
                'tmp_name' => $_FILES['photo_file']['tmp_name'][$memberId] ?? '',
                'error' => $_FILES['photo_file']['error'][$memberId] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['photo_file']['size'][$memberId] ?? 0,
            ];
            if (($single['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $_FILES['photo'] = $single;
            site_admin_handle_team_photo_upload($memberId);
        }
    }
}

site_admin_flash_set('Сохранено: ' . site_admin_dataset_label($section) . '.');
header('Location: /admin/edit.php?section=' . rawurlencode($section), true, 302);
exit;
