<?php
// Shared helpers for secure video URLs and decryption

function json_input() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function build_base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? "localhost";
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\");
    return $scheme . "://" . $host . $dir;
}

function sign_video_url($video_id, $user_id, $exp, $purpose, $secret) {
    $payload = $video_id . "|" . $user_id . "|" . $exp . "|" . $purpose;
    return hash_hmac('sha256', $payload, $secret);
}

function verify_video_signature($video_id, $user_id, $exp, $purpose, $secret, $sig) {
    $expected = sign_video_url($video_id, $user_id, $exp, $purpose, $secret);
    return hash_equals($expected, $sig);
}

function decrypt_video_path_if_needed($value, $key) {
    if ($value === null || $value === '') return $value;
    $lower = strtolower($value);
    if (preg_match('#^https?://#', $value)
        || strpos($lower, 'uploads/') === 0
        || strpos($lower, '/uploads/') !== false
        || preg_match('/\.(mp4|mkv|webm|m3u8|ts)$/', $lower)
    ) {
        return $value;
    }

    $raw = base64_decode($value, true);
    if ($raw === false || strlen($raw) <= 16) {
        return $value;
    }
    $iv = substr($raw, 0, 16);
    $ciphertext = substr($raw, 16);

    $keyBytes = $key;
    if (strlen($keyBytes) !== 32) {
        $keyBytes = substr(hash('sha256', $key, true), 0, 32);
    }

    $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $keyBytes, OPENSSL_RAW_DATA, $iv);
    if ($plain === false || $plain === '') {
        return $value;
    }

    $plainLower = strtolower($plain);
    if (preg_match('#^https?://#', $plain)
        || strpos($plainLower, 'uploads/') === 0
        || strpos($plainLower, '/uploads/') !== false
        || preg_match('/\.(mp4|mkv|webm|m3u8|ts)$/', $plainLower)
    ) {
        return $plain;
    }

    return $value;
}

function encrypt_video_path_for_storage($value, $key) {
    $value = trim((string) $value);
    if ($value === '') return '';

    $alreadyDecoded = decrypt_video_path_if_needed($value, $key);
    if ($alreadyDecoded !== $value) {
        return $value;
    }

    $keyBytes = $key;
    if (strlen($keyBytes) !== 32) {
        $keyBytes = substr(hash('sha256', $key, true), 0, 32);
    }

    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($value, 'AES-256-CBC', $keyBytes, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        return $value;
    }

    return base64_encode($iv . $ciphertext);
}

function resolve_video_file_path($video_path, $storage_base) {
    if (!$video_path) return null;

    if (file_exists($video_path)) {
        $realBase = realpath($storage_base);
        $realFile = realpath($video_path);
        if ($realBase !== false && $realFile !== false && strpos($realFile, $realBase) === 0) {
            return $realFile;
        }
    }

    if (preg_match('#^https?://#', $video_path)) {
        $url = parse_url($video_path);
        if (!empty($url['path'])) {
            $video_path = ltrim($url['path'], '/');
        }
    }

    $video_path = ltrim($video_path, '/');
    if (strpos($video_path, 'uploads/') === 0) {
        $video_path = substr($video_path, strlen('uploads/'));
    }

    $full = rtrim($storage_base, "/\\") . DIRECTORY_SEPARATOR . $video_path;
    $realBase = realpath($storage_base);
    $realFile = realpath($full);
    if ($realBase === false || $realFile === false) return null;
    if (strpos($realFile, $realBase) !== 0) return null;
    return $realFile;
}

function user_has_video_access($conn, $user_id, $video_id) {
    $today = date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT 1
        FROM group_members gm
        JOIN group_videos gv ON gm.group_id = gv.group_id
        WHERE gm.user_id = ? AND gm.end_date >= ? AND gv.video_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("isi", $user_id, $today, $video_id);
    $stmt->execute();
    $group_result = $stmt->get_result();
    $has_group_access = $group_result->num_rows > 0;
    $stmt->close();

    if ($has_group_access) return true;

    $stmt = $conn->prepare("
        SELECT 1
        FROM user_videos uv
        WHERE uv.user_id = ? AND uv.video_id = ? AND uv.end_date >= ?
        LIMIT 1
    ");
    $stmt->bind_param("iis", $user_id, $video_id, $today);
    $stmt->execute();
    $direct_result = $stmt->get_result();
    $has_direct_access = $direct_result->num_rows > 0;
    $stmt->close();

    return $has_direct_access;
}
?>
