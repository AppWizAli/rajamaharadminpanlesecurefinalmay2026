<?php
include "config.php"; 
$response = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['status'])) {
    $user_id = intval($_POST['user_id']);
    $status = intval($_POST['status']);

    // Ensure valid input
    if ($user_id > 0) {
        // Check if status is 1, update to 'read'
        $new_status = ($status === 1) ? 'read' : 'unread';

        // Update the status in the database
        $stmt = $conn->prepare("UPDATE user_message SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_status, $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response = 'Status updated successfully!';
            } else {
                $response = 'No record found for the provided user_id.';
            }
        } else {
            $response = 'Error updating status: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response = 'Invalid user_id.';
    }
} else {
    $response = 'Invalid request method or missing data.';
}

$conn->close();

echo json_encode($response);
?>
