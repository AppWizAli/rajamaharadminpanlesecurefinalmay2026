<?php
include "config.php";
include "auth_token_check.php";
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = enforce_authenticated_user_match(isset($input['user_id']) ? intval($input['user_id']) : 0);
    if ($user_id > 0) {
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
            if (count($group_ids) > 0) {
                // Fetch all video_ids associated with the user's groups
                $in_groups = str_repeat('?,', count($group_ids) - 1) . '?';
                $video_query = "SELECT video_id FROM group_videos WHERE group_id IN ($in_groups)";
                $video_stmt = $conn->prepare($video_query);
                $video_stmt->bind_param(str_repeat('i', count($group_ids)), ...$group_ids);
                $video_stmt->execute();
                $video_result = $video_stmt->get_result();
                $video_ids = [];
                while ($video = $video_result->fetch_assoc()) {
                    $video_ids[] = $video['video_id'];
                }
                if (count($video_ids) > 0) {
                    // Fetch video details with drama_id, season_id, video_id, created_at, season_number, and drama_name using JOINs
                    $in_videos = str_repeat('?,', count($video_ids) - 1) . '?';
                    $details_query = "
                        SELECT 
                            e.id as video_id,
                            e.created_at,
                            e.*,
                            s.id as season_id,
                            s.season_number,
                            d.id as drama_id,
                            d.name as drama_name
                        FROM episode e
                        INNER JOIN season s ON e.season_id = s.id
                        INNER JOIN drama d ON s.drama_id = d.id
                        WHERE e.id IN ($in_videos)
                    ";
                    $details_stmt = $conn->prepare($details_query);
                    $details_stmt->bind_param(str_repeat('i', count($video_ids)), ...$video_ids);
                    $details_stmt->execute();
                    $details_result = $details_stmt->get_result();
                    $videos = $details_result->fetch_all(MYSQLI_ASSOC);
                    foreach ($videos as &$video) {
                        if (isset($video['video_path'])) {
                            $video['video_path'] = "";
                        }
                    }
                    unset($video);
                    
                    // Return the results as JSON
                    echo json_encode([
                        "success" => true,
                        "videos" => $videos
                    ]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "No videos found for the user's groups."
                    ]);
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "User is not part of any group."
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
            "message" => "Invalid user ID."
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}
// Close statements and connection
if (isset($group_stmt)) $group_stmt->close();
if (isset($video_stmt)) $video_stmt->close();
if (isset($details_stmt)) $details_stmt->close();
$conn->close();
?>
