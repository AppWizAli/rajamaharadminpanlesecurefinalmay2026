<?php
include "config.php";
include "auth_token_check.php";
if (!isset($_GET['user_id'])) {
    echo json_encode([
        "status" => false,
        "message" => "Missing user_id"
    ]);
    exit;
}

$user_id = intval($_GET['user_id']);
$today = date('Y-m-d');

$stmt = $conn->prepare("
    SELECT end_date FROM group_members 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$status = true;
$message = "No active subscription found.";

while ($row = $result->fetch_assoc()) {
    $end_date = $row['end_date'];

    if ($end_date) {
        $days_left = (strtotime($end_date) - strtotime($today)) / (60 * 60 * 24);
        $days_left = (int)$days_left;

        if ($days_left > 2) {
            $status = false;
            $message = "Subscription is active.";
            break; // No need to continue
        }elseif ($days_left === 2) {
            $status = true;
            $message = "Your subscription ends in two days. Please contact us to resubscribe.";
            break;
        } elseif ($days_left === 1) {
            $status = true;
            $message = "Your subscription ends in one day. Please contact us to resubscribe.";
            break;
        } elseif ($days_left === 0) {
            $status = true;
            $message = "Your subscription ends today. Please contact us to resubscribe.";
            break;
        }
    }
}
$stmt->close();

echo json_encode([
    "status" => $status,
    "message" => $message
]);
