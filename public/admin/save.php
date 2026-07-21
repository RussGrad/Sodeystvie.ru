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

$teamSkipped = 0;
$teamPhotoErrors = [];

if ($section === 'settings') {
    $payload = site_admin_sanitize_settings($_POST);
    foreach (['telegram', 'vk', 'max', 'whatsapp'] as $messengerType) {
        $key = 'messenger_show_' . $messengerType;
        $payload[$key] = isset($_POST[$key]) && (string) $_POST[$key] === '1' ? '1' : '0';
    }

    $currentSettings = site_admin_read_dataset('settings');
    $currentImage = is_array($currentSettings)
        ? site_home_lead_image_path()
        : '';
    $payload['home_lead_image'] = $currentImage;

    if (isset($_POST['home_lead_image_remove']) && (string) $_POST['home_lead_image_remove'] === '1') {
        if (!site_admin_delete_home_lead_image()) {
            site_admin_flash_set('Не удалось удалить изображение промоматериала. Проверьте права на assets/admin/.');
            header('Location: /admin/edit.php?section=settings', true, 302);
            exit;
        }
        $payload['home_lead_image'] = '';
    }

    $imageFile = $_FILES['home_lead_image_file'] ?? null;
    if (is_array($imageFile) && (int) ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = site_admin_handle_home_lead_image_upload($imageFile);
        if (!$upload['ok']) {
            site_admin_flash_set($upload['error'] ?? 'Не удалось загрузить изображение промоматериала.');
            header('Location: /admin/edit.php?section=settings', true, 302);
            exit;
        }
        $payload['home_lead_image'] = (string) ($upload['path'] ?? '');
    }
} elseif ($section === 'team') {
    require_once __DIR__ . '/../includes/site-team.php';
    $raw = $_POST['items'] ?? [];
    if (!is_array($raw)) {
        $raw = [];
    }
    $raw = array_values($raw);
    $payload = [];
    foreach ($raw as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $sanitized = site_admin_sanitize_team_row($row);
        if ($sanitized['id'] === '' || $sanitized['name'] === '') {
            $teamSkipped++;
            continue;
        }

        $existingPhoto = site_team_photo_path($sanitized['id']);
        if ($existingPhoto !== '') {
            $sanitized['photo'] = $existingPhoto;
        }

        $single = site_admin_team_photo_file_at_index((int) $index);
        if ($single !== null) {
            $_FILES['photo'] = $single;
            $upload = site_admin_handle_team_photo_upload($sanitized['id']);
            if (!$upload['ok']) {
                $teamPhotoErrors[] = $sanitized['name'] . ': ' . ($upload['error'] ?? 'ошибка фото');
            } elseif (!empty($upload['path'])) {
                $sanitized['photo'] = (string) $upload['path'];
            }
        }

        $payload[] = $sanitized;
    }
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

if ($section === 'settings' && isset($_FILES['messenger_icon']) && is_array($_FILES['messenger_icon'])) {
    site_admin_handle_messenger_icons_upload($_FILES['messenger_icon']);
}

$flash = 'Сохранено: ' . site_admin_dataset_label($section) . '.';
if ($section === 'team') {
    $flash .= ' На сайте: ' . count($payload) . ' чел.';
    if ($teamSkipped > 0) {
        $flash .= ' Пропущено без ID/имени: ' . $teamSkipped . '.';
    }
    if (count($teamPhotoErrors) > 0) {
        $flash .= ' Фото: ' . implode('; ', $teamPhotoErrors) . '.';
    }
}
site_admin_flash_set($flash);
header('Location: /admin/edit.php?section=' . rawurlencode($section), true, 302);
exit;
