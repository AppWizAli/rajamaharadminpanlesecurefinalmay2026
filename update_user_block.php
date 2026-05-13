<?php
session_start();
include "config.php";
include "security_schema.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

ensure_security_tables($conn);

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$is_blocked = isset($_POST['is_blocked']) ? intval($_POST['is_blocked']) : 0;
$message = trim($_POST['message'] ?? '');
$admin_id = intval($_SESSION['admin_id']);

if ($user_id > 0) {
    if ($message === '') {
        $message = "Your app access is currently paused. Please contact Urdu Bolo support.";
    }

    $stmt = $conn->prepare("
        INSERT INTO user_security_blocks (user_id, is_blocked, message, blocked_by, blocked_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            is_blocked = VALUES(is_blocked),
            message = VALUES(message),
            blocked_by = VALUES(blocked_by),
            blocked_at = IF(VALUES(is_blocked) = 1, NOW(), blocked_at)
    ");
    $stmt->bind_param("iisi", $user_id, $is_blocked, $message, $admin_id);
    $stmt->execute();
    $stmt->close();
}

$redirect = "security_incidents.php";
if (!empty($_POST['return_url']) && strpos($_POST['return_url'], "\n") === false && strpos($_POST['return_url'], "\r") === false) {
    $return_url = $_POST['return_url'];
    if (preg_match('/^security_[a-z_]+\.php(\?.*)?$/', $return_url)) {
        $redirect = $return_url;
    }
} elseif (!empty($_POST['return_user_id'])) {
    $redirect .= "?user_id=" . intval($_POST['return_user_id']);
}
header("Location: " . $redirect);
exit;
?>
