<?php

declare(strict_types=1);

/**
 * Визуальный редактор сайта (только для залогиненного администратора).
 * ?ve=1 включает режим и ставит cookie; режим держится на всех страницах до ?ve=0.
 */

require_once __DIR__ . '/site-admin.php';

const SITE_VE_COOKIE = 'sodeystvie_ve';

function site_ve_set_active_cookie(bool $active): void
{
    if ($active) {
        $_COOKIE[SITE_VE_COOKIE] = '1';
    } else {
        unset($_COOKIE[SITE_VE_COOKIE]);
    }

    if (headers_sent()) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    setcookie(SITE_VE_COOKIE, $active ? '1' : '', [
        'expires' => $active ? 0 : time() - 3600,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function site_visual_editor_enabled(): bool
{
    static $enabled = null;
    if ($enabled !== null) {
        return $enabled;
    }

    if (!site_admin_is_logged_in()) {
        $enabled = false;

        return false;
    }

    $flag = isset($_GET['ve']) ? (string) $_GET['ve'] : '';
    if ($flag === '0' || $flag === 'off' || $flag === 'false') {
        site_ve_set_active_cookie(false);
        $enabled = false;

        return false;
    }
    if ($flag === '1' || $flag === 'true') {
        site_ve_set_active_cookie(true);
        $enabled = true;

        return true;
    }

    $enabled = isset($_COOKIE[SITE_VE_COOKIE]) && $_COOKIE[SITE_VE_COOKIE] === '1';

    return $enabled;
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
        'home_lead_eyebrow' => 120,
        'home_lead_badge' => 120,
        'home_lead_calc_label' => 120,
        'home_lead_calc_offset' => 8,
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
        'deal_cards_kicker' => 80,
        'cases_section_title' => 120,
        'cases_section_lead' => 400,
        'magazine_photo_rotate' => 8,
        'magazine_photo_scale' => 8,
        'magazine_photo_x' => 8,
        'magazine_photo_y' => 8,
        'magazine_logo_rotate' => 8,
        'magazine_logo_scale' => 8,
        'magazine_logo_x' => 8,
        'magazine_logo_y' => 8,
        'magazine_pen_rotate' => 8,
        'magazine_pen_scale' => 8,
        'magazine_pen_x' => 8,
        'magazine_pen_y' => 8,
        'magazine_layout_x' => 8,
        'magazine_layout_y' => 8,
        'magazine_layout_rotate' => 8,
        'magazine_layout_scale' => 8,
    ];
}

/**
 * Поля датасетов, доступные в VE: dataset => field => max length.
 *
 * @return array<string, array<string, int>>
 */
function site_visual_editor_dataset_fields(): array
{
    return [
        'team' => [
            'name' => 120,
            'role' => 120,
            'experience' => 160,
        ],
        'services' => [
            'title' => 120,
            'short' => 240,
            'text' => 2000,
            'icon' => 40,
            'image' => 0,
        ],
        'cases' => [
            'tag' => 40,
            'title' => 160,
            'result' => 120,
            'text' => 1200,
            'image' => 0,
        ],
        'deal-cards' => [
            'title' => 80,
            'subtitle' => 80,
            'imageAlt' => 160,
            'image' => 0,
        ],
    ];
}

/**
 * @return array<string, string> icon id => label
 */
function site_ve_service_icon_options(): array
{
    return [
        'valuation' => 'Оценка',
        'realtor' => 'Риелтор / дом',
        'selection' => 'Подбор',
        'analytics' => 'Аналитика',
        'chain' => 'Цепочка',
        'mortgage' => 'Ипотека',
        'legal' => 'Юридические',
    ];
}

/**
 * HTML-атрибуты для кликабельного элемента в режиме VE.
 *
 * @param 'text'|'textarea'|'tel'|'email'|'url'|'image'|'icon'|'mag-image' $type
 */
function site_ve_attrs(
    string $field,
    string $type = 'text',
    string $label = '',
    string $dataset = '',
    string $itemId = '',
    string $currentValue = ''
): string {
    if (!site_visual_editor_enabled()) {
        return '';
    }

    if ($dataset !== '') {
        $map = site_visual_editor_dataset_fields();
        if ($itemId === '') {
            return '';
        }
        if ($type === 'image') {
            if ($field !== 'image' || !isset($map[$dataset])) {
                return '';
            }
        } elseif (!isset($map[$dataset][$field])) {
            return '';
        }
        if ($dataset === 'team' && site_ve_team_from_crm()) {
            return '';
        }
    } else {
        $allowed = site_visual_editor_settings_fields();
        if ($type === 'mag-image') {
            if (!in_array($field, ['magazine_photo', 'magazine_logo', 'magazine_pen'], true)) {
                return '';
            }
        } elseif ($type === 'mag-layout') {
            if ($field !== 'magazine_layout') {
                return '';
            }
        } elseif ($type === 'mag-plate') {
            if ($field !== 'home_lead_calc_label') {
                return '';
            }
        } elseif ($type !== 'image' && !isset($allowed[$field]) && $field !== 'logo') {
            return '';
        }
    }

    $parts = [
        'data-ve-field="' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . '"',
        'data-ve-type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"',
    ];
    if ($label !== '') {
        $parts[] = 'data-ve-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"';
    }
    if ($dataset !== '') {
        $parts[] = 'data-ve-dataset="' . htmlspecialchars($dataset, ENT_QUOTES, 'UTF-8') . '"';
        $parts[] = 'data-ve-item="' . htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8') . '"';
    }
    if ($currentValue !== '') {
        $parts[] = 'data-ve-value="' . htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8') . '"';
    }
    if ($type === 'mag-image') {
        $kind = match ($field) {
            'magazine_logo' => 'logo',
            'magazine_pen' => 'pen',
            default => 'photo',
        };
        $transform = site_magazine_transform($kind);
        $parts[] = 'data-ve-rotate="' . (string) $transform['rotate'] . '"';
        $parts[] = 'data-ve-scale="' . (string) $transform['scale'] . '"';
        $parts[] = 'data-ve-x="' . (string) $transform['x'] . '"';
        $parts[] = 'data-ve-y="' . (string) $transform['y'] . '"';
        if (site_magazine_has_custom_asset($kind) || ($kind === 'photo' && site_magazine_asset_path('photo') !== '')) {
            $parts[] = 'data-ve-has-image="1"';
        }
    }
    if ($type === 'mag-layout') {
        $layout = site_magazine_layout();
        $parts[] = 'data-ve-x="' . (string) $layout['x'] . '"';
        $parts[] = 'data-ve-y="' . (string) $layout['y'] . '"';
        $parts[] = 'data-ve-rotate="' . (string) $layout['rotate'] . '"';
        $parts[] = 'data-ve-scale="' . (string) $layout['scale'] . '"';
    }
    if ($type === 'mag-plate') {
        $parts[] = 'data-ve-y="' . (string) site_home_lead_calc_offset() . '"';
        if ($currentValue === '') {
            $parts[] = 'data-ve-value="' . htmlspecialchars(
                site_content_setting('home_lead_calc_label', 'Рассчитать примерный платёж'),
                ENT_QUOTES,
                'UTF-8'
            ) . '"';
        }
    }
    if (
        in_array($type, ['mag-image', 'mag-layout', 'mag-plate'], true)
        || $field === 'home_lead_badge'
    ) {
        $parts[] = 'data-ve-canvas="1"';
    }

    return ' ' . implode(' ', $parts);
}

function site_ve_team_from_crm(): bool
{
    static $fromCrm = null;
    if ($fromCrm !== null) {
        return $fromCrm;
    }

    require_once __DIR__ . '/site-team.php';
    $fromCrm = count(site_team_all_from_crm()) > 0;

    return $fromCrm;
}

function site_visual_editor_boot_json(): string
{
    $icons = [];
    foreach (site_ve_service_icon_options() as $id => $label) {
        $icons[] = ['id' => $id, 'label' => $label];
    }

    $payload = [
        'csrf' => site_admin_csrf_token(),
        'saveUrl' => '/admin/api/visual-save.php',
        'exitUrl' => '/admin/',
        'fields' => array_keys(site_visual_editor_settings_fields()),
        'serviceIcons' => $icons,
        'pages' => [
            ['href' => '/', 'label' => 'Главная'],
            ['href' => '/about/', 'label' => 'О компании'],
            ['href' => '/services/', 'label' => 'Услуги'],
            ['href' => '/mortgage/', 'label' => 'Ипотека'],
            ['href' => '/contacts/', 'label' => 'Контакты'],
        ],
        'teamFromCrm' => site_ve_team_from_crm(),
    ];

    return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Точечное обновление поля элемента в team/services/cases.
 *
 * @return array{value: string, warning: string|null}
 */
function site_ve_save_dataset_field(string $dataset, string $itemId, string $field, string $value): array
{
    require_once __DIR__ . '/site-content.php';

    $map = site_visual_editor_dataset_fields();
    if ($field === 'image' || !isset($map[$dataset][$field])) {
        throw new RuntimeException('Неизвестное поле датасета');
    }
    if ($itemId === '') {
        throw new RuntimeException('Не указан id элемента');
    }

    $rows = site_admin_read_dataset($dataset);
    if (!is_array($rows)) {
        throw new RuntimeException('Датасет не найден');
    }

    $found = false;
    $savedValue = '';
    foreach ($rows as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((string) ($row['id'] ?? '') !== $itemId) {
            continue;
        }

        $row[$field] = $value;
        if ($dataset === 'team') {
            $clean = site_admin_sanitize_team_row($row);
            if ($clean['id'] === '' || $clean['name'] === '') {
                throw new RuntimeException('Имя сотрудника не может быть пустым');
            }
            $rows[$i] = $clean;
            $savedValue = (string) $clean[$field];
        } elseif ($dataset === 'services') {
            if ($field === 'icon') {
                $icon = trim($value);
                if (!isset(site_ve_service_icon_options()[$icon])) {
                    throw new RuntimeException('Неизвестная иконка');
                }
                $row['icon'] = $icon;
            }
            $clean = site_admin_sanitize_service_row($row);
            if ($clean === null) {
                throw new RuntimeException('Название услуги не может быть пустым');
            }
            $rows[$i] = $clean;
            $savedValue = (string) $clean[$field];
        } elseif ($dataset === 'cases') {
            if ($field === 'image') {
                throw new RuntimeException('Изображение загружается файлом');
            }
            $clean = site_admin_sanitize_case_row($row);
            if ($clean === null) {
                throw new RuntimeException('Заголовок кейса не может быть пустым');
            }
            $rows[$i] = $clean;
            $savedValue = (string) $clean[$field];
        } elseif ($dataset === 'deal-cards') {
            if ($field === 'image') {
                throw new RuntimeException('Изображение загружается файлом');
            }
            require_once __DIR__ . '/site-deal-cards.php';
            $clean = site_admin_sanitize_deal_card_row($row);
            if ($clean === null) {
                throw new RuntimeException('Заголовок карточки не может быть пустым');
            }
            $rows[$i] = $clean;
            $savedValue = (string) ($clean[$field] ?? '');
        } else {
            throw new RuntimeException('Неизвестный датасет');
        }
        $found = true;
        break;
    }

    if (!$found) {
        throw new RuntimeException('Элемент не найден');
    }

    if (!site_admin_write_dataset($dataset, $rows)) {
        throw new RuntimeException('Не удалось сохранить файл');
    }

    $warning = null;
    if ($dataset === 'team' && site_ve_team_from_crm()) {
        $warning = 'Команда сейчас из CRM — правка записана в team.json, но на сайте не отобразится.';
    }

    return ['value' => $savedValue, 'warning' => $warning];
}

/**
 * Создать новую услугу в services.json.
 *
 * @return array{id: string, title: string}
 */
function site_ve_create_service(string $title = ''): array
{
    require_once __DIR__ . '/site-content.php';

    $rows = site_admin_read_dataset('services');
    if (!is_array($rows)) {
        $rows = [];
    }

    $title = trim($title);

    $existing = [];
    foreach ($rows as $row) {
        if (is_array($row) && isset($row['id'])) {
            $existing[(string) $row['id']] = true;
        }
    }

    $n = count($rows) + 1;
    $id = 'service-' . $n;
    while (isset($existing[$id])) {
        $n++;
        $id = 'service-' . $n;
        if ($n > 500) {
            $id = 'service-' . bin2hex(random_bytes(3));
            break;
        }
    }

    $label = $title !== '' ? mb_substr($title, 0, 120) : 'Новая услуга';
    $row = site_admin_sanitize_service_row([
        'id' => $id,
        'title' => $label,
        'short' => 'Краткое описание услуги — нажмите, чтобы изменить',
        'text' => 'Полное описание услуги. Отредактируйте текст в визуальном редакторе или в админке.',
        'icon' => 'realtor',
        'href' => '/services/' . $id . '/',
        'bullets' => [],
    ]);
    if ($row === null) {
        throw new RuntimeException('Не удалось создать услугу');
    }

    $rows[] = $row;
    if (!site_admin_write_dataset('services', $rows)) {
        throw new RuntimeException('Не удалось сохранить services.json');
    }

    return ['id' => (string) $row['id'], 'title' => (string) $row['title']];
}

/**
 * Удалить услугу из services.json и её кастомное изображение.
 */
function site_ve_delete_service(string $itemId): void
{
    require_once __DIR__ . '/site-content.php';

    $itemId = trim($itemId);
    if ($itemId === '') {
        throw new RuntimeException('Не указан id услуги');
    }

    $rows = site_admin_read_dataset('services');
    if (!is_array($rows)) {
        throw new RuntimeException('Каталог услуг пуст');
    }

    $out = [];
    $found = false;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((string) ($row['id'] ?? '') === $itemId) {
            $found = true;
            continue;
        }
        $out[] = $row;
    }

    if (!$found) {
        throw new RuntimeException('Услуга не найдена');
    }
    if (count($out) === 0) {
        throw new RuntimeException('Нельзя удалить последнюю услугу');
    }

    if (!site_admin_write_dataset('services', $out)) {
        throw new RuntimeException('Не удалось сохранить services.json');
    }

    site_visual_editor_delete_item_image('services', $itemId);
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
        $raw = trim((string) $value);
        if (str_ends_with($key, '_rotate')) {
            $deg = (int) round((float) $raw);
            $current[$key] = (string) max(-180, min(180, $deg));
            continue;
        }
        if (str_ends_with($key, '_scale')) {
            $scale = (float) str_replace(',', '.', $raw);
            if ($scale <= 0) {
                $scale = 1.0;
            }
            $current[$key] = (string) round(max(0.5, min(2.5, $scale)), 2);
            continue;
        }
        $current[$key] = mb_substr($raw, 0, $allowed[$key]);
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

/**
 * Загрузка фото карточки направления в assets/deal-cards/{id}.{ext}
 *
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 * @return array{ok: bool, path?: string, error?: string}
 */
function site_visual_editor_handle_deal_card_image_upload(string $itemId, array $file): array
{
    $safe = preg_replace('/[^a-z-]/', '', $itemId) ?? '';
    $allowedIds = ['sell', 'buy', 'rent-out', 'rent-in'];
    if ($safe === '' || !in_array($safe, $allowedIds, true)) {
        return ['ok' => false, 'error' => 'Неизвестная карточка'];
    }

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Выберите изображение'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки (код ' . $error . ')'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Файл должен быть не больше 8 МБ'];
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
        default => null,
    };
    if ($ext === null) {
        return ['ok' => false, 'error' => 'Допустимы JPG, PNG или WebP'];
    }
    if (@getimagesize($tmp) === false) {
        return ['ok' => false, 'error' => 'Файл не является изображением'];
    }

    $dir = dirname(__DIR__) . '/assets/deal-cards';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Не удалось создать каталог deal-cards'];
    }

    $staged = $dir . '/.' . $safe . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $staged)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить файл'];
    }

    $destination = $dir . '/' . $safe . '.' . $ext;
    if (!@rename($staged, $destination)) {
        @unlink($staged);

        return ['ok' => false, 'error' => 'Не удалось установить изображение'];
    }

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $other) {
        if ($other === $ext) {
            continue;
        }
        $old = $dir . '/' . $safe . '.' . $other;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    $public = '/assets/deal-cards/' . $safe . '.' . $ext . '?v=' . (string) time();

    return ['ok' => true, 'path' => $public];
}

/**
 * Загрузка фото кейса в assets/cases/{id}.{ext}
 *
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 * @return array{ok: bool, path?: string, error?: string}
 */
function site_visual_editor_handle_case_image_upload(string $itemId, array $file): array
{
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $itemId) ?? '';
    if ($safe === '' || mb_strlen($safe) > 40) {
        return ['ok' => false, 'error' => 'Некорректный id кейса'];
    }

    require_once __DIR__ . '/site-cases.php';
    $exists = false;
    foreach (site_cases_all() as $case) {
        if (($case['id'] ?? '') === $itemId || ($case['id'] ?? '') === $safe) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        return ['ok' => false, 'error' => 'Кейс не найден'];
    }

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Выберите изображение'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки (код ' . $error . ')'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Файл должен быть не больше 8 МБ'];
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
        default => null,
    };
    if ($ext === null) {
        return ['ok' => false, 'error' => 'Допустимы JPG, PNG или WebP'];
    }
    if (@getimagesize($tmp) === false) {
        return ['ok' => false, 'error' => 'Файл не является изображением'];
    }

    $dir = dirname(__DIR__) . '/assets/cases';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Не удалось создать каталог cases'];
    }

    $staged = $dir . '/.' . $safe . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $staged)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить файл'];
    }

    $destination = $dir . '/' . $safe . '.' . $ext;
    if (!@rename($staged, $destination)) {
        @unlink($staged);

        return ['ok' => false, 'error' => 'Не удалось установить изображение'];
    }

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $other) {
        if ($other === $ext) {
            continue;
        }
        $old = $dir . '/' . $safe . '.' . $other;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    $public = '/assets/cases/' . $safe . '.' . $ext . '?v=' . (string) time();

    return ['ok' => true, 'path' => $public];
}

/**
 * Загрузка изображения услуги в assets/services/{id}.{ext}
 *
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 * @return array{ok: bool, path?: string, error?: string}
 */
function site_visual_editor_handle_service_image_upload(string $itemId, array $file): array
{
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $itemId) ?? '';
    if ($safe === '' || mb_strlen($safe) > 40) {
        return ['ok' => false, 'error' => 'Некорректный id услуги'];
    }

    require_once __DIR__ . '/services-catalog.php';
    if (sodeystvie_service_by_id($safe) === null && sodeystvie_service_by_id($itemId) === null) {
        return ['ok' => false, 'error' => 'Услуга не найдена'];
    }

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Выберите изображение'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки (код ' . $error . ')'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 4 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Файл должен быть не больше 4 МБ'];
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
        return ['ok' => false, 'error' => 'Допустимы JPG, PNG, WebP или SVG'];
    }
    if ($ext !== 'svg' && @getimagesize($tmp) === false) {
        return ['ok' => false, 'error' => 'Файл не является изображением'];
    }

    $dir = dirname(__DIR__) . '/assets/services';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Не удалось создать каталог services'];
    }

    $staged = $dir . '/.' . $safe . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $staged)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить файл'];
    }

    $destination = $dir . '/' . $safe . '.' . $ext;
    if (!@rename($staged, $destination)) {
        @unlink($staged);

        return ['ok' => false, 'error' => 'Не удалось установить изображение'];
    }

    foreach (['jpg', 'jpeg', 'png', 'webp', 'svg'] as $other) {
        if ($other === $ext) {
            continue;
        }
        $old = $dir . '/' . $safe . '.' . $other;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    $public = '/assets/services/' . $safe . '.' . $ext . '?v=' . (string) time();

    return ['ok' => true, 'path' => $public];
}

/**
 * Удаление кастомного изображения услуги / кейса.
 *
 * @return array{ok: bool, error?: string}
 */
function site_visual_editor_delete_item_image(string $dataset, string $itemId): array
{
    if ($dataset === 'services') {
        $safe = preg_replace('/[^a-z0-9_-]/i', '', $itemId) ?? '';
        if ($safe === '') {
            return ['ok' => false, 'error' => 'Некорректный id'];
        }
        $dir = dirname(__DIR__) . '/assets/services';
        $removed = false;
        foreach (['jpg', 'jpeg', 'png', 'webp', 'svg'] as $ext) {
            $path = $dir . '/' . $safe . '.' . $ext;
            if (is_file($path) && @unlink($path)) {
                $removed = true;
            }
        }

        return $removed
            ? ['ok' => true]
            : ['ok' => false, 'error' => 'Изображение не найдено'];
    }

    if ($dataset === 'cases') {
        $safe = preg_replace('/[^a-z0-9_-]/i', '', $itemId) ?? '';
        if ($safe === '') {
            return ['ok' => false, 'error' => 'Некорректный id'];
        }
        $dir = dirname(__DIR__) . '/assets/cases';
        $removed = false;
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $path = $dir . '/' . $safe . '.' . $ext;
            if (is_file($path) && @unlink($path)) {
                $removed = true;
            }
        }

        return $removed
            ? ['ok' => true]
            : ['ok' => false, 'error' => 'Изображение не найдено'];
    }

    return ['ok' => false, 'error' => 'Удаление для этого блока не поддерживается'];
}

/**
 * Загрузка ассета журнала: photo | logo → assets/mortgage/magazine-{kind}.*
 *
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 * @return array{ok: bool, path?: string, error?: string}
 */
function site_visual_editor_handle_magazine_asset_upload(string $kind, array $file): array
{
    $kind = site_magazine_kind($kind);
    $base = 'magazine-' . $kind;

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Выберите изображение'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки (код ' . $error . ')'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Файл должен быть не больше 8 МБ'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Некорректная загрузка'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $allowSvg = $kind === 'logo' || $kind === 'pen';
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => $allowSvg ? 'svg' : null,
        default => null,
    };
    if ($ext === null) {
        return ['ok' => false, 'error' => $allowSvg ? 'Допустимы JPG, PNG, WebP или SVG' : 'Допустимы JPG, PNG или WebP'];
    }
    if ($ext !== 'svg' && @getimagesize($tmp) === false) {
        return ['ok' => false, 'error' => 'Файл не является изображением'];
    }

    $dir = dirname(__DIR__) . '/assets/mortgage';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Не удалось создать каталог mortgage'];
    }

    $staged = $dir . '/.' . $base . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $staged)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить файл'];
    }

    $destination = $dir . '/' . $base . '.' . $ext;
    if (!@rename($staged, $destination)) {
        @unlink($staged);

        return ['ok' => false, 'error' => 'Не удалось установить изображение'];
    }

    foreach (['jpg', 'jpeg', 'png', 'webp', 'svg'] as $other) {
        if ($other === $ext) {
            continue;
        }
        $old = $dir . '/' . $base . '.' . $other;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    return ['ok' => true, 'path' => '/assets/mortgage/' . $base . '.' . $ext . '?v=' . (string) time()];
}

/**
 * @return array{ok: bool, error?: string}
 */
function site_visual_editor_delete_magazine_asset(string $kind): array
{
    $kind = site_magazine_kind($kind);
    $base = 'magazine-' . $kind;
    $dir = dirname(__DIR__) . '/assets/mortgage';
    $removed = false;
    foreach (['jpg', 'jpeg', 'png', 'webp', 'svg'] as $ext) {
        $path = $dir . '/' . $base . '.' . $ext;
        if (is_file($path) && @unlink($path)) {
            $removed = true;
        }
    }

    return $removed
        ? ['ok' => true]
        : ['ok' => false, 'error' => 'Своё изображение не найдено'];
}
