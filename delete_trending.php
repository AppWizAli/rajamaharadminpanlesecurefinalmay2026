<?php
include "config.php";
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $delete_sql = "DELETE FROM trending_dramas WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Trending drama deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Error preparing delete: " . $conn->error;
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
