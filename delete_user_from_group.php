<?php
include "config.php"; // Database connection

$response = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['group_id'], $_POST['user_id'])) {
    $group_id = intval($_POST['group_id']);
    $user_id = intval($_POST['user_id']);

    if ($group_id > 0 && $user_id > 0) {

        $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);

        if ($stmt->execute()) {
           
            $response = 'User removed successfully!';
        } else {
            $response = 'Error removing user from the group: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response = 'Invalid group ID or user ID.';
    }
}

$conn->close();

echo json_encode($response);
?>
