<?php
include "config.php";
header("Access-Control-Allow-Origin: *");
// Fetch the most recent notification
$sql = "SELECT id, title, message, image, created_at FROM notifications ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);
$notification = null;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $notification = [
        'id' => $row['id'],
        'title' => $row['title'],
        'message' => $row['message'],
        'image' => $row['image'],
        'created_at' => $row['created_at']
    ];
}
// Close the database connection
$conn->close();
// Return the notification as JSON
header('Content-Type: application/json');
echo json_encode($notification ?: []);
?>