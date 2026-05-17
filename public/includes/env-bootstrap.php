<?php

declare(strict_types=1);

/**
 * Загрузка переменных из public/.env (без Composer).
 * На shared-хостинге часто нет глобальных env — достаточно положить .env рядом с index.php.
 */
if (!function_exists('site_load_dotenv_file')) {
    /**
     * @param non-empty-string $path
     */
    function site_load_dotenv_file(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ($k === '') {
                continue;
            }
            if (preg_match('/^"(.*)"$/s', $v, $m) || preg_match("/^'(.*)'$/s", $v, $m)) {
                $v = $m[1];
            }
            putenv($k . '=' . $v);
            $_ENV[$k] = $v;
        }
    }
}

$envPath = dirname(__DIR__) . '/.env';
site_load_dotenv_file($envPath);

$productionConfig = __DIR__ . '/config.production.php';
if (is_readable($productionConfig)) {
    require $productionConfig;
}
