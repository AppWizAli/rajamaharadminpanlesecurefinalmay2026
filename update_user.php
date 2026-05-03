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

// Retrieve form data using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null; // Hash the password if provided
    $profile_image = $_FILES['profile_image']['name']; // Get profile image name

    // Check if a new profile image is uploaded
    if (!empty($profile_image)) {
        // Upload new profile image to server
        $target_dir = "users/";
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            // Update user data including profile image
            $sql = "UPDATE users SET username = ?, email = ?, password = ?, profile_image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $username, $email, $password, $profile_image, $user_id);
        } else {
            echo "Error uploading file.";
            exit;
        }
    } else {
        // Update user data without changing profile image
        $sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $email, $password, $user_id);
    }

    if ($stmt->execute()) {
        header("Location: usersrecords.php");
    } else {
        echo "Error updating user: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
