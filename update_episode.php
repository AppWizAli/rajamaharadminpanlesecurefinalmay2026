<?php
include "config.php";
include "video_security.php";
include "media_input_helper.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
    $season_id = intval($_POST['season_id']);
    $episode_number = intval($_POST['episode_number']);
    $description = $_POST['description'];
    $privacy = $_POST['privacy'] ?? '';
    $download_access = $_POST['downloadAccessOptions'][0] ?? '';
    $video_path = isset($_POST['video_path']) ? trim($_POST['video_path']) : null;
    $thumbnail_path = isset($_POST['thumbnail']) ? trim($_POST['thumbnail']) : null;

    try {
        $thumbnail_path = resolve_media_value(
            $_FILES['thumbnail_file'] ?? [],
            $thumbnail_path ?? '',
            [
                'label' => 'thumbnail',
                'relativeDirectory' => 'uploads/thumbnails/episode',
                'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'prefix' => 'episode_thumb',
                'required' => false
            ]
        );

        $resolvedVideoPath = resolve_media_value(
            $_FILES['video_file'] ?? [],
            $video_path ?? '',
            [
                'label' => 'video',
                'relativeDirectory' => 'uploads/videos',
                'allowedExtensions' => ['mp4', 'm3u8', 'mkv', 'webm', 'mov', 'ts'],
                'prefix' => 'episode_video',
                'storeRelativePath' => true,
                'required' => false
            ]
        );

        if ($resolvedVideoPath !== '') {
            enforce_secure_video_policy($resolvedVideoPath, $privacy, $download_access);
            $video_path = encrypt_video_path_for_storage($resolvedVideoPath, $VIDEO_URL_ENCRYPTION_KEY);
        }
    } catch (RuntimeException $e) {
        die($e->getMessage());
    }


    if ($episode_id > 0) {
        $sql = "UPDATE episode SET episode_number = ?, description = ?, privacy = ?, download_access = ?";
        $params = [$episode_number, $description, $privacy, $download_access];
        $types = "isss";

        if ($video_path) {
            $sql .= ", video_path = ?";
            $params[] = $video_path;
            $types .= "s";
        }

        if ($thumbnail_path) {
            $sql .= ", thumbnail = ?";
            $params[] = $thumbnail_path;
            $types .= "s";
        }

        $sql .= " WHERE id = ?";
        $params[] = $episode_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    } else {
        $sql = "INSERT INTO episode (season_id, episode_number, video_path, description, privacy, download_access, thumbnail)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssss", $season_id, $episode_number, $video_path, $description, $privacy, $download_access, $thumbnail_path);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: view_episods.php?season_id=$season_id");
    exit;
}
?>

