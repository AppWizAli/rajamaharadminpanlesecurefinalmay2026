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
    $admin_name = isset($_POST['admin_name']) ? $_POST['admin_name'] : '';
    $admin_email = isset($_POST['admin_email']) ? $_POST['admin_email'] : '';
    $admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
    $admin_type = isset($_POST['admin_type']) ? $_POST['admin_type'] : '';

    // Perform validation if needed

    // Hash the password
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

    // Prepare SQL statement to insert into admin table
    $sql = "INSERT INTO admin (admin_name, admin_email, admin_password, admin_type) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $admin_name, $admin_email, $hashed_password, $admin_type);

    // Execute the statement
    if ($stmt->execute()) {
        header("Location: admin_records.php");
    } else {
        echo "Error adding admin: " . $stmt->error;
    }

    // Close statement and database connection
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
