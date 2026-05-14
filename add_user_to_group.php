<?php
include "config.php";
include "subscription_schema.php";
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

function redirect_back_to_group($groupId, $useDirectGroupsPage) {
    $target = $useDirectGroupsPage ? 'view_dgroups_users.php' : 'view_group_users.php';
    header("Location: {$target}?group_id=" . intval($groupId));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['add_user_to_group'])) {
    header("Location: show_groups.php");
    exit;
}

$groupId = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
$endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime($startDate . ' +31 days'));
$createdAt = date('Y-m-d H:i:s');
$useDirectGroupsPage = array_key_exists('start_date', $_POST);

if ($groupId <= 0 || $userId <= 0) {
    redirect_back_to_group($groupId, $useDirectGroupsPage);
}

$checkStmt = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
if (!$checkStmt) {
    redirect_back_to_group($groupId, $useDirectGroupsPage);
}
$checkStmt->bind_param("ii", $groupId, $userId);
$checkStmt->execute();
$alreadyExists = $checkStmt->get_result()->num_rows > 0;
$checkStmt->close();

if ($alreadyExists) {
    redirect_back_to_group($groupId, $useDirectGroupsPage);
}

if ($comment === '') {
    $commentStmt = $conn->prepare("SELECT comment FROM group_members WHERE user_id = ? AND comment IS NOT NULL AND comment <> '' ORDER BY id DESC LIMIT 1");
    if ($commentStmt) {
        $commentStmt->bind_param("i", $userId);
        $commentStmt->execute();
        $commentRow = $commentStmt->get_result()->fetch_assoc();
        if ($commentRow && !empty($commentRow['comment'])) {
            $comment = $commentRow['comment'];
        }
        $commentStmt->close();
    }
}

$insertStmt = $conn->prepare("
    INSERT INTO group_members (group_id, user_id, comment, subscription, start_date, end_date, created_at)
    VALUES (?, ?, ?, 0, ?, ?, ?)
");
if ($insertStmt) {
    $insertStmt->bind_param("iissss", $groupId, $userId, $comment, $startDate, $endDate, $createdAt);
    $insertStmt->execute();
    $inserted = $insertStmt->affected_rows > 0;
    $insertStmt->close();

    if ($inserted) {
        $settings = get_subscription_settings($conn);
        subscription_create_approved_invoice_record($conn, [
            'user_id' => $userId,
            'group_id' => $groupId,
            'amount' => $settings['monthly_amount'] ?? '0.00',
            'currency' => $settings['currency'] ?? 'PKR',
            'payment_method' => 'Admin Assignment',
            'note' => $comment,
            'admin_note' => $useDirectGroupsPage
                ? 'User added to group manually from direct group users page.'
                : 'User added to group manually from group users page.',
            'details_snapshot' => subscription_build_details_snapshot($settings),
            'subscription_start_date' => $startDate,
            'subscription_end_date' => $endDate,
            'approved_by' => intval($_SESSION['admin_id'] ?? 0)
        ]);
    }
}

$conn->close();
redirect_back_to_group($groupId, $useDirectGroupsPage);
?>
