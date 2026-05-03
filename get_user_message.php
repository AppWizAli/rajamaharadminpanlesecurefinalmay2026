<?php
include "config.php"; 
$response = '';

// Get the raw POST data (JSON)
$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($data['user_id'], $data['status'])) {
        // Update message status
        $user_id = intval($data['user_id']);
        $status = trim($data['status']);

        if ($user_id > 0 && $status == 1) {
            // Update messages' status to 'read' for this user
            $update_stmt = $conn->prepare("UPDATE user_message SET status = 'read' WHERE user_id = ? AND status = 'unread'");
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {
                $response = 'Message status updated successfully to read!';
            } else {
                $response = 'No unread messages found for this user to update.';
            }
            $update_stmt->close();
        } else {
            $response = 'Invalid user_id or status.';
        }
    } elseif (isset($data['user_id'])) {
        // Fetching messages
        $user_id = intval($data['user_id']);

        if ($user_id > 0) {
            // Retrieve all messages for the user
            $stmt = $conn->prepare("SELECT * FROM user_message WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }

            $response = empty($messages) ? 'No messages found.' : $messages;
            $stmt->close();
        } else {
            $response = 'Invalid user_id.';
        }
    } else {
        $response = 'Invalid request data.';
    }
} else {
    $response = 'Invalid request method.';
}

$conn->close();

// Send the response as JSON
echo json_encode($response);
?>
