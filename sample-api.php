<?php
include "config.php";
include "video_security.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
    $drama_id = isset($input['drama_id']) ? intval($input['drama_id']) : 0;
    $season_id = isset($input['season_id']) ? intval($input['season_id']) : 0;
    $season_number = isset($input['season_number']) ? intval($input['season_number']) : null;

    // Validate inputs
    if ($user_id <= 0 || $drama_id <= 0 || $season_id <= 0 || $season_number === null) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid or missing parameters."
        ]);
        exit;
    }

    // SQL query to fetch all episode details based on matching drama_id, season_id, and season_number
    $sql = "
        SELECT e.id AS episode_id, e.season_id, e.episode_number, e.description, 
               e.privacy, e.download_access,e.video_path, e.thumbnail, s.drama_id, s.season_number, d.name AS drama_name
        FROM episode e
        INNER JOIN season s ON e.season_id = s.id
        INNER JOIN drama d ON s.drama_id = d.id
        WHERE s.id = ? AND s.season_number = ? AND s.drama_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $season_id, $season_number, $drama_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $episodes = [];
    try {
        // Fetch all group_ids for the user
        $group_query = "SELECT group_id FROM group_members WHERE user_id = $user_id";
        $group_result = $conn->query($group_query);

        $group_ids = [];
        while ($group = $group_result->fetch_assoc()) {
            $group_ids[] = $group['group_id'];
        }

        // Convert group_ids to a comma-separated string
        if (count($group_ids) > 0) {
            $group_ids_str = implode(",", $group_ids);
        } else {
            $group_ids_str = null;  // No groups, so do not use the IN clause
        }

        // Loop through each episode and apply the privacy logic
        while ($row = $result->fetch_assoc()) {
            $video_id = $row['episode_id'];
            // Store the episode data in the result array (with or without video path)
            $episode_data = [
                'episode_id' => $row['episode_id'],
                'season_id' => $row['season_id'],
                'episode_number' => $row['episode_number'],
                'description' => $row['description'],
                'privacy' => $row['privacy'],
                'download_access' => $row['download_access'],
                'thumbnail' => $row['thumbnail'],
                'drama_id' => $row['drama_id'],
                'drama_name' => $row['drama_name'],
                'video_path' => '',
            ];

            // Add the episode to the final result
            $episodes[] = $episode_data;
        }

        // Return the results
        echo json_encode([
            "success" => true,
            "episodes" => $episodes
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error: " . $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}

// Close database connection
$conn->close();
?>
