<?php

declare(strict_types=1);

require_once __DIR__ . '/site-content.php';

const SITE_ADMIN_SESSION_KEY = 'sodeystvie_admin_auth';
const SITE_ADMIN_CSRF_KEY = 'sodeystvie_admin_csrf';

function site_admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_name('sodeystvie_admin');
    session_start();
}

function site_admin_is_configured(): bool
{
    $login = trim(site_env('SITE_ADMIN_LOGIN', ''));
    $hash = trim(site_env('SITE_ADMIN_PASSWORD_HASH', ''));
    $plain = trim(site_env('SITE_ADMIN_PASSWORD', ''));

    return $login !== '' && ($hash !== '' || $plain !== '');
}

function site_admin_login_name(): string
{
    $login = trim(site_env('SITE_ADMIN_LOGIN', ''));

    return $login !== '' ? $login : 'admin';
}

function site_admin_verify_password(string $password): bool
{
    $hash = trim(site_env('SITE_ADMIN_PASSWORD_HASH', ''));
    if ($hash !== '') {
        return password_verify($password, $hash);
    }

    $plain = site_env('SITE_ADMIN_PASSWORD', '');
    if ($plain === '') {
        return false;
    }

    return hash_equals($plain, $password);
}

function site_admin_ip_allowed(): bool
{
    $raw = trim(site_env('SITE_ADMIN_IP_ALLOWLIST', ''));
    if ($raw === '') {
        return true;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!is_string($ip) || $ip === '') {
        return false;
    }

    $parts = preg_split('/\s*,\s*/', $raw) ?: [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '' && $part === $ip) {
            return true;
        }
    }

    return false;
}

function site_admin_is_logged_in(): bool
{
    site_admin_session_start();

    return !empty($_SESSION[SITE_ADMIN_SESSION_KEY])
        && is_string($_SESSION[SITE_ADMIN_SESSION_KEY])
        && hash_equals(site_admin_login_name(), $_SESSION[SITE_ADMIN_SESSION_KEY]);
}

function site_admin_require_login(): void
{
    if (!site_admin_is_configured()) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>Админка не настроена</title></head><body>';
        echo '<h1>Админка не настроена</h1>';
        echo '<p>Задайте <code>SITE_ADMIN_LOGIN</code> и <code>SITE_ADMIN_PASSWORD_HASH</code> (или <code>SITE_ADMIN_PASSWORD</code>) в файле <code>.env</code> на сервере.</p>';
        echo '</body></html>';
        exit;
    }

    if (!site_admin_ip_allowed()) {
        http_response_code(403);
        exit('Доступ запрещён');
    }

    if (!site_admin_is_logged_in()) {
        header('Location: /admin/login.php', true, 302);
        exit;
    }
}

function site_admin_login(string $username, string $password): bool
{
    if (!site_admin_ip_allowed()) {
        return false;
    }

    $limit = site_admin_login_rate_limit_allow();
    if (!$limit['ok']) {
        return false;
    }

    $username = trim($username);
    if ($username === '' || !hash_equals(site_admin_login_name(), $username)) {
        return false;
    }

    if (!site_admin_verify_password($password)) {
        return false;
    }

    site_admin_session_start();
    session_regenerate_id(true);
    $_SESSION[SITE_ADMIN_SESSION_KEY] = site_admin_login_name();

    return true;
}

function site_admin_logout(): void
{
    site_admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

/**
 * @return array{ok: bool, error?: string}
 */
function site_admin_login_rate_limit_allow(int $maxPerHour = 20): array
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0';
    }
    $key = hash('sha256', $ip);
    $dir = rtrim(sys_get_temp_dir(), '/') . '/sodeystvie-admin-login';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return ['ok' => true];
    }
    $file = $dir . '/' . $key . '.json';
    $now = time();
    $data = ['t' => []];
    if (is_readable($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['t']) && is_array($decoded['t'])) {
                $data = $decoded;
            }
        }
    }
    $data['t'] = array_values(array_filter(
        $data['t'],
        static fn ($ts) => is_int($ts) && $ts > $now - 3600,
    ));
    if (count($data['t']) >= $maxPerHour) {
        return ['ok' => false, 'error' => 'Слишком много попыток входа. Попробуйте через час.'];
    }
    $data['t'][] = $now;
    @file_put_contents($file, json_encode($data), LOCK_EX);

    return ['ok' => true];
}

function site_admin_csrf_token(): string
{
    site_admin_session_start();
    if (empty($_SESSION[SITE_ADMIN_CSRF_KEY]) || !is_string($_SESSION[SITE_ADMIN_CSRF_KEY])) {
        $_SESSION[SITE_ADMIN_CSRF_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[SITE_ADMIN_CSRF_KEY];
}

function site_admin_verify_csrf(?string $token): bool
{
    site_admin_session_start();
    $expected = $_SESSION[SITE_ADMIN_CSRF_KEY] ?? '';

    return is_string($token) && is_string($expected) && $expected !== '' && hash_equals($expected, $token);
}

function site_admin_send_noindex(): void
{
    header('X-Robots-Tag: noindex, nofollow');
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function site_admin_sanitize_team_row(array $row): array
{
    $id = preg_replace('/[^a-z0-9_-]/i', '', (string) ($row['id'] ?? '')) ?? '';

    return [
        'id' => mb_substr($id, 0, 40),
        'name' => mb_substr(trim((string) ($row['name'] ?? '')), 0, 120),
        'role' => mb_substr(trim((string) ($row['role'] ?? '')), 0, 120),
        'experience' => mb_substr(trim((string) ($row['experience'] ?? '')), 0, 160),
        'photo' => mb_substr(trim((string) ($row['photo'] ?? '')), 0, 200),
        'telegram' => mb_substr(trim((string) ($row['telegram'] ?? '')), 0, 200),
        'whatsapp' => mb_substr(trim((string) ($row['whatsapp'] ?? '')), 0, 200),
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>|null
 */
function site_admin_sanitize_review_row(array $row): ?array
{
    $id = trim((string) ($row['id'] ?? ''));
    $text = trim((string) ($row['text'] ?? ''));
    if ($id === '' || $text === '') {
        return null;
    }
    $source = trim((string) ($row['source'] ?? 'yandex'));
    if (!in_array($source, ['yandex', '2gis', 'other'], true)) {
        $source = 'yandex';
    }
    $rating = isset($row['rating']) && is_numeric($row['rating']) ? max(1, min(5, (int) $row['rating'])) : 5;

    return [
        'id' => mb_substr($id, 0, 40),
        'author' => mb_substr(trim((string) ($row['author'] ?? '')), 0, 80),
        'date' => mb_substr(trim((string) ($row['date'] ?? '')), 0, 10),
        'rating' => $rating,
        'text' => mb_substr($text, 0, 2000),
        'source' => $source,
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>|null
 */
function site_admin_sanitize_case_row(array $row): ?array
{
    $id = trim((string) ($row['id'] ?? ''));
    $title = trim((string) ($row['title'] ?? ''));
    if ($id === '' || $title === '') {
        return null;
    }

    return [
        'id' => mb_substr($id, 0, 40),
        'tag' => mb_substr(trim((string) ($row['tag'] ?? '')), 0, 40),
        'title' => mb_substr($title, 0, 160),
        'result' => mb_substr(trim((string) ($row['result'] ?? '')), 0, 120),
        'text' => mb_substr(trim((string) ($row['text'] ?? '')), 0, 1200),
    ];
}

/**
 * @param list<mixed> $lines
 * @return list<string>
 */
function site_admin_sanitize_string_list(array|string $lines): array
{
    if (is_string($lines)) {
        $lines = preg_split('/\r\n|\r|\n/', $lines) ?: [];
    }
    $out = [];
    foreach ($lines as $line) {
        $s = mb_substr(trim((string) $line), 0, 400);
        if ($s !== '') {
            $out[] = $s;
        }
    }

    return $out;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>|null
 */
function site_admin_sanitize_vacancy_row(array $row): ?array
{
    $id = trim((string) ($row['id'] ?? ''));
    $title = trim((string) ($row['title'] ?? ''));
    if ($id === '' || $title === '') {
        return null;
    }

    return [
        'id' => mb_substr($id, 0, 40),
        'title' => mb_substr($title, 0, 160),
        'schedule' => mb_substr(trim((string) ($row['schedule'] ?? '')), 0, 80),
        'salary' => mb_substr(trim((string) ($row['salary'] ?? '')), 0, 80),
        'location' => mb_substr(trim((string) ($row['location'] ?? '')), 0, 160),
        'lead' => mb_substr(trim((string) ($row['lead'] ?? '')), 0, 400),
        'duties' => site_admin_sanitize_string_list($row['duties'] ?? []),
        'requirements' => site_admin_sanitize_string_list($row['requirements'] ?? []),
        'conditions' => site_admin_sanitize_string_list($row['conditions'] ?? []),
        'active' => !empty($row['active']),
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>|null
 */
function site_admin_sanitize_service_row(array $row): ?array
{
    $id = preg_replace('/[^a-z0-9_-]/i', '', (string) ($row['id'] ?? '')) ?? '';
    $title = trim((string) ($row['title'] ?? ''));
    if ($id === '' || $title === '') {
        return null;
    }

    $bulletsRaw = $row['bullets'] ?? [];
    if (is_string($bulletsRaw)) {
        $bulletsRaw = preg_split('/\r\n|\r|\n/', $bulletsRaw) ?: [];
    }
    $bullets = site_admin_sanitize_string_list(is_array($bulletsRaw) ? $bulletsRaw : []);
    $out = [
        'id' => mb_substr($id, 0, 40),
        'title' => mb_substr($title, 0, 120),
        'short' => mb_substr(trim((string) ($row['short'] ?? '')), 0, 240),
        'text' => mb_substr(trim((string) ($row['text'] ?? '')), 0, 2000),
        'icon' => mb_substr(trim((string) ($row['icon'] ?? 'realtor')), 0, 40),
        'bullets' => $bullets,
    ];
    $href = trim((string) ($row['href'] ?? ''));
    if ($href !== '' && (str_starts_with($href, '/') || preg_match('#^https?://#i', $href))) {
        $out['href'] = mb_substr($href, 0, 200);
        $label = trim((string) ($row['hrefLabel'] ?? ''));
        if ($label !== '') {
            $out['hrefLabel'] = mb_substr($label, 0, 80);
        }
    }

    return $out;
}

/**
 * @param array<string, mixed> $input
 * @return array<string, string>
 */
function site_admin_sanitize_settings(array $input): array
{
    $fields = [
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
        'reviews_rating' => 400,
        'reviews_count' => 400,
        'telegram_url' => 400,
        'vk_url' => 400,
        'max_url' => 400,
        'whatsapp_url' => 400,
        'messenger_show_telegram' => 1,
        'messenger_show_vk' => 1,
        'messenger_show_max' => 1,
        'messenger_show_whatsapp' => 1,
        'footer_reprint_notice' => 500,
        'footer_info_disclaimer' => 1000,
        'privacy_policy' => 30000,
        'deal_cards_kicker' => 80,
        'cases_section_title' => 120,
        'cases_section_lead' => 400,
        'nav_label_home' => 80,
        'nav_label_catalog' => 80,
        'nav_label_services' => 80,
        'nav_label_mortgage' => 80,
        'nav_label_about' => 80,
        'nav_label_contacts' => 80,
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
    $out = [];
    foreach ($fields as $key => $limit) {
        $out[$key] = mb_substr(trim((string) ($input[$key] ?? '')), 0, $limit);
    }

    return $out;
}

/**
 * @param mixed $payload
 * @return mixed
 */
function site_admin_sanitize_dataset(string $id, $payload)
{
    if ($id === 'settings') {
        return site_admin_sanitize_settings(is_array($payload) ? $payload : []);
    }

    if (!is_array($payload)) {
        return [];
    }

    $out = [];
    foreach ($payload as $row) {
        if (!is_array($row)) {
            continue;
        }
        $sanitized = match ($id) {
            'team' => site_admin_sanitize_team_row($row),
            'reviews' => site_admin_sanitize_review_row($row),
            'cases' => site_admin_sanitize_case_row($row),
            'vacancies' => site_admin_sanitize_vacancy_row($row),
            'services' => site_admin_sanitize_service_row($row),
            default => null,
        };
        if ($sanitized === null) {
            continue;
        }
        if ($id === 'team' && ($sanitized['id'] === '' || $sanitized['name'] === '')) {
            continue;
        }
        $out[] = $sanitized;
    }

    return $out;
}

/**
 * @return array{ok: bool, error?: string}
 */
function site_admin_handle_team_photo_upload(string $memberId): array
{
    $safeId = preg_replace('/[^a-z0-9_-]/i', '', $memberId) ?? '';
    if ($safeId === '') {
        return ['ok' => false, 'error' => 'Некорректный id сотрудника'];
    }

    if (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) {
        return ['ok' => false, 'error' => 'Файл не получен'];
    }

    $file = $_FILES['photo'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки файла'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Файл должен быть не больше 5 МБ'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Некорректная загрузка'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => null,
    };
    if ($ext === null) {
        return ['ok' => false, 'error' => 'Допустимы JPG, PNG или WebP'];
    }

    $dir = dirname(__DIR__) . '/assets/team';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Не удалось создать каталог для фото'];
    }

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $oldExt) {
        $old = $dir . '/' . $safeId . '.' . $oldExt;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    $dest = $dir . '/' . $safeId . '.' . $ext;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить файл'];
    }

    return ['ok' => true, 'path' => '/assets/team/' . $safeId . '.' . $ext];
}

/**
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 * @return array{ok: bool, path?: string, error?: string}
 */
function site_admin_handle_home_lead_image_upload(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Выберите изображение для загрузки'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Не удалось загрузить изображение (код ' . $error . ')'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Изображение должно быть не больше 8 МБ'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Некорректная загрузка изображения'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => null,
    };
    if ($ext === null || @getimagesize($tmp) === false) {
        return ['ok' => false, 'error' => 'Допустимы только корректные JPG, PNG или WebP'];
    }

    $dir = dirname(__DIR__) . '/assets/admin';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Не удалось создать каталог для изображения'];
    }

    $staged = $dir . '/.home-lead-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $staged)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить изображение'];
    }

    $destination = $dir . '/home-lead.' . $ext;
    if (!@rename($staged, $destination)) {
        @unlink($staged);

        return ['ok' => false, 'error' => 'Не удалось установить загруженное изображение'];
    }

    foreach (['jpg', 'png', 'webp'] as $oldExt) {
        $old = $dir . '/home-lead.' . $oldExt;
        if ($old !== $destination && is_file($old)) {
            @unlink($old);
        }
    }

    return ['ok' => true, 'path' => '/assets/admin/home-lead.' . $ext];
}

function site_admin_delete_home_lead_image(): bool
{
    $dir = dirname(__DIR__) . '/assets/admin';
    $ok = true;
    foreach (['jpg', 'png', 'webp'] as $ext) {
        $path = $dir . '/home-lead.' . $ext;
        if (is_file($path) && !@unlink($path)) {
            $ok = false;
        }
    }

    return $ok;
}

/**
 * @param array<string, mixed> $files поле $_FILES['messenger_icon']
 */
function site_admin_handle_messenger_icons_upload(array $files): void
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return;
    }

    require_once __DIR__ . '/site-messengers.php';

    foreach ($files['name'] as $type => $name) {
        if (!is_string($type) || !in_array($type, site_messenger_types(), true)) {
            continue;
        }
        if (!is_string($name) || $name === '') {
            continue;
        }

        $single = [
            'name' => $files['name'][$type] ?? '',
            'type' => $files['type'][$type] ?? '',
            'tmp_name' => $files['tmp_name'][$type] ?? '',
            'error' => $files['error'][$type] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$type] ?? 0,
        ];

        if (($single['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        site_admin_handle_messenger_icon_upload($type, $single);
    }
}

/**
 * @param 'telegram'|'vk'|'max'|'whatsapp' $type
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 * @return array{ok: bool, error?: string}
 */
function site_admin_handle_messenger_icon_upload(string $type, array $file): array
{
    if (!in_array($type, site_messenger_types(), true)) {
        return ['ok' => false, 'error' => 'Некорректный тип мессенджера'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Ошибка загрузки файла'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 512 * 1024) {
        return ['ok' => false, 'error' => 'Иконка должна быть не больше 512 КБ'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Некорректная загрузка'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $ext = match ($mime) {
        'image/svg+xml' => 'svg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => null,
    };
    if ($ext === null) {
        return ['ok' => false, 'error' => 'Допустимы SVG, PNG или WebP'];
    }

    $dir = site_messenger_icons_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Не удалось создать каталог для иконок'];
    }

    foreach (['svg', 'png', 'webp'] as $oldExt) {
        $old = $dir . '/' . $type . '.' . $oldExt;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    $dest = $dir . '/' . $type . '.' . $ext;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Не удалось сохранить иконку'];
    }

    return ['ok' => true];
}
