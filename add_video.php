<?php
include "config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Handle assigning videos to group
if (isset($_POST['assign_video_to_group'])) {
    // Get POST data and sanitize it
    $group_id = intval($_POST['group_id']);
    $drama_id = intval($_POST['darama_id']);
    $season_id = intval($_POST['season_id']);
    $video_ids = isset($_POST['video_id']) ? $_POST['video_id'] : []; // Array of Video IDs

    // Debugging: Output the values to check what is coming in POST
    //var_dump($group_id, $drama_id, $season_id, $video_ids);
    
    // Validate input
    if ($group_id > 0 && $drama_id > 0 && $season_id > 0 && !empty($video_ids)) {
        foreach ($video_ids as $video_id) {
            $video_id = intval($video_id); // Convert each video ID to integer

            // Check if the video is already assigned
            $check_assignment_sql = "SELECT * FROM group_videos WHERE group_id = ? AND video_id = ? AND drama_id = ? AND season_id = ?";
            $check_assignment_stmt = $conn->prepare($check_assignment_sql);
            $check_assignment_stmt->bind_param("iiii", $group_id, $video_id, $drama_id, $season_id);
            $check_assignment_stmt->execute();
            $check_assignment_stmt->store_result();

            if ($check_assignment_stmt->num_rows == 0) {
                // Assign the video to the group without season_number
                $insert_assignment_sql = "INSERT INTO group_videos (group_id, video_id, drama_id, season_id) VALUES (?, ?, ?, ?)";
                $insert_assignment_stmt = $conn->prepare($insert_assignment_sql);
                $insert_assignment_stmt->bind_param("iiii", $group_id, $video_id, $drama_id, $season_id);
                $insert_assignment_stmt->execute();
                $insert_assignment_stmt->close();
            }

            $check_assignment_stmt->close();
        }

        // Redirect after processing
        header("Location: create_groups.php");
        exit;
    } else {
        // Error output if validation fails
        if ($group_id <= 0) {
            echo "Group ID is missing or invalid.<br>";
        }
        if ($drama_id <= 0) {
            echo "Drama ID is missing or invalid.<br>";
        }
        if ($season_id <= 0) {
            echo "Season ID is missing or invalid.<br>";
        }
        if (empty($video_ids)) {
            echo "No videos selected.<br>";
        }
        echo "Invalid input. Please ensure all fields are filled correctly.";
    }
}
?>
