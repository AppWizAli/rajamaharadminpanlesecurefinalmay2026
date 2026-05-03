<?php
include "config.php";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set response type
header("Content-Type: text/plain");

// Check request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST inputs
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

    // Validate input
    if (empty($email) || empty($new_password)) {
        echo "Email and new password are required.";
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo "Email address not found.";
        $stmt->close();
        exit;
    }

    // Get user ID
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $hashed_password, $user_id);

    if ($update->execute()) {
        echo "Password updated successfully.";
    } else {
        echo "Failed to update password.";
    }

    $update->close();
} else {
    echo "Invalid request method.";
}

$conn->close();
?>
