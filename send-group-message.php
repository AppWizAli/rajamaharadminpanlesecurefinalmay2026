<?php
include "config.php"; 
$response = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'], $_POST['message'])) {
    $group_id = intval($_POST['group_id']);
    $message = trim($_POST['message']);

    if ($group_id > 0 && !empty($message)) {
        $status = 'unread';

        // Fetch all user IDs from the group_members table for the given group_id
        $stmt = $conn->prepare("SELECT user_id FROM group_members WHERE group_id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_ids = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($user_ids)) {
            // Start a transaction
            $conn->begin_transaction();

            try {
                // Delete existing messages for all users in the group
                $delete_stmt = $conn->prepare("DELETE FROM user_message WHERE user_id IN (SELECT user_id FROM group_members WHERE group_id = ?)");
                $delete_stmt->bind_param("i", $group_id);
                $delete_stmt->execute();
                $delete_stmt->close();

                // Insert the message for each user
                $insert_stmt = $conn->prepare("INSERT INTO user_message (user_id, message, status) VALUES (?, ?, ?)");

                foreach ($user_ids as $user) {
                    $user_id = $user['user_id'];
                    $insert_stmt->bind_param("iss", $user_id, $message, $status);
                    $insert_stmt->execute();
                }

                $insert_stmt->close();

                // Commit the transaction
                $conn->commit();
                $response = 'Message sent to all users in the group successfully!';
            } catch (Exception $e) {
                // Rollback the transaction in case of an error
                $conn->rollback();
                $response = 'Error sending messages: ' . $e->getMessage();
            }
        } else {
            $response = 'No users found in the specified group.';
        }
    } else {
        $response = 'Invalid input or missing data.';
    }
} else {
    $response = 'Invalid request method.';
}

$conn->close();

echo json_encode($response);
?>
