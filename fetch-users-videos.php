<?php
include "config.php";
include "auth_token_check.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$user_id = isset($data['user_id']) ? $data['user_id'] : null;
$season_id = isset($data['season_id']) ? intval($data['season_id']) : null;
$drama_id = isset($data['drama_id']) ? intval($data['drama_id']) : null;
$season_number = isset($data['season_number']) ? intval($data['season_number']) : null;

if ($user_id === null || $season_id === null || $drama_id === null || $season_number === null) {
    http_response_code(400);
    echo json_encode([
        "error" => "All parameters (user_id, season_id, drama_id, season_number) are required"
    ]);
    exit;
}

if ($season_id <= 0 || $drama_id <= 0 || $season_number < 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid parameter values"
    ]);
    exit;
}

try {
    $sql = "
        SELECT uv.user_id, uv.video_id, e.id AS episode_id, e.season_id, e.episode_number, 
               e.video_path, e.description, e.privacy, e.download_access, e.thumbnail,
               e.created_at, s.drama_id, s.season_number, d.name AS drama_name
        FROM user_videos uv
        LEFT JOIN episode e ON uv.video_id = e.id
        LEFT JOIN season s ON e.season_id = s.id
        LEFT JOIN drama d ON s.drama_id = d.id
        WHERE uv.user_id = ? 
          AND e.season_id = ?
          AND s.drama_id = ?
          AND s.season_number = ?
          AND uv.end_date >= CURDATE()
        ORDER BY e.episode_number ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("siii", $user_id, $season_id, $drama_id, $season_number);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $videos = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $videos[] = [
                'user_id' => (string)$row['user_id'],
                'video_id' => (string)$row['video_id'],
                'episode_id' => (string)$row['episode_id'],
                'season_id' => (string)$row['season_id'],
                'episode_number' => (string)$row['episode_number'],
                'video_path' => '',
                'description' => $row['description'],
                'privacy' => $row['privacy'],
                'download_access' => $row['download_access'],
                'thumbnail' => $row['thumbnail'],
                'created_at' => $row['created_at'],
                'drama_id' => (string)$row['drama_id'],
                'drama_name' => $row['drama_name'],
            ];
        }
    }

    // Return directly as array (List<VideoData> in Kotlin)
    echo json_encode($videos);

    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Server Error: " . $e->getMessage()
    ]);
}

$conn->close();
?>




