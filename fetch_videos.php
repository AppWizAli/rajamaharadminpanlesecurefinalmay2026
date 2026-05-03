<?php
session_start();
include "config.php";
include "auth_token_check.php";
include "media_input_helper.php";
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// Fetch all episodes
$sql = "SELECT * FROM episode";
$result = $conn->query($sql);

$response = array("success" => false, "videos" => array());

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['video_path'] = "";
        if (isset($row['thumbnail'])) {
            $row['thumbnail'] = media_to_client_url($row['thumbnail']);
        }
        $response["videos"][] = $row;
    }
    $response["success"] = true;
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
