<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include "config.php";



$sql = "SELECT * FROM users";
$result = $conn->query($sql);

$users = array();
if ($result->num_rows > 0) {
  
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    $users = array("message" => "No users found.");
}

echo json_encode($users);

$conn->close();
?>
