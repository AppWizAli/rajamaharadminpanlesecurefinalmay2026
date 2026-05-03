<?php
header("Content-Type: application/json");
include "auth_token_check.php";
include "config.php";
include "media_input_helper.php";

// Read the JSON data from POST request
$data = json_decode(file_get_contents("php://input"), true);

// Ensure season_id is provided and is a valid integer
$season_id = isset($data['season_id']) ? intval($data['season_id']) : 0;

if ($season_id <= 0) {
    echo json_encode(array("error" => "Invalid or missing season ID."));
    exit;
}

// Query to fetch episodes for the given season_id
$sql = "SELECT * FROM episode WHERE season_id = ? ORDER BY episode_number ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $season_id);
$stmt->execute();
$result = $stmt->get_result();

$episodes = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['video_path'] = "";
        if (isset($row['thumbnail'])) {
            $row['thumbnail'] = media_to_client_url($row['thumbnail']);
        }
        $episodes[] = $row;
    }
} else {
    $episodes = array("message" => "No episodes found for the provided season ID.");
}

echo json_encode($episodes);

$conn->close();
?>
