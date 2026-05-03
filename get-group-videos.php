<?php
include "config.php";
include "auth_token_check.php";
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
    $drama_id = isset($input['drama_id']) ? intval($input['drama_id']) : 0;
    $season_id = isset($input['season_id']) ? intval($input['season_id']) : 0;
    $season_number = isset($input['season_number']) ? intval($input['season_number']) : null;
    if ($user_id > 0 && $drama_id > 0 && $season_id > 0 && $season_number !== null) {
        try {
            // Fetch all group_ids for the user
            $group_query = "SELECT group_id FROM group_members WHERE user_id = ?";
            $group_stmt = $conn->prepare($group_query);
            $group_stmt->bind_param("i", $user_id);
            $group_stmt->execute();
            $group_result = $group_stmt->get_result();
            $group_ids = [];
            while ($group = $group_result->fetch_assoc()) {
                $group_ids[] = $group['group_id'];
            }
            if (!empty($group_ids)) {
                // Build placeholders for the group_ids
                $placeholders = implode(",", array_fill(0, count($group_ids), "?"));
                // SQL query with all parameters
                $sql = "
                    SELECT gv.group_id, gv.video_id, e.id AS episode_id, e.season_id, e.episode_number,
                           e.video_path, e.description, e.privacy, e.download_access, e.thumbnail,
                           e.created_at, s.drama_id, s.season_number, d.name AS drama_name
                    FROM group_videos gv
                    LEFT JOIN episode e ON gv.video_id = e.id
                    LEFT JOIN season s ON e.season_id = s.id
                    LEFT JOIN drama d ON s.drama_id = d.id
                    WHERE gv.group_id IN ($placeholders)
                      AND s.id = ?
                      AND s.drama_id = ?
                      AND s.season_number = ?
                ";
                // Merge all input parameters for binding
                $params = array_merge($group_ids, [$season_id, $drama_id, $season_number]);
                $types = str_repeat("i", count($group_ids)) . "iii"; // Bind types for all parameters
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                // Fetch results
                $videos = $result->fetch_all(MYSQLI_ASSOC);
                foreach ($videos as &$video) {
                    if (isset($video['video_path'])) {
                        $video['video_path'] = "";
                    }
                }
                unset($video);
                // Return results as JSON
                echo json_encode([
                    "success" => true,
                    "videos" => $videos
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "No groups found for the user."
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid or missing parameters."
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
