<?php
include "config.php";
include "subscription_schema.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$schemaStatus = ensure_subscription_tables($conn);
if (!empty($schemaStatus['message'])) {
    echo json_encode([
        "status" => false,
        "message" => "Subscription setup issue: " . $schemaStatus['message']
    ]);
    exit;
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($userId <= 0) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid user."
    ]);
    exit;
}

$settings = get_subscription_settings($conn);

$subscriptionStmt = $conn->prepare("
    SELECT
        gm.group_id,
        g.group_name,
        gm.start_date,
        gm.end_date,
        CASE WHEN gm.end_date IS NOT NULL AND gm.end_date >= CURDATE() THEN 1 ELSE 0 END AS is_active
    FROM group_members gm
    INNER JOIN `groups` g ON g.id = gm.group_id
    WHERE gm.user_id = ?
    ORDER BY CASE WHEN gm.end_date IS NULL THEN 1 ELSE 0 END, gm.end_date DESC, gm.group_id DESC
");
$subscriptions = [];
if ($subscriptionStmt) {
    $subscriptionStmt->bind_param("i", $userId);
    $subscriptionStmt->execute();
    $subscriptionResult = $subscriptionStmt->get_result();
    while ($row = $subscriptionResult->fetch_assoc()) {
        $subscriptions[] = $row;
    }
    $subscriptionStmt->close();
} else {
    echo json_encode([
        "status" => false,
        "message" => "Unable to load subscriptions: " . $conn->error
    ]);
    exit;
}

$historyStmt = $conn->prepare("
    SELECT
        sr.id,
        sr.group_id,
        g.group_name,
        sr.amount,
        sr.currency,
        sr.payment_method,
        sr.screenshot_url,
        sr.note,
        sr.status,
        sr.admin_note,
        sr.invoice_no,
        sr.months_added,
        sr.subscription_start_date,
        sr.subscription_end_date,
        sr.approved_at,
        sr.rejected_at,
        sr.created_at
    FROM subscription_requests sr
    LEFT JOIN `groups` g ON g.id = sr.group_id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC, sr.id DESC
");
$requests = [];
if ($historyStmt) {
    $historyStmt->bind_param("i", $userId);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    while ($row = $historyResult->fetch_assoc()) {
        $requests[] = $row;
    }
    $historyStmt->close();
} else {
    echo json_encode([
        "status" => false,
        "message" => "Unable to load request history: " . $conn->error
    ]);
    exit;
}
$conn->close();

echo json_encode([
    "status" => true,
    "settings" => [
        "monthly_amount" => $settings['monthly_amount'],
        "currency" => $settings['currency'],
        "default_group_id" => $settings['default_group_id'],
        "default_group_name" => $settings['default_group_name'],
        "jazzcash_number" => $settings['jazzcash_number'],
        "jazzcash_title" => $settings['jazzcash_title'],
        "easypaisa_number" => $settings['easypaisa_number'],
        "easypaisa_title" => $settings['easypaisa_title'],
        "bank_name" => $settings['bank_name'],
        "bank_account_title" => $settings['bank_account_title'],
        "bank_account_number" => $settings['bank_account_number'],
        "bank_iban" => $settings['bank_iban'],
        "payment_instructions" => $settings['payment_instructions']
    ],
    "current_subscriptions" => $subscriptions,
    "requests" => $requests
]);
?>
