<?php
include "config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get the group ID and video ID from the URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$video_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : 0;

if ($user_id > 0 && $video_id > 0) {
    // Delete video from group
    $delete_sql = "DELETE FROM user_videos WHERE user_id = ? AND video_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $user_id, $video_id);

    if ($delete_stmt->execute()) {
        echo "Video removed from group successfully.";
    } else {
        echo "Error removing video from group: " . $delete_stmt->error;
    }

    $delete_stmt->close();
}

$conn->close();

// Redirect back to the group videos page
header("Location: view_videos.php?id=$user_id");
exit;
?>
