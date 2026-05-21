<?php
session_start();
include "config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$users = $data['users'] ?? [];
$targetGroupId = intval($data['target_group_id'] ?? 0);

if (!is_array($users) || empty($users)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No users selected for moving'
    ]);
    exit;
}

if ($targetGroupId <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a valid target group'
    ]);
    exit;
}

$groupStmt = $conn->prepare("SELECT id FROM `groups` WHERE id = ? LIMIT 1");
if (!$groupStmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to validate target group'
    ]);
    exit;
}

$groupStmt->bind_param("i", $targetGroupId);
$groupStmt->execute();
$groupExists = $groupStmt->get_result()->num_rows > 0;
$groupStmt->close();

if (!$groupExists) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Selected target group was not found'
    ]);
    exit;
}

$movedCount = 0;
$failedCount = 0;
$errors = [];

$conn->begin_transaction();

try {
    $sourceMembershipStmt = $conn->prepare("
        SELECT id
        FROM group_members
        WHERE user_id = ? AND group_id = ?
        LIMIT 1
    ");
    $checkTargetStmt = $conn->prepare("
        SELECT id
        FROM group_members
        WHERE user_id = ? AND group_id = ?
        LIMIT 1
    ");
    $insertTargetStmt = $conn->prepare("
        INSERT INTO group_members (
            group_id,
            user_id,
            comment,
            subscription,
            start_date,
            end_date,
            created_at,
            updated_at
        )
        SELECT
            ?,
            user_id,
            comment,
            subscription,
            start_date,
            end_date,
            created_at,
            updated_at
        FROM group_members
        WHERE id = ?
    ");
    $deleteSourceStmt = $conn->prepare("
        DELETE FROM group_members
        WHERE id = ?
    ");

    if (!$sourceMembershipStmt || !$checkTargetStmt || !$insertTargetStmt || !$deleteSourceStmt) {
        throw new Exception('Failed to prepare bulk move statements');
    }

    foreach ($users as $user) {
        $userId = intval($user['user_id'] ?? 0);
        $sourceGroupId = intval($user['group_id'] ?? 0);

        if ($userId <= 0 || $sourceGroupId <= 0) {
            $failedCount++;
            $errors[] = 'Invalid user or source group in selection';
            continue;
        }

        if ($sourceGroupId === $targetGroupId) {
            $failedCount++;
            $errors[] = "User ID {$userId} is already in the selected group";
            continue;
        }

        $checkTargetStmt->bind_param("ii", $userId, $targetGroupId);
        $checkTargetStmt->execute();
        if ($checkTargetStmt->get_result()->num_rows > 0) {
            $failedCount++;
            $errors[] = "User ID {$userId} already exists in target group";
            continue;
        }

        $sourceMembershipStmt->bind_param("ii", $userId, $sourceGroupId);
        $sourceMembershipStmt->execute();
        $sourceMembership = $sourceMembershipStmt->get_result()->fetch_assoc();

        if (!$sourceMembership) {
            $failedCount++;
            $errors[] = "User ID {$userId} source membership was not found";
            continue;
        }

        $sourceMembershipId = intval($sourceMembership['id'] ?? 0);
        $insertTargetStmt->bind_param("ii", $targetGroupId, $sourceMembershipId);

        if (!$insertTargetStmt->execute() || $insertTargetStmt->affected_rows <= 0) {
            $failedCount++;
            $errors[] = "User ID {$userId} could not be copied to target group";
            continue;
        }

        $deleteSourceStmt->bind_param("i", $sourceMembershipId);
        if ($sourceMembershipId > 0 && $deleteSourceStmt->execute() && $deleteSourceStmt->affected_rows > 0) {
            $movedCount++;
        } else {
            throw new Exception("User ID {$userId} was copied but old membership could not be removed");
        }
    }

    $sourceMembershipStmt->close();
    $checkTargetStmt->close();
    $insertTargetStmt->close();
    $deleteSourceStmt->close();

    $conn->commit();

    if ($movedCount > 0 && $failedCount === 0) {
        $status = 'success';
        $message = "Successfully moved {$movedCount} user(s) to the new group.";
    } elseif ($movedCount > 0) {
        $status = 'partial';
        $message = "Moved {$movedCount} user(s), but {$failedCount} failed.";
    } else {
        $status = 'error';
        $message = 'No selected users were moved.';
    }

    echo json_encode([
        'status' => $status,
        'message' => $message,
        'moved' => $movedCount,
        'failed' => $failedCount,
        'errors' => $errors
    ]);
} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'moved' => 0,
        'failed' => count($users)
    ]);
}

$conn->close();
?>
