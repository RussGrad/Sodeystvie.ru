<?php

declare(strict_types=1);

/**
 * Прокси онлайн-чата витрины → CRM /api/public/site-chat.
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/config.php';

site_send_security_headers();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!site_is_same_origin_post() && $method === 'POST') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Запрос отклонён'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $limit = site_lead_rate_limit_allow(120);
    if (!$limit['ok']) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'error' => $limit['error'] ?? 'Слишком много запросов'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (site_public_site_api_key() === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Чат временно недоступен'], JSON_UNESCAPED_UNICODE);
    exit;
}

$crmBase = site_crm_site_chat_url() . '/messages';

if ($method === 'GET') {
    $token = isset($_GET['visitorToken']) ? trim((string) $_GET['visitorToken']) : '';
    if ($token === '' || !preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $token)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Некорректный идентификатор чата'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $since = isset($_GET['since']) ? trim((string) $_GET['since']) : '';
    $url = $crmBase . '?visitorToken=' . rawurlencode($token);
    if ($since !== '') {
        $url .= '&since=' . rawurlencode($since);
    }
    $result = site_http_public_api_get($url, 12);
    if (isset($result['_error'])) {
        http_response_code(isset($result['_http']) && is_int($result['_http']) ? $result['_http'] : 502);
        echo json_encode(['ok' => false, 'error' => (string) $result['_error']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
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

$token = isset($data['visitorToken']) ? trim((string) $data['visitorToken']) : '';
$body = isset($data['body']) ? trim((string) $data['body']) : '';
$name = site_sanitize_lead_name(isset($data['name']) ? (string) $data['name'] : '');
$phone = site_sanitize_lead_phone(isset($data['phone']) ? (string) $data['phone'] : '');
$pageUrl = site_sanitize_lead_page_url(isset($data['pageUrl']) ? (string) $data['pageUrl'] : '');

if ($token === '' || !preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $token)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Некорректный идентификатор чата'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($body === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Введите сообщение'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($body) > 2000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Сообщение слишком длинное'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = [
    'visitorToken' => $token,
    'body' => $body,
    'sourceHost' => site_canonical_host(),
    'pageUrl' => $pageUrl !== '' ? $pageUrl : (isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : ''),
];
if ($name !== '') {
    $payload['name'] = $name;
}
if ($phone !== '') {
    $payload['phone'] = $phone;
}

$result = site_http_post_json($crmBase, $payload, 12);
if (isset($result['_error'])) {
    $http = isset($result['_http']) && is_int($result['_http']) ? $result['_http'] : 502;
    if ($http < 400 || $http > 599) {
        $http = 502;
    }
    $error = (string) $result['_error'];
    if ($http === 404) {
        $error = 'На CRM не развёрнут API онлайн-чата. Обновите an-realty-api на сервере (git pull, prisma migrate deploy, build, restart).';
    }
    http_response_code($http);
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
