<?php
// Include your database connection file (config.php or similar)

session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page or any other page you prefer
    header("Location: login.php");
    exit;
}
// Retrieve user ID from query string parameter
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    echo "Invalid user ID.";
    exit;
}
// Delete user record from database
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    header("Location: usersrecords.php");
} else {
    echo "Error deleting user: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
