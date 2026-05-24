<?php

declare(strict_types=1);

/**
 * Приём заявки с витрины (same-origin) и прокси в CRM POST /api/public/leads.
 */

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once dirname(__DIR__) . '/includes/config.php';

site_send_security_headers();

if (!site_is_same_origin_post()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Запрос отклонён'], JSON_UNESCAPED_UNICODE);
    exit;
}

$limit = site_lead_rate_limit_allow(25);
if (!$limit['ok']) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => $limit['error'] ?? 'Слишком много запросов'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 8192) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Некорректный запрос'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Некорректный JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Некорректные данные'], JSON_UNESCAPED_UNICODE);
    exit;
}

$honeypot = isset($data['company']) ? trim((string) $data['company']) : '';
if ($honeypot !== '') {
    http_response_code(200);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = site_sanitize_lead_name(isset($data['name']) ? (string) $data['name'] : '');
$phone = site_sanitize_lead_phone(isset($data['phone']) ? (string) $data['phone'] : '');
$pageUrl = site_sanitize_lead_page_url(isset($data['pageUrl']) ? (string) $data['pageUrl'] : '');
$objectId = isset($data['objectId']) ? trim((string) $data['objectId']) : '';

if ($name === '' || strlen($phone) < 11) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Укажите имя и корректный телефон'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($objectId !== '' && !site_validate_crm_object_id($objectId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Некорректный объект'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (site_public_site_api_key() === '') {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Заявки временно недоступны: не настроен PUBLIC_SITE_API_KEY на сервере',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = [
    'name' => $name,
    'phone' => $phone,
    'source' => 'Сайт an-sodeystvie.ru',
];
if ($pageUrl !== '') {
    $payload['pageUrl'] = $pageUrl;
}
if ($objectId !== '') {
    $payload['objectId'] = $objectId;
}

$result = site_http_post_json(site_crm_leads_url(), $payload);

if (isset($result['_error'])) {
    $http = isset($result['_http']) && is_int($result['_http']) ? $result['_http'] : 502;
    if ($http < 400 || $http > 599) {
        $http = 502;
    }
    http_response_code($http);
    echo json_encode(['ok' => false, 'error' => (string) $result['_error']], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);
echo json_encode([
    'ok' => true,
    'id' => $result['id'] ?? null,
], JSON_UNESCAPED_UNICODE);
