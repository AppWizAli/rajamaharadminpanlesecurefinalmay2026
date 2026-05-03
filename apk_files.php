<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "config.php";


header("Content-Type: application/json");

if ($conn->connect_error) {
    echo json_encode(array("error" => "Database connection failed: " . $conn->connect_error));
    exit;
}


$sql = "SELECT * FROM apk_files";
$result = $conn->query($sql);


if (!$result) {
    echo json_encode(array("error" => "SQL error: " . $conn->error));
    exit;
}


$response = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
} else {
    $response = array("message" => "No APK files found.");
}

echo json_encode($response);
$conn->close();
?>
