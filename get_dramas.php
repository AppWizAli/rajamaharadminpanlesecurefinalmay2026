<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
include "auth_token_check.php"; 
include "config.php";

// Fetch dramas
$sql = "SELECT id, name, thumbnail FROM drama";
$result = $conn->query($sql);

$dramas = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $dramas[] = $row;
    }
} else {
    $dramas = array("message" => "No dramas found.");
}

echo json_encode($dramas);

$conn->close();
?>
