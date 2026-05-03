<?php
session_start();
include "config.php";
include "video_security.php";
include "media_input_helper.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql_fetch_drama_id = "SELECT drama_id FROM season WHERE id = ?";
$stmt_fetch_drama_id = $conn->prepare($sql_fetch_drama_id);
$stmt_fetch_drama_id->bind_param("i", $id);
$stmt_fetch_drama_id->execute();
$stmt_fetch_drama_id->bind_result($drama_id);
$stmt_fetch_drama_id->fetch();
$stmt_fetch_drama_id->close();

$sql_fetch_episodes = "SELECT id, video_path, thumbnail FROM episode WHERE season_id = ?";
$stmt_fetch_episodes = $conn->prepare($sql_fetch_episodes);
$stmt_fetch_episodes->bind_param("i", $id);
$stmt_fetch_episodes->execute();
$result = $stmt_fetch_episodes->get_result();

$episodes = [];
while ($row = $result->fetch_assoc()) {
    $episodes[] = $row;
}
$stmt_fetch_episodes->close();

foreach ($episodes as $episode) {
    $vp = decrypt_video_path_if_needed($episode['video_path'], $VIDEO_URL_ENCRYPTION_KEY);
    $video_file = resolve_video_file_path($vp, $VIDEO_STORAGE_BASE);
    if (!empty($video_file) && file_exists($video_file)) {
        unlink($video_file);
    }
    $thumbFile = media_resolve_local_public_file($episode['thumbnail']);
    if (!empty($thumbFile) && file_exists($thumbFile)) {
        unlink($thumbFile);
    }

    $sql_delete_episode = "DELETE FROM episode WHERE id = ?";
    $stmt_delete_episode = $conn->prepare($sql_delete_episode);
    $stmt_delete_episode->bind_param("i", $episode['id']);
    $stmt_delete_episode->execute();
    $stmt_delete_episode->close();
}

$sql_delete_season = "DELETE FROM season WHERE id = ?";
$stmt_delete_season = $conn->prepare($sql_delete_season);
$stmt_delete_season->bind_param("i", $id);

if ($stmt_delete_season->execute()) {
    $stmt_delete_season->close();
    $conn->close();
    header("Location: view_season.php?drama_id=$drama_id");
    exit;
} else {
    echo "Error deleting season: " . $stmt_delete_season->error;
}

$stmt_delete_season->close();
$conn->close();
?>

