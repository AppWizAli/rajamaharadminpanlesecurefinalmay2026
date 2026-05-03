<?php
session_start();
include "config.php";
include "video_security.php";
include "media_input_helper.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
