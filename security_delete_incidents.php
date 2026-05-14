<?php
session_start();
include "config.php";
include "security_schema.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

ensure_security_tables($conn);

function security_delete_redirect($fallback = "security_incidents.php") {
    $redirect = $fallback;
    if (!empty($_POST['return_url']) && strpos($_POST['return_url'], "\n") === false && strpos($_POST['return_url'], "\r") === false) {
        $returnUrl = $_POST['return_url'];
        if (preg_match('/^security_[a-z_]+\.php(\?.*)?$/', $returnUrl)) {
            $redirect = $returnUrl;
        }
    }
    header("Location: " . $redirect);
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['security_flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Invalid delete request.');
    security_delete_redirect();
}

$mode = trim($_POST['mode'] ?? '');
$deleted = 0;

if ($mode === 'incident') {
    $selected = $_POST['incident_ids'] ?? [];
    if (!is_array($selected)) {
        $selected = [];
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $selected), function ($value) {
        return $value > 0;
    })));

    if (empty($ids)) {
        set_flash_message('warning', 'Please select at least one issue to delete.');
        security_delete_redirect();
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM security_incidents WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    set_flash_message('success', $deleted . ' issue(s) deleted successfully.');
    security_delete_redirect();
}

if ($mode === 'group') {
    $selected = $_POST['group_keys'] ?? [];
    if (!is_array($selected)) {
        $selected = [];
    }

    $groupClauses = [];
    $params = [];
    $types = '';

    foreach ($selected as $encoded) {
        $json = base64_decode((string)$encoded, true);
        if ($json === false) {
            continue;
        }
        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            continue;
        }

        $userId = isset($payload['user_id']) ? intval($payload['user_id']) : 0;
        $deviceId = trim((string)($payload['device_id'] ?? 'unknown'));

        $userClause = $userId > 0 ? "user_id = ?" : "(user_id IS NULL OR user_id = 0)";
        if ($userId > 0) {
            $params[] = $userId;
            $types .= 'i';
        }

        if ($deviceId === 'unknown') {
            $deviceClause = "(device_id IS NULL OR device_id = '')";
        } else {
            $deviceClause = "device_id = ?";
            $params[] = $deviceId;
            $types .= 's';
        }

        $groupClauses[] = "($userClause AND $deviceClause)";
    }

    if (empty($groupClauses)) {
        set_flash_message('warning', 'Please select at least one user/device row to delete.');
        security_delete_redirect();
    }

    $sql = "DELETE FROM security_incidents WHERE " . implode(' OR ', $groupClauses);
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    set_flash_message('success', $deleted . ' issue record(s) deleted from the selected user/device row(s).');
    security_delete_redirect();
}

set_flash_message('danger', 'Unknown delete mode.');
security_delete_redirect();
?>
