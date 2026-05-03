<?php
header("Content-Type: application/json");

include "config.php";

// Fetch the latest banner video
$sql = "SELECT video_url FROM banners LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $video_url = $row['video_url'];
    if (!empty($video_url)) {
        $response = array("success" => true, "video_url" => $video_url);
    } else {
        $response = array("success" => false, "message" => "Banner video not found.");
    }
} else {
    $response = array("success" => false, "message" => "No banner available.");
}

echo json_encode($response);

$conn->close();
?>
