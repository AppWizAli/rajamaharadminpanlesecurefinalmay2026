<?php
// Include your database connection file (config.php or similar)
include "config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch the current admin's type
$current_admin_id = $_SESSION['admin_id'];
$sql = "SELECT admin_type FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $current_admin = $result->fetch_assoc();
    $current_admin_type = $current_admin['admin_type'];
} else {
    echo "Current admin not found.";
    exit;
}

$stmt->close();

// Fetch admin details based on GET parameter
$admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    echo "Admin to delete not found.";
    exit;
}

$stmt->close();

// Check if the current admin is allowed to delete the target admin
if ($current_admin_type !== 'owner' && $current_admin_id != $admin_id) {
    echo "Unauthorized action.";
    exit;
}

// Process delete operation
$sql = "DELETE FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);

if ($stmt->execute()) {
    // Redirect to admin list page or success page
    header("Location: admin_records.php");
    exit;
} else {
    echo "Error deleting admin: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
