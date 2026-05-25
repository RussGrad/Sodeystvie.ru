<?php

declare(strict_types=1);

/**
 * Конвертация изображений в WebP с дисковым кэшем (CRM, Яндекс.Диск, /assets/).
 */
require_once __DIR__ . '/../includes/config.php';

if (!site_image_webp_enabled() || !site_image_can_convert()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'WebP conversion is not available on this server (enable GD with imagewebp).';
    exit;
}

$maxWidth = isset($_GET['w']) && is_numeric($_GET['w']) ? (int) $_GET['w'] : 0;
$maxWidth = $maxWidth > 0 ? min($maxWidth, site_image_webp_max_width()) : site_image_webp_max_width();
$quality = site_image_webp_quality();

$cacheDir = site_image_cache_dir();
if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
    http_response_code(500);
    exit;
}

$binary = null;
$cacheKey = '';

$path = isset($_GET['path']) && is_string($_GET['path']) ? trim($_GET['path']) : '';
$remote = isset($_GET['u']) && is_string($_GET['u']) ? trim($_GET['u']) : '';

if ($path !== '' && site_image_public_path_is_allowed($path)) {
    $abs = dirname(__DIR__) . $path;
    if (!is_file($abs)) {
        http_response_code(404);
        exit;
    }
    $binary = file_get_contents($abs);
    if ($binary === false) {
        http_response_code(404);
        exit;
    }
    $cacheKey = 'path:' . $path . ':w' . $maxWidth . ':q' . $quality;
} elseif ($remote !== '' && preg_match('#^https?://#i', $remote) && site_image_url_is_allowed_remote($remote)) {
    $cacheKey = 'url:' . $remote . ':w' . $maxWidth . ':q' . $quality;
    $binary = site_image_fetch_remote($remote);
    if ($binary === null) {
        http_response_code(502);
        exit;
    }
} else {
    http_response_code(400);
    exit;
}

$cacheFile = $cacheDir . '/' . hash('sha256', $cacheKey) . '.webp';

if (is_file($cacheFile) && filemtime($cacheFile) > time() - 86400 * 30) {
    site_image_send_webp_file($cacheFile);
    exit;
}

$webp = site_image_convert_to_webp($binary, $maxWidth, $quality);
if ($webp === null) {
    http_response_code(415);
    exit;
}

file_put_contents($cacheFile, $webp);
site_image_send_webp_bytes($webp);

/**
 * @return string|null
 */
function site_image_fetch_remote(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'SodeystvieImageProxy/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 400) {
            return null;
        }

        return is_string($body) ? $body : null;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header' => "User-Agent: SodeystvieImageProxy/1.0\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);

    return is_string($body) && $body !== '' ? $body : null;
}

/**
 * @return string|null
 */
function site_image_convert_to_webp(string $binary, int $maxWidth, int $quality): ?string
{
    $img = @imagecreatefromstring($binary);
    if ($img === false) {
        return null;
    }

    $w = imagesx($img);
    $h = imagesy($img);
    if ($w > 0 && $h > 0 && $w > $maxWidth && function_exists('imagescale')) {
        $nh = (int) round($h * ($maxWidth / $w));
        $scaled = imagescale($img, $maxWidth, max(1, $nh));
        if ($scaled !== false) {
            imagedestroy($img);
            $img = $scaled;
        }
    }

    imagealphablending($img, true);
    imagesavealpha($img, true);

    ob_start();
    $ok = imagewebp($img, null, $quality);
    $webp = ob_get_clean();
    imagedestroy($img);

    if (!$ok || !is_string($webp) || $webp === '') {
        return null;
    }

    return $webp;
}

function site_image_send_webp_file(string $path): void
{
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=2592000, immutable');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
}

function site_image_send_webp_bytes(string $bytes): void
{
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=604800');
    header('Content-Length: ' . (string) strlen($bytes));
    echo $bytes;
}
