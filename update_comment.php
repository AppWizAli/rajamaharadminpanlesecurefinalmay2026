<?php
include "config.php"; // Include database connection
$response = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['group_id'], $_POST['comment'])) {
    $user_id = intval($_POST['user_id']);
    $group_id = intval($_POST['group_id']);
    $comment = trim($_POST['comment']);

    if ($user_id > 0 && $group_id > 0 && !empty($comment)) {

        $stmt = $conn->prepare("UPDATE group_members SET comment = ? WHERE user_id = ?");
        $stmt->bind_param("si", $comment, $user_id);


        if ($stmt->execute()) {
     
            $response = 'Comment updated successfully!';
        } else {
            $response = 'Error updating comment: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response = 'Invalid input or missing data.';
    }
} else {
    $response = 'Invalid request method.';
}

$conn->close();

// Send the response in JSON format
echo json_encode($response);
?>
