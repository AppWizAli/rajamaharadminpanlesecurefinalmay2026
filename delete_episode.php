<?php
session_start();
include "config.php";
include "video_security.php";
include "media_input_helper.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$episode_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$season_id = isset($_GET['season_id']) ? intval($_GET['season_id']) : 0;

if ($episode_id > 0 && $season_id > 0) {
    $sql = "SELECT video_path, thumbnail FROM episode WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $episode_id);
    $stmt->execute();
    $stmt->bind_result($video_path, $thumbnail_path);
    $stmt->fetch();
    $stmt->close();

    $resolvedVideoPath = decrypt_video_path_if_needed($video_path, $VIDEO_URL_ENCRYPTION_KEY);
    $videoFile = resolve_video_file_path($resolvedVideoPath, $VIDEO_STORAGE_BASE);
    if (!empty($videoFile) && file_exists($videoFile)) {
        unlink($videoFile);
    }
    $thumbFile = media_resolve_local_public_file($thumbnail_path);
    if (!empty($thumbFile) && file_exists($thumbFile)) {
        unlink($thumbFile);
    }

    $sql = "DELETE FROM group_videos WHERE video_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $episode_id);
    if (!$stmt->execute()) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }
    $stmt->close();

    $sql = "DELETE FROM episode WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $episode_id);
    if (!$stmt->execute()) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }
    $stmt->close();

    $_SESSION['season_id'] = $season_id;

    header("Location: view_episods.php?season_id=$season_id");
    exit;
} else {
    echo "Invalid episode ID or season ID.";
}
?>

