<?php
include "config.php"; // Database connection

// Collect and validate parameters
$group_id   = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
$video_id   = isset($_GET['video_id']) ? intval($_GET['video_id']) : null;
$user_id    = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$season_id  = isset($_GET['season_id']) ? intval($_GET['season_id']) : null;

if (!$group_id || !$video_id || !$user_id || !$season_id) {
    echo json_encode(['error' => 'Missing one or more required parameters (group_id, video_id, user_id, season_id)']);
    exit;
}

// ✅ FIX: uv.group_id = gm.group_id instead of uv.group_id = u.id
$query = "
    SELECT 
        u.id, u.username, u.email, 
        gm.comment, gm.end_date 
    FROM users u
    JOIN group_members gm ON u.id = gm.user_id
    JOIN group_videos uv ON uv.group_id = gm.group_id
    JOIN episode e ON uv.video_id = e.id
    WHERE gm.group_id = ?
      AND u.id = ?
      AND uv.video_id = ?
      AND e.season_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $group_id, $user_id, $video_id, $season_id);
$stmt->execute();
$result = $stmt->get_result();

$response = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $end_date = $row['end_date'];
        $today = date('Y-m-d');
        $status = false;
        $message = '';

        if ($end_date) {
            if ($end_date == $today) {
                $message = '<span class="text-warning">Subscription ends today</span>';
            } elseif ($end_date > $today) {
                $days_left = (strtotime($end_date) - strtotime($today)) / (60 * 60 * 24);
                $status = true;
                $message = '<span class="text-success">Ends in ' . intval($days_left) . ' day(s)</span>';
            } else {
                $message = '<span class="text-danger">Subscription ended</span>';
            }
        } else {
            $message = '<span class="text-danger">No end date</span>';
        }

        $response[] = [
            'user_id' => $row['id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'comment' => $row['comment'],
            'end_date' => $end_date,
            'status' => $status,
            'message' => $message
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    echo json_encode(['message' => 'No matching records found']);
}

$conn->close();
?>
