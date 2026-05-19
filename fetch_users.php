<?php
include "config.php";
include "auth_token_check.php";
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

$userId = require_authenticated_user_id();

$stmt = $conn->prepare("
    SELECT id, username, email, profile_image, created_at, logged_number
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$users = array();
if ($row = $result->fetch_assoc()) {
    $users[] = $row;
} else {
    $users = array("message" => "No users found.");
}

echo json_encode($users);

$stmt->close();
$conn->close();
?>
