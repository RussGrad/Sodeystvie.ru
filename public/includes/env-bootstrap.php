<?php

declare(strict_types=1);

/**
 * Загрузка переменных из public/.env (без Composer).
 * На shared-хостинге часто нет глобальных env — достаточно положить .env рядом с index.php.
 */
if (!function_exists('site_can_putenv')) {
    function site_can_putenv(): bool
    {
        if (!function_exists('putenv')) {
            return false;
        }
        $disabled = ini_get('disable_functions');
        if (!is_string($disabled) || $disabled === '') {
            return true;
        }

        $list = array_map('trim', explode(',', strtolower($disabled)));

        return !in_array('putenv', $list, true);
    }
}

if (!function_exists('site_set_env_var')) {
    /** @param non-empty-string $key */
    function site_set_env_var(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        if (site_can_putenv()) {
            putenv($key . '=' . $value);
        }
    }
}

if (!function_exists('site_read_env_var')) {
    function site_read_env_var(string $key): string
    {
        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        if (function_exists('getenv')) {
            $v = getenv($key);
            if ($v !== false && $v !== '') {
                return (string) $v;
            }
        }

        return '';
    }
}

if (!function_exists('site_load_dotenv_file')) {
    /**
     * @param non-empty-string $path
     */
    function site_load_dotenv_file(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return;
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k, " \t\r\n\0\x0B");
            $v = trim($v, " \t\r\n\0\x0B");
            if ($k === '') {
                continue;
            }
            if (preg_match('/^"(.*)"$/s', $v, $m) || preg_match('/^\'(.*)\'$/s', $v, $m)) {
                $v = $m[1];
            }
            site_set_env_var($k, $v);
        }
    }
}

if (!function_exists('site_load_php_config_file')) {
    /**
     * @param non-empty-string $path файл, возвращающий array<string, string>
     */
    function site_load_php_config_file(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }
        $cfg = require $path;
        if (!is_array($cfg)) {
            return;
        }
        foreach ($cfg as $key => $value) {
            if (!is_string($key) || !is_string($value) || $value === '') {
                continue;
            }
            site_set_env_var($key, $value);
        }
    }
}

$siteRoot = dirname(__DIR__);
site_load_dotenv_file($siteRoot . '/.env');
site_load_dotenv_file($siteRoot . '/crm-config.env');
site_load_php_config_file(__DIR__ . '/crm-config.local.php');

$productionConfig = __DIR__ . '/config.production.php';
if (is_readable($productionConfig)) {
    require $productionConfig;
}
