<?php
include "auth_token_check.php";
include "config.php";

header('Content-Type: application/json');

if (isset($_GET['user_id'], $_GET['video_id'])) {
    $user_id = intval($_GET['user_id']);
    $video_id = intval($_GET['video_id']);
    $today = date('Y-m-d');
    
    // Validate parameters
    if ($user_id <= 0 || $video_id <= 0) {
        echo json_encode([
            "status" => false,
            "message" => "Invalid user_id or video_id - must be positive numbers"
        ]);
        exit;
    }

    // Step 1: Check if video exists
    $stmt = $conn->prepare("SELECT 1 FROM episode WHERE id = ?");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        echo json_encode([
            "status" => false,
            "message" => "Video ID $video_id does not exist in database"
        ]);
        exit;
    }
    $stmt->close();

    // Step 2: Check group access
    $stmt = $conn->prepare("
        SELECT 1 
        FROM group_members gm 
        JOIN group_videos gv ON gm.group_id = gv.group_id
        WHERE gm.user_id = ? AND gm.end_date >= ? AND gv.video_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("isi", $user_id, $today, $video_id);
    $stmt->execute();
    $group_result = $stmt->get_result();
    $has_group_access = $group_result->num_rows > 0;
    $stmt->close();

    // Step 3: Check direct access
    $stmt = $conn->prepare("
        SELECT 1 
        FROM user_videos uv 
        WHERE uv.user_id = ? AND uv.video_id = ? AND uv.end_date >= ?
        LIMIT 1
    ");
    $stmt->bind_param("iis", $user_id, $video_id, $today);
    $stmt->execute();
    $direct_result = $stmt->get_result();
    $has_direct_access = $direct_result->num_rows > 0;
    $stmt->close();

    // Final response - EXACT SAME FORMAT as original
    if ($has_group_access || $has_direct_access) {
        echo json_encode([
            "status" => true,
            "message" => "User has access to the video."
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Access denied. User is not in a group with this video and has no direct assignment."
        ]);
    }
    
} else {
    echo json_encode([
        "status" => false,
        "message" => "Missing required parameters (user_id, video_id)."
    ]);
}

$conn->close();
?>