<?php
include "config.php";
include "auth_token_check.php";
include "video_security.php";

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => false, "message" => "Invalid request method."]);
    exit;
}

$input = json_input();
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$video_id = isset($input['video_id']) ? intval($input['video_id']) : 0;
$purpose = isset($input['purpose']) ? trim($input['purpose']) : "playback";

if ($user_id <= 0 || $video_id <= 0) {
    echo json_encode(["status" => false, "message" => "Invalid user_id or video_id."]);
    exit;
}

if ($purpose !== "playback" && $purpose !== "download") {
    $purpose = "playback";
}

// Fetch video details
$stmt = $conn->prepare("SELECT id, video_path, download_access, privacy FROM episode WHERE id = ?");
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    echo json_encode(["status" => false, "message" => "Video not found."]);
    exit;
}
$row = $result->fetch_assoc();
$stmt->close();

$download_access = $row['download_access'] ?? 'never';
$privacy = $row['privacy'] ?? 'private';

// Check access (public videos are accessible to all)
if ($privacy !== "public" && !user_has_video_access($conn, $user_id, $video_id)) {
    echo json_encode(["status" => false, "message" => "Access denied."]);
    exit;
}

// Enforce download policy
if ($purpose === "download" && $download_access === "never") {
    echo json_encode(["status" => false, "message" => "Download not allowed."]);
    exit;
}

// Build signed URLs
$base = build_base_url();
$now = time();
$expPlayback = $now + $VIDEO_SIGN_TTL_PLAYBACK;
$expDownload = $now + $VIDEO_SIGN_TTL_DOWNLOAD;

$sigPlayback = sign_video_url($video_id, $user_id, $expPlayback, "playback", $VIDEO_SIGN_SECRET);
$sigDownload = sign_video_url($video_id, $user_id, $expDownload, "download", $VIDEO_SIGN_SECRET);

$playback_url = $base . "/secure_media.php?video_id={$video_id}&user_id={$user_id}&exp={$expPlayback}&purpose=playback&sig={$sigPlayback}";
$download_url = $base . "/secure_media.php?video_id={$video_id}&user_id={$user_id}&exp={$expDownload}&purpose=download&sig={$sigDownload}";

$cache_key = hash_hmac('sha256', $user_id . "|" . $video_id, $VIDEO_SIGN_SECRET);

// Close DB connection
$conn->close();

echo json_encode([
    "status" => true,
    "playback_url" => $playback_url,
    "download_url" => $download_url,
    "cache_key" => $cache_key,
    "expires_at" => $purpose === "download" ? ($expDownload * 1000) : ($expPlayback * 1000),
    "drm_scheme" => null,
    "drm_license_url" => null,
    "drm_headers" => null
]);
?>
