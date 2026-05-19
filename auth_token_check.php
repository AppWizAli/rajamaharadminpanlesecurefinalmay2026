<?php
// Auth check for API requests
if (!isset($API_AUTH_SECRET)) {
    include "config.php";
}

if (!function_exists('auth_json_deny')) {
    function auth_json_deny($message, $code = 401) {
        http_response_code($code);
        header("Content-Type: application/json");
        echo json_encode(["error" => $message]);
        exit;
    }
}

if (!function_exists('auth_request_headers_normalized')) {
    function auth_request_headers_normalized() {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
        }

        if (!isset($headers['Authorization']) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }

        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower((string) $name)] = $value;
        }
        return $normalized;
    }
}

if (!function_exists('auth_current_script_name')) {
    function auth_current_script_name() {
        return basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    }
}

if (!function_exists('auth_public_token_allowed_scripts')) {
    function auth_public_token_allowed_scripts() {
        return [
            'get_dramas.php',
            'get_tdramas.php',
            'get_seasons.php',
            'get_banner_video.php',
            'get_banner_image.php',
            'get_notifications.php',
            'fetch_episodes.php',
            'fetch_seasons.php',
            'fetch_videos.php'
        ];
    }
}

if (!function_exists('authenticated_user_id')) {
    function authenticated_user_id() {
        $value = $GLOBALS['AUTH_USER_ID'] ?? null;
        return is_int($value) && $value > 0 ? $value : null;
    }
}

if (!function_exists('require_authenticated_user_id')) {
    function require_authenticated_user_id() {
        $userId = authenticated_user_id();
        if ($userId === null) {
            auth_json_deny("Authenticated user required.");
        }
        return $userId;
    }
}

if (!function_exists('enforce_authenticated_user_match')) {
    function enforce_authenticated_user_match($candidateUserId) {
        $authUserId = require_authenticated_user_id();
        if ($candidateUserId > 0 && intval($candidateUserId) !== $authUserId) {
            auth_json_deny("User mismatch.", 403);
        }
        return $authUserId;
    }
}

$headers = auth_request_headers_normalized();
$authHeader = trim((string) ($headers['authorization'] ?? ''));
if ($authHeader === '') {
    auth_json_deny("Unauthorized access.");
}

if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
    auth_json_deny("Unauthorized access.");
}

$token = trim((string) ($matches[1] ?? ''));
if ($token === '') {
    auth_json_deny("Unauthorized access.");
}

$currentScript = auth_current_script_name();
$publicScripts = auth_public_token_allowed_scripts();
if ($token === ($PUBLIC_READ_TOKEN ?? '')) {
    if (in_array($currentScript, $publicScripts, true)) {
        $GLOBALS['AUTH_USER_ID'] = null;
        $GLOBALS['AUTH_IS_PUBLIC_TOKEN'] = true;
        return;
    }
    auth_json_deny("Public token is not allowed for this endpoint.", 403);
}

$decoded = base64_decode($token, true);
if ($decoded === false) {
    auth_json_deny("Unauthorized access.");
}

$parts = explode('|', $decoded);
if (count($parts) !== 3) {
    auth_json_deny("Unauthorized access.");
}

list($userId, $exp, $sig) = $parts;
$userId = intval($userId);
$exp = intval($exp);
if ($userId <= 0 || $exp <= 0) {
    auth_json_deny("Unauthorized access.");
}

if (time() > $exp) {
    auth_json_deny("Token expired.");
}

$tokenSigningSecret = $API_TOKEN_SIGNING_SECRET ?? $API_AUTH_SECRET;
$expected = hash_hmac('sha256', $userId . '|' . $exp, $tokenSigningSecret);
if (!hash_equals($expected, $sig)) {
    auth_json_deny("Unauthorized access.");
}

$GLOBALS['AUTH_USER_ID'] = $userId;
$GLOBALS['AUTH_IS_PUBLIC_TOKEN'] = false;
?>
