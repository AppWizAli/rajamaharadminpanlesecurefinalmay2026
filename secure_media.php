<?php
include "config.php";
include "video_security.php";

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
