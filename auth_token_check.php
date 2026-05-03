<?php
// Auth check for API requests
if (!isset($API_AUTH_SECRET)) {
    include "config.php";
}

// Try to get the headers
if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
} else {
    $headers = getallheaders();
}

$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
if (!$authHeader) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

if (!preg_match('/^Bearer\s+(.*)$/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

$token = trim($matches[1]);
if ($token === '') {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

// Allow legacy static token for fallback (optional)
if ($token === $API_AUTH_SECRET) {
    return;
}

$decoded = base64_decode($token, true);
if ($decoded === false) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

$parts = explode('|', $decoded);
if (count($parts) !== 3) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

list($userId, $exp, $sig) = $parts;
$userId = intval($userId);
$exp = intval($exp);
if ($userId <= 0 || $exp <= 0) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

if (time() > $exp) {
    http_response_code(401);
    echo json_encode(["error" => "Token expired."]);
    exit;
}

$expected = hash_hmac('sha256', $userId . '|' . $exp, $API_AUTH_SECRET);
if (!hash_equals($expected, $sig)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}
?>
