<?php
if (!function_exists('api_security_json_error')) {
    function api_security_json_error($message, $code = 400) {
        http_response_code($code);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "status" => false,
            "message" => "",
            "error" => $message
        ]);
        exit;
    }
}

if (!function_exists('api_security_client_ip')) {
    function api_security_client_ip() {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            if (strpos($candidate, ',') !== false) {
                $candidate = trim(explode(',', $candidate)[0]);
            }

            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return 'unknown';
    }
}

if (!function_exists('api_security_storage_dir')) {
    function api_security_storage_dir() {
        static $dir = null;
        if ($dir !== null) {
            return $dir;
        }

        $base = sys_get_temp_dir();
        if (!is_string($base) || trim($base) === '') {
            $base = __DIR__;
        }

        $dir = rtrim($base, "\\/") . DIRECTORY_SEPARATOR . 'urdu_bolo_api_limits';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (!is_dir($dir)) {
            $dir = __DIR__;
        }

        return $dir;
    }
}

if (!function_exists('api_security_rate_limit')) {
    function api_security_rate_limit($scope, $identifier, $limit, $windowSeconds) {
        $scope = trim((string) $scope);
        $identifier = trim((string) $identifier);
        $limit = max(1, intval($limit));
        $windowSeconds = max(1, intval($windowSeconds));

        $path = api_security_storage_dir() . DIRECTORY_SEPARATOR . hash('sha256', $scope . '|' . $identifier) . '.json';
        $now = time();
        $windowStart = $now - $windowSeconds;
        $timestamps = [];

        if (is_file($path)) {
            $raw = @file_get_contents($path);
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $timestamp) {
                    $timestamp = intval($timestamp);
                    if ($timestamp >= $windowStart) {
                        $timestamps[] = $timestamp;
                    }
                }
            }
        }

        if (count($timestamps) >= $limit) {
            $retryAfter = max(1, ($timestamps[0] + $windowSeconds) - $now);
            return [
                'allowed' => false,
                'retry_after' => $retryAfter
            ];
        }

        $timestamps[] = $now;
        @file_put_contents($path, json_encode($timestamps), LOCK_EX);

        return [
            'allowed' => true,
            'retry_after' => 0
        ];
    }
}

if (!function_exists('api_security_throttle_or_fail')) {
    function api_security_throttle_or_fail($scope, $identifier, $limit, $windowSeconds, $message) {
        $result = api_security_rate_limit($scope, $identifier, $limit, $windowSeconds);
        if (!$result['allowed']) {
            header('Retry-After: ' . intval($result['retry_after']));
            api_security_json_error($message, 429);
        }
    }
}

if (!function_exists('api_security_normalize_email')) {
    function api_security_normalize_email($email) {
        return strtolower(trim((string) $email));
    }
}

if (!function_exists('api_security_normalize_username')) {
    function api_security_normalize_username($username) {
        $username = preg_replace('/\s+/', ' ', trim((string) $username));
        return $username === null ? '' : $username;
    }
}

if (!function_exists('api_security_validate_email')) {
    function api_security_validate_email($email, &$message) {
        if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            return false;
        }

        $message = '';
        return true;
    }
}

if (!function_exists('api_security_validate_password')) {
    function api_security_validate_password($password, &$message) {
        $password = (string) $password;
        if (strlen($password) < 8) {
            $message = "Password must be at least 8 characters.";
            return false;
        }

        if (strlen($password) > 128) {
            $message = "Password is too long.";
            return false;
        }

        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $message = "Password must include at least one letter and one number.";
            return false;
        }

        $message = '';
        return true;
    }
}

if (!function_exists('api_security_validate_username')) {
    function api_security_validate_username($username, &$message) {
        $username = api_security_normalize_username($username);
        $compact = preg_replace('/[^A-Za-z0-9]/', '', $username);
        $compact = $compact === null ? '' : $compact;

        if (strlen($username) < 5 || strlen($username) > 40) {
            $message = "Username must be between 5 and 40 characters.";
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._-]{3,38}[A-Za-z0-9]$/', $username)) {
            $message = "Username can use letters, numbers, spaces, dot, underscore, and dash only.";
            return false;
        }

        if (preg_match('/([A-Za-z0-9])\1{3,}/', $username)) {
            $message = "Please use a real name instead of repeated characters.";
            return false;
        }

        preg_match_all('/[A-Za-z]/', $username, $letters);
        if (count($letters[0]) < 3) {
            $message = "Username must contain real letters.";
            return false;
        }

        $wordCount = count(array_filter(preg_split('/\s+/', $username) ?: [], 'strlen'));
        if ($wordCount < 2 && strlen($compact) < 7) {
            $message = "Please enter a more complete name.";
            return false;
        }

        $vowelCount = preg_match_all('/[aeiou]/i', $username);
        if ($wordCount < 2 && $vowelCount === 0 && strlen($compact) < 9) {
            $message = "Please enter a valid-looking name.";
            return false;
        }

        $message = '';
        return true;
    }
}
?>
