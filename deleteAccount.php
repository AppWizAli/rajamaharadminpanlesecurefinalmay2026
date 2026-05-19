<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
include "config.php"; // must contain $conn
include "auth_token_check.php";

// Get user_id from JSON, POST or GET
$data = json_decode(file_get_contents("php://input"), true);
$requestedUserId = 0;
if (isset($data['user_id'])) {
    $requestedUserId = intval($data['user_id']);
} elseif (isset($_POST['user_id'])) {
    $requestedUserId = intval($_POST['user_id']);
} elseif (isset($_GET['user_id'])) {
    $requestedUserId = intval($_GET['user_id']);
}
$user_id = enforce_authenticated_user_match($requestedUserId);

// Validate DB connection
if (!$conn || $conn->connect_error) {
    echo json_encode(false);
    exit();
}

// Step 1: Delete related rows from group_members
$delMembers = $conn->prepare("DELETE FROM group_members WHERE user_id = ?");
if ($delMembers) {
    $delMembers->bind_param("i", $user_id);
    $delMembers->execute();
}

// Step 2: Delete user from users table
$deleteQuery = $conn->prepare("DELETE FROM users WHERE id = ?");
if (!$deleteQuery) {
    echo json_encode(false);
    exit();
}
$deleteQuery->bind_param("i", $user_id);

if ($deleteQuery->execute()) {
    echo json_encode(true);
} else {
    echo json_encode(false);
}

$conn->close();
