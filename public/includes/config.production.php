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
    $cur = getenv($key);
    if ($cur === false || $cur === '' || str_contains((string) $cur, 'localhost') || str_contains((string) $cur, '127.0.0.1')) {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
};

$force('CRM_API_BASE', 'https://an-realty-crm.ru');
$force('CRM_PUBLIC_BASE', 'https://an-realty-crm.ru');
$force('CRM_LISTINGS_PATH', '/api/public/listings');
