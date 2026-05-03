<?php
include "config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if group_id is provided via POST
if (!isset($_POST['id']) || !is_numeric($_POST['id']) || intval($_POST['id']) <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
    exit;
}

$group_id = intval($_POST['id']);

// Begin a transaction
$conn->begin_transaction();

try {
    // Delete related records in group_members table
    $delete_members_sql = "DELETE FROM group_members WHERE group_id = ?";
    $delete_members_stmt = $conn->prepare($delete_members_sql);
    if ($delete_members_stmt === false) {
        throw new Exception("Error preparing group_members SQL: " . $conn->error);
    }
    $delete_members_stmt->bind_param("i", $group_id);
    if (!$delete_members_stmt->execute()) {
        throw new Exception("Error executing group_members deletion: " . $delete_members_stmt->error);
    }
    $delete_members_stmt->close();

    // Delete related records in group_videos table
    $delete_videos_sql = "DELETE FROM group_videos WHERE group_id = ?";
    $delete_videos_stmt = $conn->prepare($delete_videos_sql);
    if ($delete_videos_stmt === false) {
        throw new Exception("Error preparing group_videos SQL: " . $conn->error);
    }
    $delete_videos_stmt->bind_param("i", $group_id);
    if (!$delete_videos_stmt->execute()) {
        throw new Exception("Error executing group_videos deletion: " . $delete_videos_stmt->error);
    }
    $delete_videos_stmt->close();

    // Delete the group
    $delete_group_sql = "DELETE FROM `groups` WHERE id = ?";
    $delete_group_stmt = $conn->prepare($delete_group_sql);
    if ($delete_group_stmt === false) {
        throw new Exception("Error preparing groups SQL: " . $conn->error);
    }
    $delete_group_stmt->bind_param("i", $group_id);
    if (!$delete_group_stmt->execute()) {
        throw new Exception("Error executing group deletion: " . $delete_group_stmt->error);
    }
    $delete_group_stmt->close();

    // Commit the transaction
    $conn->commit();

    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Group deleted successfully']);
} catch (Exception $e) {
    // Rollback the transaction if something fails
    $conn->rollback();
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error deleting group: ' . $e->getMessage()]);
}

$conn->close();
?>