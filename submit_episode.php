<?php
session_start();
include "config.php";
include "video_security.php";
include "media_input_helper.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', '0');
ini_set('max_input_time', '0');
set_time_limit(0);
header('Content-Type: application/json');

function parse_size_to_bytes($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    switch ($unit) {
        case 'g':
            $number *= 1024;
            // no break
        case 'm':
            $number *= 1024;
            // no break
        case 'k':
            $number *= 1024;
    }
    return (int) $number;
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMaxBytes = parse_size_to_bytes(ini_get('post_max_size'));
if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Upload is too large for the current server limit. Please increase post_max_size/upload_max_filesize or upload a direct video link.'
    ]);
    exit;
}

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $season_id = intval($_POST['season_id']);
    $episode_number = intval($_POST['episode_number']);
    $description = $_POST['description'] ?? '';
    $privacy = $_POST['privacyOptions'] ?? 'public';
    $download_access = $_POST['downloadAccessOptions'] ?? 'never';
    try {
        $thumbnail_path = resolve_media_value(
            $_FILES['thumbnail_file'] ?? [],
            $_POST['thumbnail'] ?? '',
            [
                'label' => 'thumbnail',
                'relativeDirectory' => 'uploads/thumbnails/episode',
                'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'prefix' => 'episode_thumb',
                'required' => true
            ]
        );

        $resolved_video_path = resolve_media_value(
            $_FILES['video_file'] ?? [],
            $_POST['video'] ?? '',
            [
                'label' => 'video',
                'relativeDirectory' => 'uploads/videos',
                'allowedExtensions' => ['mp4', 'm3u8', 'mkv', 'webm', 'mov', 'ts'],
                'prefix' => 'episode_video',
                'stagedUploadToken' => $_POST['video_upload_token'] ?? '',
                'stagedUploadPurpose' => 'episode_video',
                'storeRelativePath' => true,
                'required' => true
            ]
        );
        enforce_secure_video_policy($resolved_video_path, $privacy, $download_access);
    } catch (RuntimeException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }

    $video_path = encrypt_video_path_for_storage($resolved_video_path, $VIDEO_URL_ENCRYPTION_KEY);

    // === Insert into DB ===
    $stmt = $conn->prepare("INSERT INTO episode (season_id, episode_number, video_path, description, privacy, download_access, thumbnail) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisssss", $season_id, $episode_number, $video_path, $description, $privacy, $download_access, $thumbnail_path);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Episode uploaded successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database preparation error: ' . $conn->error]);
    }
}

$conn->close();
