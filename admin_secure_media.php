<?php
session_start();
include "config.php";
include "video_security.php";

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$video_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : 0;
if ($video_id <= 0) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

$stmt = $conn->prepare("SELECT video_path FROM episode WHERE id = ?");
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

$video_path = $row['video_path'] ?? '';
$video_path = decrypt_video_path_if_needed($video_path, $VIDEO_URL_ENCRYPTION_KEY);

$file = resolve_video_file_path($video_path, $VIDEO_STORAGE_BASE);
if ($file === null || !is_file($file)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

$size = filesize($file);
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$contentType = "video/mp4";
if ($ext === "mkv") $contentType = "video/x-matroska";
if ($ext === "webm") $contentType = "video/webm";

if (ob_get_level()) {
    ob_end_clean();
}
set_time_limit(0);

header("Content-Type: $contentType");
header("Accept-Ranges: bytes");
header("X-Content-Type-Options: nosniff");

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
