<?php

declare(strict_types=1);

/**
 * Безопасность витрины: заголовки, валидация, лимиты.
 */

if (!function_exists('site_allow_debug_endpoints')) {
    /** Отладка (?phpinfo, ?dbtest) только при SITE_ALLOW_DEBUG=true в .env */
    function site_allow_debug_endpoints(): bool
    {
        return strtolower(trim(site_env('SITE_ALLOW_DEBUG', 'false'))) === 'true';
    }
}

if (!function_exists('site_send_security_headers')) {
    function site_send_security_headers(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}

if (!function_exists('site_validate_crm_object_id')) {
    /** ID объекта CRM (cuid/uuid/ascii), защита от path traversal в URL API */
    function site_validate_crm_object_id(string $id): bool
    {
        $id = trim($id);
        if ($id === '' || strlen($id) > 64) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $id);
    }
}

if (!function_exists('site_sanitize_lead_name')) {
    function site_sanitize_lead_name(string $name): string
    {
        $name = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim($name);

        return mb_substr($name, 0, 120);
    }
}

if (!function_exists('site_sanitize_lead_phone')) {
    function site_sanitize_lead_phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if ($digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }
        if ($digits[0] !== '7') {
            $digits = '7' . $digits;
        }

        return substr($digits, 0, 11);
    }
}

if (!function_exists('site_sanitize_lead_page_url')) {
    function site_sanitize_lead_page_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (strlen($url) > 500) {
            $url = substr($url, 0, 500);
        }
        if (!preg_match('#^https?://#i', $url)) {
            if (str_starts_with($url, '/')) {
                return $url;
            }

            return '';
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return '';
        }

        return $url;
    }
}

if (!function_exists('site_is_same_origin_post')) {
    /** POST /api/lead-submit.php — только с того же сайта */
    function site_is_same_origin_post(): bool
    {
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
        if ($host === '') {
            return false;
        }
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string) $_SERVER['HTTP_ORIGIN']) : '';
        if ($origin !== '') {
            $oh = parse_url($origin, PHP_URL_HOST);

            return is_string($oh) && strtolower($oh) === preg_replace('/:\d+$/', '', $host);
        }
        $referer = isset($_SERVER['HTTP_REFERER']) ? trim((string) $_SERVER['HTTP_REFERER']) : '';
        if ($referer === '') {
            return false;
        }
        $rh = parse_url($referer, PHP_URL_HOST);

        return is_string($rh) && strtolower($rh) === preg_replace('/:\d+$/', '', $host);
    }
}

if (!function_exists('site_lead_rate_limit_allow')) {
    /**
     * Простой лимит заявок с IP (файлы в sys temp).
     *
     * @return array{ok: bool, error?: string}
     */
    function site_lead_rate_limit_allow(int $maxPerHour = 25): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
        if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0';
        }
        $key = hash('sha256', $ip);
        $dir = rtrim(sys_get_temp_dir(), '/') . '/sodeystvie-lead-limit';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return ['ok' => true];
        }
        $file = $dir . '/' . $key . '.json';
        $now = time();
        $window = 3600;
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
            static fn ($ts) => is_int($ts) && $ts > $now - $window,
        ));
        if (count($data['t']) >= $maxPerHour) {
            return ['ok' => false, 'error' => 'Слишком много заявок. Попробуйте через час или позвоните нам.'];
        }
        $data['t'][] = $now;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        return ['ok' => true];
    }
}
