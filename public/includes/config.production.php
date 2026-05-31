<?php

declare(strict_types=1);

/**
 * Боевые URL CRM для an-sodeystvie.ru.
 * Подключается после public/.env — перезаписывает localhost, если .env скопировали с Mac.
 */
$httpHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
$serverName = isset($_SERVER['SERVER_NAME']) ? strtolower((string) $_SERVER['SERVER_NAME']) : '';
$isVitrina = str_contains($httpHost, 'an-sodeystvie.ru')
    || str_contains($serverName, 'an-sodeystvie.ru');

if (!$isVitrina) {
    return;
}

/** @param non-empty-string $key @param non-empty-string $value */
$force = static function (string $key, string $value): void {
    $cur = site_read_env_var($key);
    if ($cur === '' || str_contains($cur, 'localhost') || str_contains($cur, '127.0.0.1')) {
        site_set_env_var($key, $value);
    }
};

$force('CRM_API_BASE', 'https://an-realty-crm.ru');
$force('CRM_PUBLIC_BASE', 'https://an-realty-crm.ru');
$force('CRM_LISTINGS_PATH', '/api/public/listings');
$force('CRM_LEADS_PATH', '/api/public/leads');
$force('CRM_SITE_CHAT_PATH', '/api/public/site-chat');

/**
 * Ключ заявок с витрины (тот же — PUBLIC_SITE_API_KEY в apps/api/.env на CRM).
 * Подставляется на an-sodeystvie.ru, если .env на хостинге не прочитался (часто отключён putenv).
 * Для смены ключа: public/.env, crm-config.env или includes/crm-config.local.php.
 * Карта: YANDEX_MAPS_API_KEY — лучше в crm-config.env (без точки в имени, удобно на REG.RU).
 */
$siteApiKey = site_read_env_var('PUBLIC_SITE_API_KEY');
if ($siteApiKey === '') {
    site_set_env_var('PUBLIC_SITE_API_KEY', 'f950274c-273d-466f-9161-8b404571380e');
}
