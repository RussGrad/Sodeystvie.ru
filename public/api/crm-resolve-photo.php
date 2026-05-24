<?php

declare(strict_types=1);

/**
 * Прокси резолва фото (yadi.sk → прямой URL). Кэш на стороне витрины, один запрос на фото.
 */
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$url = isset($_GET['url']) && is_string($_GET['url']) ? trim($_GET['url']) : '';
if ($url === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Укажите url'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    !preg_match('#^https?://#i', $url)
    && !str_starts_with($url, '/uploads/')
    && !str_starts_with($url, 'yadisk:')
) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный url'], JSON_UNESCAPED_UNICODE);
    exit;
}

$resolved = site_crm_photo_src($url);
if ($resolved === '') {
    http_response_code(404);
    echo json_encode(['error' => 'Не удалось получить ссылку на фото'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['url' => $resolved], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
