<?php
include "config.php"; 
$response = '';

// Check and update subscriptions if the end date matches the current date
$current_date = date('Y-m-d');
$update_sql = "UPDATE group_members SET subscription = 1 WHERE end_date = ? AND subscription = 0";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("s", $current_date);
$update_stmt->execute();
$update_stmt->close();

// Get users whose subscription is now 1 (expired)
$expired_users_sql = "SELECT user_id FROM group_members WHERE subscription = 1";
$expired_users_result = $conn->query($expired_users_sql);

if ($expired_users_result->num_rows > 0) {
    while ($row = $expired_users_result->fetch_assoc()) {
        $expired_user_id = $row['user_id'];
        $warning_message = "Your subscription has expired. Pay your monthly fee to see more videos.";
        $status = 'unread';

        // Delete any existing message for the expired user
        $delete_stmt = $conn->prepare("DELETE FROM user_message WHERE user_id = ?");
        $delete_stmt->bind_param("i", $expired_user_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Insert the warning message for the expired user
        $stmt = $conn->prepare("INSERT INTO user_message (user_id, message, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $expired_user_id, $warning_message, $status);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['message'])) {
    $user_id = intval($_POST['user_id']);
    $message = trim($_POST['message']);

    if ($user_id > 0 && !empty($message)) {
        $status = 'unread';

        // Delete any existing message for the given user_id
        $delete_stmt = $conn->prepare("DELETE FROM user_message WHERE user_id = ?");
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Insert the new message
        $stmt = $conn->prepare("INSERT INTO user_message (user_id, message, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $message, $status);

        if ($stmt->execute()) {
            $response = 'Message sent successfully!';
        } else {
            $response = 'Error sending message: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response = 'Invalid input or missing data.';
    }
} else {
    $response = 'Invalid request method.';
}

$conn->close();

echo json_encode($response);
