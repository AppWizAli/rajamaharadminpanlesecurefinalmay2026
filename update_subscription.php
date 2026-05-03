<?php
include "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $group_id = intval($_POST['group_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Basic validation
    if (!$user_id || !$group_id || !$start_date || !$end_date) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }

    // Update query
    $stmt = $conn->prepare("UPDATE group_members SET start_date = ?, end_date = ? WHERE user_id = ? AND group_id = ?");
    if ($stmt) {
        $stmt->bind_param("ssii", $start_date, $end_date, $user_id, $group_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database preparation failed']);
    }

    $conn->close();
}
?>
