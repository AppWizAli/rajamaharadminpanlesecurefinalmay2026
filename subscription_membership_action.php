<?php
session_start();
include "config.php";

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}

$action = trim($_POST['action'] ?? '');
$userId = intval($_POST['user_id'] ?? 0);
$groupId = intval($_POST['group_id'] ?? 0);

if ($userId <= 0 || $groupId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user or group.'
    ]);
    exit;
}

$today = new DateTime('today');
$yesterday = (clone $today)->modify('-1 day');

if ($action === 'increase_membership') {
    $stmt = $conn->prepare("
        SELECT start_date, end_date
        FROM group_members
        WHERE user_id = ? AND group_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $userId, $groupId);
    $stmt->execute();
    $membership = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$membership) {
        echo json_encode([
            'success' => false,
            'message' => 'Subscription row not found.'
        ]);
        exit;
    }

    $baseDate = clone $today;
    if (!empty($membership['end_date'])) {
        $currentEndDate = new DateTime($membership['end_date']);
        if ($currentEndDate > $today) {
            $baseDate = $currentEndDate;
        }
    }
    $newEndDate = $baseDate->modify('+31 days')->format('Y-m-d');
    $newStartDate = !empty($membership['start_date']) ? $membership['start_date'] : $today->format('Y-m-d');

    $updateStmt = $conn->prepare("
        UPDATE group_members
        SET start_date = ?, end_date = ?, subscription = 0, updated_at = NOW()
        WHERE user_id = ? AND group_id = ?
    ");
    $updateStmt->bind_param("ssii", $newStartDate, $newEndDate, $userId, $groupId);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Subscription date increased by 31 days.'
    ]);
    exit;
}

if ($action !== 'save_membership') {
    echo json_encode([
        'success' => false,
        'message' => 'Unknown action.'
    ]);
    exit;
}

$status = trim($_POST['membership_status'] ?? 'active');
$startDateRaw = trim($_POST['start_date'] ?? '');
$endDateRaw = trim($_POST['end_date'] ?? '');

$currentStmt = $conn->prepare("
    SELECT start_date, end_date
    FROM group_members
    WHERE user_id = ? AND group_id = ?
    LIMIT 1
");
$currentStmt->bind_param("ii", $userId, $groupId);
$currentStmt->execute();
$membership = $currentStmt->get_result()->fetch_assoc();
$currentStmt->close();

if (!$membership) {
    echo json_encode([
        'success' => false,
        'message' => 'Subscription row not found.'
    ]);
    exit;
}

$resolvedStartDate = $startDateRaw !== '' ? DateTime::createFromFormat('Y-m-d', $startDateRaw) : null;
$resolvedEndDate = $endDateRaw !== '' ? DateTime::createFromFormat('Y-m-d', $endDateRaw) : null;

if ($startDateRaw !== '' && (!$resolvedStartDate || $resolvedStartDate->format('Y-m-d') !== $startDateRaw)) {
    echo json_encode([
        'success' => false,
        'message' => 'Start date is invalid.'
    ]);
    exit;
}

if ($endDateRaw !== '' && (!$resolvedEndDate || $resolvedEndDate->format('Y-m-d') !== $endDateRaw)) {
    echo json_encode([
        'success' => false,
        'message' => 'End date is invalid.'
    ]);
    exit;
}

if (!$resolvedStartDate) {
    if (!empty($membership['start_date'])) {
        $resolvedStartDate = new DateTime($membership['start_date']);
    } else {
        $resolvedStartDate = clone $today;
    }
}

if ($status === 'active') {
    if (!$resolvedEndDate) {
        echo json_encode([
            'success' => false,
            'message' => 'Please choose an end date for an active subscription.'
        ]);
        exit;
    }
    if ($resolvedEndDate < $today) {
        echo json_encode([
            'success' => false,
            'message' => 'Active subscription end date must be today or later.'
        ]);
        exit;
    }
    if ($resolvedStartDate > $resolvedEndDate) {
        echo json_encode([
            'success' => false,
            'message' => 'Start date must be before end date.'
        ]);
        exit;
    }
    $subscriptionFlag = 0;
} else {
    if (!$resolvedEndDate || $resolvedEndDate >= $today) {
        $resolvedEndDate = clone $yesterday;
    }
    if ($resolvedStartDate > $resolvedEndDate) {
        $resolvedStartDate = clone $resolvedEndDate;
    }
    $subscriptionFlag = 1;
}

$startDateValue = $resolvedStartDate->format('Y-m-d');
$endDateValue = $resolvedEndDate->format('Y-m-d');

$updateStmt = $conn->prepare("
    UPDATE group_members
    SET start_date = ?, end_date = ?, subscription = ?, updated_at = NOW()
    WHERE user_id = ? AND group_id = ?
");
$updateStmt->bind_param("ssiii", $startDateValue, $endDateValue, $subscriptionFlag, $userId, $groupId);
$updateStmt->execute();
$updateStmt->close();

echo json_encode([
    'success' => true,
    'message' => $status === 'active'
        ? 'Subscription row updated and activated.'
        : 'Subscription row updated and marked inactive.'
]);
?>
