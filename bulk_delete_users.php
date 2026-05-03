<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data from request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['users']) || !is_array($data['users']) || empty($data['users'])) {
    echo json_encode(['status' => 'error', 'message' => 'No users selected for deletion']);
    exit;
}

$users = $data['users'];
$deleted_count = 0;
$failed_count = 0;
$errors = [];

// Start transaction
$conn->begin_transaction();

try {
    // Prepare delete statement
    $delete_stmt = $conn->prepare("DELETE FROM group_members WHERE user_id = ? AND group_id = ?");
    
    if (!$delete_stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    // Loop through each user and delete
    foreach ($users as $user) {
        $user_id = intval($user['user_id']);
        $group_id = intval($user['group_id']);
        
        if ($user_id <= 0 || $group_id <= 0) {
            $failed_count++;
            $errors[] = "Invalid user_id or group_id";
            continue;
        }
        
        $delete_stmt->bind_param("ii", $user_id, $group_id);
        
        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                $deleted_count++;
            } else {
                $failed_count++;
                $errors[] = "User ID $user_id not found in group $group_id";
            }
        } else {
            $failed_count++;
            $errors[] = "Failed to delete user ID $user_id: " . $delete_stmt->error;
        }
    }
    
    $delete_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Prepare response message
    if ($deleted_count > 0 && $failed_count == 0) {
        $message = "Successfully deleted $deleted_count user(s) from the group.";
        $status = 'success';
    } elseif ($deleted_count > 0 && $failed_count > 0) {
        $message = "Deleted $deleted_count user(s), but $failed_count failed. Errors: " . implode(', ', $errors);
        $status = 'partial';
    } else {
        $message = "Failed to delete users. Errors: " . implode(', ', $errors);
        $status = 'error';
    }
    
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'deleted' => $deleted_count,
        'failed' => $failed_count,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'deleted' => 0,
        'failed' => count($users)
    ]);
}

$conn->close();
?>