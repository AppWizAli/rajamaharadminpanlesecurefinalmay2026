<?php
include "config.php";

// Delete notification
$id = $_GET['id'];
$sql = "DELETE FROM notifications WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: show_notifaction.php");
    exit;
} else {
    echo "Error deleting notification: " . $stmt->error;
}
?>
