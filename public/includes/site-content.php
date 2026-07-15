<?php

declare(strict_types=1);

/**
 * Настройки и JSON-контент витрины (редактируются через /admin/).
 */

function site_content_data_dir(): string
{
    return dirname(__DIR__) . '/data';
}

function site_content_settings_path(): string
{
    return site_content_data_dir() . '/site-settings.json';
}

/**
 * @return array<string, string>
 */
function site_content_settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $path = site_content_settings_path();
    if (!is_readable($path)) {
        $cache = [];

        return $cache;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        $cache = [];

        return $cache;
    }

    $out = [];
    foreach ($decoded as $key => $value) {
        if (!is_string($key)) {
            continue;
        }
        $out[$key] = is_string($value) ? $value : (is_numeric($value) ? (string) $value : '');
    }

    $cache = $out;

    return $cache;
}

function site_content_setting(string $key, string $default = ''): string
{
    $settings = site_content_settings();
    if (array_key_exists($key, $settings) && trim($settings[$key]) !== '') {
        return trim($settings[$key]);
    }

    return $default;
}

function site_phone_tel(): string
{
    return site_content_setting('phone_tel', SITE_PHONE_TEL);
}

function site_phone_display(): string
{
    return site_content_setting('phone_display', SITE_PHONE_DISPLAY);
}

function site_email_address(): string
{
    return site_content_setting('email', SITE_EMAIL);
}

function site_postal_address(): string
{
    return site_content_setting('address', SITE_ADDRESS);
}

function site_office_hours(): string
{
    return site_content_setting('work_hours', SITE_WORK_HOURS);
}

/**
 * Промоматериал блока заявки на главной (каталог / журнал).
 * Источник истины — файл в /assets/admin/; ключ настроек синхронизируется при сохранении.
 */
function site_home_lead_image_path(): string
{
    $dir = dirname(__DIR__) . '/assets/admin';
    foreach (['webp', 'jpg', 'png'] as $ext) {
        $absolutePath = $dir . '/home-lead.' . $ext;
        if (is_readable($absolutePath)) {
            return '/assets/admin/home-lead.' . $ext;
        }
    }

    $path = site_content_setting('home_lead_image');
    if (!preg_match('#^/assets/admin/home-lead\.(?:jpg|png|webp)$#', $path)) {
        return '';
    }

    $absolutePath = dirname(__DIR__) . $path;

    return is_readable($absolutePath) ? $path : '';
}

function site_home_lead_image_src(): string
{
    $path = site_home_lead_image_path();
    if ($path === '') {
        return '';
    }

    $absolutePath = dirname(__DIR__) . $path;
    $version = (string) (@filemtime($absolutePath) ?: time());

    return $path . '?v=' . rawurlencode($version);
}

function site_home_lead_title(): string
{
    return site_content_setting(
        'home_lead_title',
        'Подберём ипотечную программу под вашу ситуацию'
    );
}

function site_home_lead_description(): string
{
    return site_content_setting(
        'home_lead_description',
        'Ответьте на несколько вопросов — специалист подготовит персональный расчёт и свяжется с вами.'
    );
}

function site_home_lead_cta(): string
{
    return site_content_setting('home_lead_cta', 'Получить расчёт');
}

/**
 * @return list<string>
 */
function site_admin_editable_datasets(): array
{
    return ['team', 'reviews', 'cases', 'vacancies', 'services', 'settings'];
}

function site_admin_dataset_path(string $id): ?string
{
    $map = [
        'team' => 'team.json',
        'reviews' => 'reviews.json',
        'cases' => 'cases.json',
        'vacancies' => 'vacancies.json',
        'services' => 'services.json',
        'settings' => 'site-settings.json',
    ];

    if (!isset($map[$id])) {
        return null;
    }

    return site_content_data_dir() . '/' . $map[$id];
}

function site_admin_dataset_label(string $id): string
{
    $labels = [
        'team' => 'Команда',
        'reviews' => 'Отзывы',
        'cases' => 'Кейсы',
        'vacancies' => 'Вакансии',
        'services' => 'Услуги',
        'settings' => 'Контакты, тексты и политика',
    ];

    return $labels[$id] ?? $id;
}

/**
 * @return mixed
 */
function site_admin_read_dataset(string $id)
{
    $path = site_admin_dataset_path($id);
    if ($path === null || !is_readable($path)) {
        return $id === 'settings' ? [] : [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    return is_array($decoded) ? $decoded : ($id === 'settings' ? [] : []);
}

/**
 * @param mixed $data
 */
function site_admin_write_dataset(string $id, $data): bool
{
    $path = site_admin_dataset_path($id);
    if ($path === null) {
        return false;
    }

    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );
    if ($json === false) {
        return false;
    }

    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        return false;
    }

    if (!@rename($tmp, $path)) {
        @unlink($tmp);

        return false;
    }

    if ($id === 'settings') {
        site_content_settings_reset_cache();
    }

    return true;
}

function site_content_settings_reset_cache(): void
{
    // Сброс static-кэша site_content_settings через повторный вызов с рефлексией невозможен —
    // проще перезагрузить страницу после сохранения.
}
