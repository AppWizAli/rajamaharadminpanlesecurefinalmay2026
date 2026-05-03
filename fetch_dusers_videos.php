<?php

include "config.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$user_id = isset($data['user_id']) ? $data['user_id'] : null;
$season_id = isset($data['season_id']) ? $data['season_id'] : null;
$drama_id = isset($data['drama_id']) ? $data['drama_id'] : null;
$video_id = isset($data['video_id']) ? $data['video_id'] : null;

if ($user_id === null || $season_id === null || $drama_id === null || $video_id === null) {
    echo json_encode(["error" => "All parameters (user_id, season_id, drama_id, video_id) are required"]);
    exit;  // Stop further execution
}  // Close the if statement

$sql = "
    SELECT uv.user_id, uv.video_id, e.id AS episode_id, e.season_id, e.episode_number, e.video_path, 
           e.description, e.privacy, e.download_access, e.thumbnail, 
           s.drama_id, s.season_number, d.name AS drama_name,uv.end_date,u.id,u.username
    FROM user_dvideos uv
    LEFT JOIN episode e ON uv.video_id = e.id
    LEFT JOIN users u ON uv.user_id = u.id
    LEFT JOIN season s ON e.season_id = s.id
    LEFT JOIN drama d ON s.drama_id = d.id
    WHERE uv.user_id = '$user_id' 
      AND e.season_id = '$season_id'
      AND s.drama_id = '$drama_id'
      AND uv.video_id = '$video_id'
";

$result = $conn->query($sql);

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
            
            
            'end_date' => $end_date,
            'status' => $status,
            'message' => $message
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}
 else {
    echo json_encode(['message' => 'No matching records found']);
}

$conn->close();
?>