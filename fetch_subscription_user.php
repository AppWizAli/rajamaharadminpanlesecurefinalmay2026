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

$user_id = enforce_authenticated_user_match(intval($_GET['user_id']));
$today = date('Y-m-d');

$status = true;
$message = "Both group and single-user subscriptions are expired.";

$stmt = $conn->prepare("
    SELECT MAX(end_date) AS end_date
    FROM (
        SELECT end_date FROM group_members WHERE user_id = ? AND end_date >= ?
        UNION ALL
        SELECT end_date FROM user_videos WHERE user_id = ? AND end_date >= ?
    ) active_subscriptions
");
$stmt->bind_param("isis", $user_id, $today, $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$active_end_date = $row['end_date'] ?? null;
$stmt->close();

if ($active_end_date) {
    $days_left = (int)((strtotime($active_end_date) - strtotime($today)) / (60 * 60 * 24));
    $status = false;
    $message = "Subscription is active.";

    if ($days_left === 2) {
        $message = "Your subscription ends in two days, but you still have access.";
    } elseif ($days_left === 1) {
        $message = "Your subscription ends in one day, but you still have access.";
    } elseif ($days_left === 0) {
        $message = "Your subscription ends today, but you still have access.";
    }
}

echo json_encode([
    "status" => $status,
    "message" => $message
]);
