<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_SESSION['admin_id'];
    $title = trim($_POST['title'] ?? "");
    $message = trim($_POST['message'] ?? "");
    $image = $_FILES['image']['name'] ?? "";
    $imagePath = "";

    // Handle image upload
    if (!empty($image)) {
        $targetDir = "Uploads/notifications/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true); // Changed permissions to 0755 for better security
        }
        $imagePath = $targetDir . time() . "_" . basename($image);
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath)) {
            $_SESSION['error'] = "❌ Error uploading image.";
            header("Location: add_notification.php");
            exit;
        }
    }

    // Insert into DB
    $sql = "INSERT INTO notifications (admin_id, title, message, image, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $admin_id, $title, $message, $imagePath);
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Notification added successfully.";
    } else {
        $_SESSION['error'] = "❌ Error adding notification: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
    header("Location: show_notifaction.php");
    exit;
}
?>