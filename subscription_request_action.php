<?php
session_start();
include "config.php";
include "subscription_schema.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

ensure_subscription_tables($conn);

function subscription_flash($type, $message) {
    $_SESSION['subscription_flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function subscription_redirect($fallback = "subscription_requests.php") {
    $redirect = $fallback;
    if (!empty($_POST['return_url']) && strpos($_POST['return_url'], "\n") === false && strpos($_POST['return_url'], "\r") === false) {
        $returnUrl = $_POST['return_url'];
        if (preg_match('/^subscription_[a-z_]+\.php(\?.*)?$/', $returnUrl)) {
            $redirect = $returnUrl;
        }
    }
    header("Location: " . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    subscription_flash('danger', 'Invalid request.');
    subscription_redirect();
}

$action = trim($_POST['action'] ?? '');
$requestId = intval($_POST['request_id'] ?? 0);
$adminNote = trim($_POST['admin_note'] ?? '');
$adminId = intval($_SESSION['admin_id']);

if ($requestId <= 0) {
    subscription_flash('danger', 'Invalid subscription request.');
    subscription_redirect();
}

if ($action === 'reject') {
    $stmt = $conn->prepare("
        UPDATE subscription_requests
        SET status = 'rejected',
            admin_note = ?,
            rejected_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("si", $adminNote, $requestId);
    $stmt->execute();
    $updated = $stmt->affected_rows;
    $stmt->close();

    subscription_flash($updated > 0 ? 'success' : 'warning', $updated > 0 ? 'Subscription request rejected.' : 'Request was already processed.');
    subscription_redirect();
}

if ($action !== 'approve') {
    subscription_flash('danger', 'Unknown action.');
    subscription_redirect();
}

$settings = get_subscription_settings($conn);
$selectedGroupId = intval($_POST['group_id'] ?? 0);
if ($selectedGroupId <= 0) {
    $selectedGroupId = intval($settings['default_group_id'] ?? 0);
}

if ($selectedGroupId <= 0) {
    subscription_flash('danger', 'Please select a subscription group before approving.');
    subscription_redirect();
}

$groupCheckStmt = $conn->prepare("SELECT id FROM `groups` WHERE id = ? LIMIT 1");
$groupCheckStmt->bind_param("i", $selectedGroupId);
$groupCheckStmt->execute();
$groupExists = $groupCheckStmt->get_result()->num_rows > 0;
$groupCheckStmt->close();

if (!$groupExists) {
    subscription_flash('danger', 'Selected group was not found.');
    subscription_redirect();
}

$conn->begin_transaction();

try {
    $requestStmt = $conn->prepare("
        SELECT id, user_id, amount, currency, note, status
        FROM subscription_requests
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $requestStmt->bind_param("i", $requestId);
    $requestStmt->execute();
    $request = $requestStmt->get_result()->fetch_assoc();
    $requestStmt->close();

    if (!$request || $request['status'] !== 'pending') {
        throw new Exception('Request was already processed.');
    }

    $userId = intval($request['user_id']);
    $today = new DateTime('today');
    $subscriptionStart = clone $today;

    $memberStmt = $conn->prepare("
        SELECT start_date, end_date
        FROM group_members
        WHERE user_id = ? AND group_id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $memberStmt->bind_param("ii", $userId, $selectedGroupId);
    $memberStmt->execute();
    $member = $memberStmt->get_result()->fetch_assoc();
    $memberStmt->close();

    if ($member && !empty($member['end_date'])) {
        $existingEnd = new DateTime($member['end_date']);
        if ($existingEnd >= $today) {
            $subscriptionStart = $existingEnd;
        }
    }

    $subscriptionEnd = clone $subscriptionStart;
    $subscriptionEnd->modify('+31 days');

    if ($member) {
        $newEndDate = $subscriptionEnd->format('Y-m-d');
        $shouldResetStartDate = empty($member['start_date']);
        if (!empty($member['end_date'])) {
            $currentEndDate = new DateTime($member['end_date']);
            if ($currentEndDate < $today) {
                $shouldResetStartDate = true;
            }
        } else {
            $shouldResetStartDate = true;
        }

        if ($shouldResetStartDate) {
            $newStartDate = $today->format('Y-m-d');
            $updateMemberStmt = $conn->prepare("
                UPDATE group_members
                SET start_date = ?, end_date = ?, subscription = 0, updated_at = NOW()
                WHERE user_id = ? AND group_id = ?
            ");
            $updateMemberStmt->bind_param("ssii", $newStartDate, $newEndDate, $userId, $selectedGroupId);
        } else {
            $updateMemberStmt = $conn->prepare("
                UPDATE group_members
                SET end_date = ?, subscription = 0, updated_at = NOW()
                WHERE user_id = ? AND group_id = ?
            ");
            $updateMemberStmt->bind_param("sii", $newEndDate, $userId, $selectedGroupId);
        }
        $updateMemberStmt->execute();
        $updateMemberStmt->close();
    } else {
        $comment = "Subscription approved from admin panel";
        $subscriptionFlag = 0;
        $startDate = $today->format('Y-m-d');
        $endDate = $subscriptionEnd->format('Y-m-d');
        $createdAt = date('Y-m-d H:i:s');
        $insertMemberStmt = $conn->prepare("
            INSERT INTO group_members (group_id, user_id, comment, subscription, start_date, end_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertMemberStmt->bind_param("iisisss", $selectedGroupId, $userId, $comment, $subscriptionFlag, $startDate, $endDate, $createdAt);
        $insertMemberStmt->execute();
        $insertMemberStmt->close();
    }

    $invoiceNo = "UB-INV-" . date('Ymd') . "-" . $requestId;
    $monthsAdded = 1;
    $subscriptionStartValue = $subscriptionStart->format('Y-m-d');
    $subscriptionEndValue = $subscriptionEnd->format('Y-m-d');

    $approveStmt = $conn->prepare("
        UPDATE subscription_requests
        SET status = 'approved',
            group_id = ?,
            admin_note = ?,
            invoice_no = ?,
            months_added = ?,
            subscription_start_date = ?,
            subscription_end_date = ?,
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");
    $approveStmt->bind_param(
        "ississii",
        $selectedGroupId,
        $adminNote,
        $invoiceNo,
        $monthsAdded,
        $subscriptionStartValue,
        $subscriptionEndValue,
        $adminId,
        $requestId
    );
    $approveStmt->execute();
    if ($approveStmt->affected_rows <= 0) {
        $approveStmt->close();
        throw new Exception('Request was already processed.');
    }
    $approveStmt->close();

    $conn->commit();
    subscription_flash('success', 'Subscription approved and one month added successfully.');
} catch (Exception $e) {
    $conn->rollback();
    subscription_flash('danger', $e->getMessage());
}

$conn->close();
subscription_redirect();
?>
