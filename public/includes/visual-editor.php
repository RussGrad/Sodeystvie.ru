<?php

declare(strict_types=1);

/**
 * Визуальный редактор сайта (только для залогиненного администратора).
 * Включается параметром ?ve=1 при активной сессии /admin/.
 */

require_once __DIR__ . '/site-admin.php';

function site_visual_editor_enabled(): bool
{
    if (!site_admin_is_logged_in()) {
        return false;
    }

    $flag = $_GET['ve'] ?? '';

    return $flag === '1' || $flag === 'true';
}

/**
 * Разрешённые поля settings для точечного сохранения.
 *
 * @return array<string, int> key => max length
 */
function site_visual_editor_settings_fields(): array
{
    return [
        'phone_tel' => 400,
        'phone_display' => 400,
        'email' => 400,
        'address' => 400,
        'work_hours' => 400,
        'legal_inn' => 400,
        'legal_ogrn' => 400,
        'slogan_short' => 400,
        'slogan_hero' => 2000,
        'hero_headline' => 400,
        'home_lead_title' => 240,
        'home_lead_description' => 800,
        'home_lead_cta' => 80,
        'nav_label_home' => 80,
        'nav_label_catalog' => 80,
        'nav_label_services' => 80,
        'nav_label_mortgage' => 80,
        'nav_label_about' => 80,
        'nav_label_contacts' => 80,
        'telegram_url' => 400,
        'vk_url' => 400,
        'max_url' => 400,
        'whatsapp_url' => 400,
    ];
}

/**
 * HTML-атрибуты для кликабельного элемента в режиме VE.
 *
 * @param 'text'|'textarea'|'tel'|'email'|'url'|'image' $type
 */
function site_ve_attrs(string $field, string $type = 'text', string $label = ''): string
{
    if (!site_visual_editor_enabled()) {
        return '';
    }

    $allowed = site_visual_editor_settings_fields();
    if ($type !== 'image' && !isset($allowed[$field]) && $field !== 'logo') {
        return '';
    }

    $parts = [
        'data-ve-field="' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . '"',
        'data-ve-type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"',
    ];
    if ($label !== '') {
        $parts[] = 'data-ve-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"';
    }

    return ' ' . implode(' ', $parts);
}

function site_visual_editor_boot_json(): string
{
    $payload = [
        'csrf' => site_admin_csrf_token(),
        'saveUrl' => '/admin/api/visual-save.php',
        'exitUrl' => '/admin/',
        'fields' => array_keys(site_visual_editor_settings_fields()),
    ];

    return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function site_visual_editor_render_assets(): void
{
    if (!site_visual_editor_enabled()) {
        return;
    }

    $cssV = (string) (@filemtime(__DIR__ . '/../css/visual-editor.css') ?: time());
    $jsV = (string) (@filemtime(__DIR__ . '/../js/visual-editor.js') ?: time());
    ?>
    <link rel="stylesheet" href="/css/visual-editor.css?v=<?php echo htmlspecialchars($cssV, ENT_QUOTES, 'UTF-8'); ?>">
    <script type="application/json" id="ve-boot"><?php echo site_visual_editor_boot_json(); ?></script>
    <script src="/js/visual-editor.js?v=<?php echo htmlspecialchars($jsV, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <?php
}

/**
 * Частичное обновление site-settings.json.
 *
 * @param array<string, string> $patch
 */
function site_visual_editor_patch_settings(array $patch): bool
{
    require_once __DIR__ . '/site-content.php';

    $current = site_admin_read_dataset('settings');
    if (!is_array($current)) {
        $current = [];
    }

    $allowed = site_visual_editor_settings_fields();
    foreach ($patch as $key => $value) {
        if (!is_string($key) || !isset($allowed[$key])) {
            continue;
        }
        $current[$key] = mb_substr(trim((string) $value), 0, $allowed[$key]);
    }

    return site_admin_write_dataset('settings', $current);
}

/**
 * Загрузка логотипа в assets/brand/logo-premium.*
 *
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 * @return array{ok: bool, path?: string, error?: string}
 */
function site_visual_editor_handle_logo_upload(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Выберите файл логотипа'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки (код ' . $error . ')'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 4 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Логотип должен быть не больше 4 МБ'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Некорректная загрузка'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        default => null,
    };
    if ($ext === null) {
        return ['ok' => false, 'error' => 'Допустимы PNG, JPG, WebP или SVG'];
    }
    if ($ext !== 'svg' && @getimagesize($tmp) === false) {
        return ['ok' => false, 'error' => 'Файл не является изображением'];
    }

    $dir = dirname(__DIR__) . '/assets/brand';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Не удалось создать каталог brand'];
    }

    $staged = $dir . '/.logo-premium-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $staged)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить файл'];
    }

    $destination = $dir . '/logo-premium.' . ($ext === 'jpg' ? 'png' : $ext);
    // jpg → save as png name only if png; keep jpg as logo-premium.jpg
    if ($ext === 'jpg') {
        $destination = $dir . '/logo-premium.jpg';
    } elseif ($ext === 'png') {
        $destination = $dir . '/logo-premium.png';
    } elseif ($ext === 'webp') {
        $destination = $dir . '/logo-premium.webp';
    } else {
        $destination = $dir . '/logo-premium.svg';
    }

    if (!@rename($staged, $destination)) {
        @unlink($staged);

        return ['ok' => false, 'error' => 'Не удалось установить логотип'];
    }

    // Для PNG/WebP — основной logo-premium.webp/png читает logo-markup
    if ($ext === 'jpg') {
        // также скопируем как png не делаем — markup ищет webp затем png
        @copy($destination, $dir . '/logo-premium.png');
    }

    $public = '/assets/brand/' . basename($destination) . '?v=' . (string) time();

    return ['ok' => true, 'path' => $public];
}
