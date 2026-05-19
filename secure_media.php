<?php
include "config.php";
include "video_security.php";

function remote_media_extension($url) {
    $path = (string) parse_url($url, PHP_URL_PATH);
    return strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
}

function stream_remote_https_media($url, $purpose) {
    if (!function_exists('curl_init')) {
        http_response_code(502);
        echo "Remote media streaming is not available on this server.";
        exit;
    }

    if (ob_get_level()) {
        ob_end_clean();
    }

    ignore_user_abort(true);
    set_time_limit(0);

    $rangeHeader = isset($_SERVER['HTTP_RANGE']) ? trim((string) $_SERVER['HTTP_RANGE']) : '';
    $responseHeaders = [];
    $statusCode = 200;
    $headersSent = false;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FAILONERROR => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_BUFFERSIZE => 1024 * 1024,
        CURLOPT_USERAGENT => 'UrduBoloSecureMedia/1.0',
        CURLOPT_HTTPHEADER => $rangeHeader !== '' ? ["Range: $rangeHeader"] : [],
        CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$responseHeaders, &$statusCode) {
            $length = strlen($headerLine);
            $trimmed = trim($headerLine);

            if ($trimmed === '') {
                return $length;
            }

            if (stripos($trimmed, 'HTTP/') === 0) {
                $responseHeaders = [];
                $parts = preg_split('/\s+/', $trimmed);
                $statusCode = isset($parts[1]) ? intval($parts[1]) : 200;
                return $length;
            }

            $separatorPos = strpos($headerLine, ':');
            if ($separatorPos === false) {
                return $length;
            }

            $name = strtolower(trim(substr($headerLine, 0, $separatorPos)));
            $value = trim(substr($headerLine, $separatorPos + 1));
            if (in_array($name, ['content-type', 'content-length', 'content-range', 'accept-ranges'], true)) {
                $responseHeaders[$name] = $value;
            }

            return $length;
        },
        CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use (&$headersSent, &$responseHeaders, &$statusCode, $purpose, $url) {
            if (!$headersSent) {
                http_response_code($statusCode > 0 ? $statusCode : 200);
                foreach ($responseHeaders as $name => $value) {
                    header($name . ': ' . $value);
                }
                header('Cache-Control: private, no-store, max-age=0');
                header('Pragma: no-cache');
                header('Referrer-Policy: no-referrer');
                header('X-Content-Type-Options: nosniff');
                if ($purpose === 'download') {
                    header('Content-Disposition: attachment; filename="' . basename((string) parse_url($url, PHP_URL_PATH)) . '"');
                }
                $headersSent = true;
            }

            echo $chunk;
            flush();
            return strlen($chunk);
        }
    ]);

    $success = curl_exec($ch);
    if ($success === false) {
        $error = curl_error($ch);
        curl_close($ch);
        if (!$headersSent) {
            http_response_code(502);
            echo "Remote media request failed: " . $error;
        }
        exit;
    }

    curl_close($ch);

    if (!$headersSent) {
        http_response_code($statusCode > 0 ? $statusCode : 200);
        foreach ($responseHeaders as $name => $value) {
            header($name . ': ' . $value);
        }
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        if ($purpose === 'download') {
            header('Content-Disposition: attachment; filename="' . basename((string) parse_url($url, PHP_URL_PATH)) . '"');
        }
    }
    exit;
}

$video_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$exp = isset($_GET['exp']) ? intval($_GET['exp']) : 0;
$purpose = isset($_GET['purpose']) ? $_GET['purpose'] : "playback";
$sig = isset($_GET['sig']) ? $_GET['sig'] : "";

if ($video_id <= 0 || $user_id <= 0 || $exp <= 0 || empty($sig)) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

if ($purpose !== "playback" && $purpose !== "download") {
    $purpose = "playback";
}

if (time() > $exp) {
    http_response_code(403);
    echo "Link expired.";
    exit;
}

if (!verify_video_signature($video_id, $user_id, $exp, $purpose, $VIDEO_SIGN_SECRET, $sig)) {
    http_response_code(403);
    echo "Invalid signature.";
    exit;
}

// Fetch video path and privacy
$stmt = $conn->prepare("SELECT video_path, download_access, privacy FROM episode WHERE id = ?");
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    echo "Video not found.";
    exit;
}
$row = $result->fetch_assoc();
$stmt->close();

$privacy = $row['privacy'] ?? 'private';
$download_access = $row['download_access'] ?? 'never';

if ($privacy !== "public" && !user_has_video_access($conn, $user_id, $video_id)) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

if ($purpose === "download" && $download_access === "never") {
    http_response_code(403);
    echo "Download not allowed.";
    exit;
}

$video_path = $row['video_path'] ?? '';
$video_path = decrypt_video_path_if_needed($video_path, $VIDEO_URL_ENCRYPTION_KEY);

$file = resolve_video_file_path($video_path, $VIDEO_STORAGE_BASE);
if ($file === null || !is_file($file)) {
    $file = null;
}

if ($file === null && preg_match('#^https?://#i', $video_path)) {
    if (stripos($video_path, 'https://') !== 0) {
        http_response_code(403);
        echo "Only HTTPS external playback links are allowed.";
        exit;
    }

    if (remote_media_extension($video_path) !== 'm3u8') {
        stream_remote_https_media($video_path, $purpose);
    }

    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
    header('Referrer-Policy: no-referrer');
    header('Location: ' . $video_path, true, 302);
    exit;
}

if ($file === null) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

$size = filesize($file);
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$contentType = "video/mp4";
if ($ext === "mkv") $contentType = "video/x-matroska";
if ($ext === "webm") $contentType = "video/webm";
if ($ext === "m3u8") $contentType = "application/vnd.apple.mpegurl";
if ($ext === "mov") $contentType = "video/quicktime";
if ($ext === "ts") $contentType = "video/mp2t";

if (ob_get_level()) {
    ob_end_clean();
}
set_time_limit(0);

header("Content-Type: $contentType");
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header("Accept-Ranges: bytes");
header("X-Content-Type-Options: nosniff");
if ($purpose === "download") {
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
}

$start = 0;
$end = $size - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    if (preg_match('/bytes=(\\d*)-(\\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
        if ($matches[1] !== '') $start = intval($matches[1]);
        if ($matches[2] !== '') $end = intval($matches[2]);
        if ($start > $end || $end >= $size) {
            http_response_code(416);
            header("Content-Range: bytes */$size");
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$size");
    }
}

$length = $end - $start + 1;
header("Content-Length: $length");

$fp = fopen($file, 'rb');
fseek($fp, $start);

$buffer = 8192;
while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
    if ($pos + $buffer > $end) {
        $buffer = $end - $pos + 1;
    }
    echo fread($fp, $buffer);
    flush();
}

fclose($fp);
exit;
?>
