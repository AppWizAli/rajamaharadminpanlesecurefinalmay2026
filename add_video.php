<?php
include "config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

function redirect_after_assignment($fallback = 'create_groups.php')
{
    $allowedTargets = ['create_groups.php', 'create_dgroups.php'];
    $requestedTarget = basename($_POST['return_to'] ?? '');
    $target = in_array($requestedTarget, $allowedTargets, true) ? $requestedTarget : $fallback;

    header("Location: " . $target);
    exit;
}

// Handle assigning videos to group
if (isset($_POST['assign_video_to_group'])) {
    $group_id = intval($_POST['group_id'] ?? 0);
    $drama_id = intval($_POST['darama_id'] ?? 0);
    $season_id = intval($_POST['season_id'] ?? 0);
    $assign_all_dramas = isset($_POST['assign_all_dramas']) && $_POST['assign_all_dramas'] === '1';
    $assign_all_seasons = isset($_POST['assign_all_seasons']) && $_POST['assign_all_seasons'] === '1';
    $video_ids = isset($_POST['video_id']) && is_array($_POST['video_id']) ? $_POST['video_id'] : [];
    $bulk_drama_ids = isset($_POST['bulk_drama_ids']) && is_array($_POST['bulk_drama_ids']) ? $_POST['bulk_drama_ids'] : [];
    $bulk_drama_ids = array_values(array_unique(array_filter(array_map('intval', $bulk_drama_ids), function ($dramaId) {
        return $dramaId > 0;
    })));

    if ($group_id <= 0) {
        echo "Group ID is missing or invalid.";
        exit;
    }

    $videos_to_assign = [];
    $stmt = null;

    if (!empty($bulk_drama_ids)) {
        $placeholders = implode(',', array_fill(0, count($bulk_drama_ids), '?'));
        $types = str_repeat('i', count($bulk_drama_ids));
        $sql = "SELECT e.id AS video_id, s.drama_id, s.id AS season_id
                FROM episode e
                INNER JOIN season s ON e.season_id = s.id
                WHERE s.drama_id IN ($placeholders)
                ORDER BY s.drama_id ASC, s.season_number ASC, e.episode_number ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt !== false) {
            $stmt->bind_param($types, ...$bulk_drama_ids);
        }
    } elseif ($assign_all_dramas) {
        $sql = "SELECT e.id AS video_id, s.drama_id, s.id AS season_id
                FROM episode e
                INNER JOIN season s ON e.season_id = s.id
                ORDER BY s.drama_id ASC, s.season_number ASC, e.episode_number ASC";
        $stmt = $conn->prepare($sql);
    } elseif ($drama_id > 0 && $assign_all_seasons) {
        $sql = "SELECT e.id AS video_id, s.drama_id, s.id AS season_id
                FROM episode e
                INNER JOIN season s ON e.season_id = s.id
                WHERE s.drama_id = ?
                ORDER BY s.season_number ASC, e.episode_number ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt !== false) {
            $stmt->bind_param("i", $drama_id);
        }
    } else {
        $video_ids = array_values(array_filter(array_map('intval', $video_ids), function ($video_id) {
            return $video_id > 0;
        }));

        if ($drama_id <= 0) {
            echo "Drama ID is missing or invalid.<br>";
        }
        if ($season_id <= 0) {
            echo "Season ID is missing or invalid.<br>";
        }
        if (empty($video_ids)) {
            echo "No videos selected.<br>";
        }
        if ($drama_id <= 0 || $season_id <= 0 || empty($video_ids)) {
            echo "Invalid input. Please ensure all fields are filled correctly.";
            exit;
        }

        foreach ($video_ids as $video_id) {
            $videos_to_assign[] = [
                'video_id' => $video_id,
                'drama_id' => $drama_id,
                'season_id' => $season_id,
            ];
        }
    }

    if ($stmt === false) {
        die("Error preparing SQL: " . $conn->error);
    }

    if ($stmt instanceof mysqli_stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $videos_to_assign[] = [
                'video_id' => intval($row['video_id']),
                'drama_id' => intval($row['drama_id']),
                'season_id' => intval($row['season_id']),
            ];
        }
        $stmt->close();
    }

    if (empty($videos_to_assign)) {
        echo "No episodes found for the selected option.";
        exit;
    }

    $check_assignment_sql = "SELECT 1 FROM group_videos WHERE group_id = ? AND video_id = ? AND drama_id = ? AND season_id = ?";
    $check_assignment_stmt = $conn->prepare($check_assignment_sql);
    if ($check_assignment_stmt === false) {
        die("Error preparing assignment check SQL: " . $conn->error);
    }

    $insert_assignment_sql = "INSERT INTO group_videos (group_id, video_id, drama_id, season_id) VALUES (?, ?, ?, ?)";
    $insert_assignment_stmt = $conn->prepare($insert_assignment_sql);
    if ($insert_assignment_stmt === false) {
        $check_assignment_stmt->close();
        die("Error preparing insert SQL: " . $conn->error);
    }

    foreach ($videos_to_assign as $assignment) {
        $video_id = intval($assignment['video_id']);
        $assignment_drama_id = intval($assignment['drama_id']);
        $assignment_season_id = intval($assignment['season_id']);

        $check_assignment_stmt->bind_param("iiii", $group_id, $video_id, $assignment_drama_id, $assignment_season_id);
        $check_assignment_stmt->execute();
        $check_assignment_stmt->store_result();

        if ($check_assignment_stmt->num_rows == 0) {
            $insert_assignment_stmt->bind_param("iiii", $group_id, $video_id, $assignment_drama_id, $assignment_season_id);
            $insert_assignment_stmt->execute();
        }
    }

    $check_assignment_stmt->close();
    $insert_assignment_stmt->close();

    redirect_after_assignment();
}
?>
