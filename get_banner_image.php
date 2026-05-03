<?php
header("Content-Type: application/json");

include "config.php"; //


$sql = "SELECT image_url FROM banners LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $image_url = $row['image_url'];
    if (!empty($image_url)) {
        $response = array("success" => true, "image_url" => $image_url);
    } else {
        $response = array("success" => false, "message" => "Banner image not found.");
    }
} else {
    $response = array("success" => false, "message" => "No banner available.");
}

echo json_encode($response);

$conn->close();
?>
