<?php
session_start();
include "config.php";
include "security_schema.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

ensure_security_tables($conn);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function current_query_string() {
    return http_build_query($_GET);
}

function current_request_url() {
    $url = "security_incidents.php";
    $query = current_query_string();
    if ($query !== '') {
        $url .= "?" . $query;
    }
    return $url;
}

function make_group_key($userId, $deviceId) {
    return base64_encode(json_encode([
        'user_id' => intval($userId),
        'device_id' => (string)$deviceId
    ]));
}

$flash = $_SESSION['security_flash'] ?? null;
unset($_SESSION['security_flash']);

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'user_id' => isset($_GET['user_id']) ? intval($_GET['user_id']) : 0,
    'device_id' => trim($_GET['device_id'] ?? ''),
    'event' => trim($_GET['event'] ?? ''),
    'severity' => trim($_GET['severity'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
];

$where = [];
$params = [];
$types = "";

if ($filters['q'] !== '') {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR si.device_model LIKE ? OR si.manufacturer LIKE ? OR si.incident_label LIKE ? OR si.extra LIKE ?)";
    $like = "%" . $filters['q'] . "%";
    array_push($params, $like, $like, $like, $like, $like, $like);
    $types .= "ssssss";
}
if ($filters['user_id'] > 0) {
    $where[] = "si.user_id = ?";
    $params[] = $filters['user_id'];
    $types .= "i";
}
if ($filters['device_id'] !== '') {
    $where[] = "si.device_id LIKE ?";
    $params[] = "%" . $filters['device_id'] . "%";
    $types .= "s";
}
if ($filters['event'] !== '') {
    $where[] = "si.incident_type LIKE ?";
    $params[] = "%" . $filters['event'] . "%";
    $types .= "s";
}
if (in_array($filters['severity'], ['info', 'warning', 'critical'], true)) {
    $where[] = "si.severity = ?";
    $params[] = $filters['severity'];
    $types .= "s";
}
if ($filters['date_from'] !== '') {
    $where[] = "si.created_at >= ?";
    $params[] = date('Y-m-d H:i:s', strtotime($filters['date_from']));
    $types .= "s";
}
if ($filters['date_to'] !== '') {
    $where[] = "si.created_at <= ?";
    $params[] = date('Y-m-d H:i:s', strtotime($filters['date_to']));
    $types .= "s";
}
if ($filters['status'] === 'blocked') {
    $where[] = "COALESCE(usb.is_blocked, 0) = 1";
} elseif ($filters['status'] === 'allowed') {
    $where[] = "COALESCE(usb.is_blocked, 0) = 0";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        COALESCE(si.user_id, 0) AS user_key,
        COALESCE(NULLIF(si.device_id, ''), 'unknown') AS device_key,
        MAX(si.created_at) AS last_seen,
        MIN(si.created_at) AS first_seen,
        COUNT(*) AS total_events,
        SUM(CASE WHEN si.severity = 'critical' THEN 1 ELSE 0 END) AS critical_events,
        SUM(CASE WHEN si.severity = 'warning' THEN 1 ELSE 0 END) AS warning_events,
        GROUP_CONCAT(DISTINCT si.incident_type ORDER BY si.created_at DESC SEPARATOR ', ') AS event_types,
        MAX(si.latitude) AS latitude,
        MAX(si.longitude) AS longitude,
        MAX(si.location_accuracy) AS location_accuracy,
        MAX(si.ip_address) AS ip_address,
        MAX(si.manufacturer) AS manufacturer,
        MAX(si.device_model) AS device_model,
        MAX(si.device_brand) AS device_brand,
        MAX(si.device_product) AS device_product,
        MAX(si.device_hardware) AS device_hardware,
        MAX(si.android_version) AS android_version,
        MAX(si.app_version) AS app_version,
        MAX(si.app_version_code) AS app_version_code,
        u.username,
        u.email,
        COALESCE(usb.is_blocked, 0) AS is_blocked,
        usb.message AS block_message
    FROM security_incidents si
    LEFT JOIN users u ON u.id = si.user_id
    LEFT JOIN user_security_blocks usb ON usb.user_id = si.user_id
    $whereSql
    GROUP BY COALESCE(si.user_id, 0), COALESCE(NULLIF(si.device_id, ''), 'unknown'), u.username, u.email, usb.is_blocked, usb.message
    ORDER BY last_seen DESC, critical_events DESC, warning_events DESC
    LIMIT 500
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$returnUrl = current_request_url();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Security Center</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="vendors/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="vendors/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="vendors/images/favicon-16x16.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <link rel="stylesheet" href="vendors/styles/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .security-card { border-radius: 8px; border: 1px solid #edf0f5; }
        .risk-row-critical { background: #fff1f1 !important; border-left: 5px solid #dc3545; }
        .risk-row-warning { background: #fff8e8 !important; border-left: 5px solid #f59f00; }
        .risk-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
        .risk-critical { background: #dc3545; }
        .risk-warning { background: #f59f00; }
        .risk-info { background: #20c997; }
        .meta { font-size: 12px; color: #667085; line-height: 1.6; }
        .badge-soft-danger, .badge-soft-warning, .badge-soft-success, .badge-soft-info {
            display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700;
        }
        .badge-soft-danger { background: #fde8e8; color: #b42318; }
        .badge-soft-warning { background: #fff4d6; color: #9a6700; }
        .badge-soft-success { background: #e7f6ec; color: #027a48; }
        .badge-soft-info { background: #e8f0ff; color: #175cd3; }
        .filters-grid { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 10px; }
        @media (max-width: 992px) { .filters-grid { grid-template-columns: repeat(2, minmax(150px, 1fr)); } }
        @media (max-width: 576px) { .filters-grid { grid-template-columns: 1fr; } }
        .message-box { min-width: 240px; }
        .toolbar-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
        .selection-tools { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .table-check { width: 22px; height: 22px; }
    </style>
</head>
<body>
<?php include "header.php"; ?>
<div class="mobile-menu-overlay"></div>

<div class="main-container">
    <div class="xs-pd-20-10 pd-ltr-20">
        <div class="page-header">
            <div class="dashboard-header mb-4 p-3 rounded shadow-sm bg-white">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="page-title">
                        <h4 class="mb-0">Security Center</h4>
                        <small>Newest user/device issues are shown first. Open details to see every error, player issue, and suspicious action.</small>
                    </div>
                    <a class="btn btn-outline-secondary" href="security_incidents.php">Clear Filters</a>
                </div>
            </div>
        </div>

        <?php if (is_array($flash) && !empty($flash['message'])): ?>
            <div class="alert alert-<?php echo h($flash['type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo h($flash['message']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="pd-20 card-box security-card mb-30">
            <form method="get">
                <div class="filters-grid">
                    <input class="form-control" name="q" placeholder="Name, email, error, device" value="<?php echo h($filters['q']); ?>">
                    <input class="form-control" type="number" name="user_id" placeholder="User ID" value="<?php echo $filters['user_id'] > 0 ? intval($filters['user_id']) : ''; ?>">
                    <input class="form-control" name="device_id" placeholder="Device ID" value="<?php echo h($filters['device_id']); ?>">
                    <input class="form-control" name="event" placeholder="Event type" value="<?php echo h($filters['event']); ?>">
                    <select class="form-control" name="severity">
                        <option value="">All risk levels</option>
                        <option value="critical" <?php echo $filters['severity'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        <option value="warning" <?php echo $filters['severity'] === 'warning' ? 'selected' : ''; ?>>Warning</option>
                        <option value="info" <?php echo $filters['severity'] === 'info' ? 'selected' : ''; ?>>Info</option>
                    </select>
                    <select class="form-control" name="status">
                        <option value="">All block status</option>
                        <option value="blocked" <?php echo $filters['status'] === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        <option value="allowed" <?php echo $filters['status'] === 'allowed' ? 'selected' : ''; ?>>Allowed</option>
                    </select>
                    <input class="form-control" type="datetime-local" name="date_from" value="<?php echo h($filters['date_from']); ?>">
                    <input class="form-control" type="datetime-local" name="date_to" value="<?php echo h($filters['date_to']); ?>">
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Filter</button>
                </div>
            </form>
        </div>

        <div class="pd-20 card-box security-card mb-30">
            <form id="bulkDeleteGroupsForm" method="post" action="security_delete_incidents.php" onsubmit="return confirm('Delete selected security rows and all their issue records?');">
                <input type="hidden" name="mode" value="group">
                <input type="hidden" name="return_url" value="<?php echo h($returnUrl); ?>">
            </form>
            <div class="toolbar-row mb-3">
                <div>
                    <strong>Security Users / Devices</strong><br>
                    <span class="meta">Use the checkboxes to delete one selected row or select all visible rows.</span>
                </div>
                <div class="selection-tools">
                    <label class="mb-0 d-flex align-items-center" style="gap:8px;">
                        <input type="checkbox" id="selectAllGroups" class="table-check">
                        <span>Select all</span>
                    </label>
                    <button class="btn btn-danger" type="submit" form="bulkDeleteGroupsForm">
                        <i class="fa fa-trash"></i> Delete Selected
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless">
                    <thead class="bg-dark text-white">
                    <tr>
                        <th style="width:44px;"></th>
                        <th>Risk</th>
                        <th>User</th>
                        <th>Device</th>
                        <th>Events</th>
                        <th>Last Location</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $userId = intval($row['user_key']);
                            $critical = intval($row['critical_events']);
                            $warning = intval($row['warning_events']);
                            $total = intval($row['total_events']);
                            $blocked = intval($row['is_blocked']) === 1;
                            $riskClass = $critical > 0 || $blocked ? 'critical' : ($warning > 0 || $total >= 5 ? 'warning' : 'info');
                            $rowClass = $riskClass === 'critical' ? 'risk-row-critical' : ($riskClass === 'warning' ? 'risk-row-warning' : '');
                            $defaultMessage = $row['block_message'] ?: 'Your app access is currently paused. Please contact Urdu Bolo support.';
                            $detailUrl = "security_incident_details.php?user_id=" . $userId . "&device_id=" . urlencode($row['device_key']);
                            $groupKey = make_group_key($userId, $row['device_key']);
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td class="align-middle">
                                    <input type="checkbox" class="table-check group-checkbox" name="group_keys[]" value="<?php echo h($groupKey); ?>" form="bulkDeleteGroupsForm">
                                </td>
                                <td>
                                    <span class="risk-dot risk-<?php echo $riskClass; ?>"></span>
                                    <?php if ($riskClass === 'critical'): ?>
                                        <span class="badge-soft-danger">Suspicious</span>
                                    <?php elseif ($riskClass === 'warning'): ?>
                                        <span class="badge-soft-warning">Watch</span>
                                    <?php else: ?>
                                        <span class="badge-soft-success">Normal</span>
                                    <?php endif; ?>
                                    <div class="meta mt-1">
                                        <?php echo $critical; ?> critical<br>
                                        <?php echo $warning; ?> warning
                                    </div>
                                </td>
                                <td>
                                    <strong>#<?php echo $userId ?: 'Guest'; ?></strong><br>
                                    <?php echo h($row['username'] ?? 'Unknown'); ?><br>
                                    <span class="meta"><?php echo h($row['email'] ?? ''); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo h(trim(($row['manufacturer'] ?? '') . ' ' . ($row['device_model'] ?? ''))); ?></strong>
                                    <div class="meta">
                                        Device ID: <?php echo h($row['device_key']); ?><br>
                                        Brand/Product: <?php echo h(($row['device_brand'] ?? '') . ' / ' . ($row['device_product'] ?? '')); ?><br>
                                        Android: <?php echo h($row['android_version']); ?>,
                                        App: <?php echo h($row['app_version']); ?> (<?php echo h($row['app_version_code']); ?>)<br>
                                        IP: <?php echo h($row['ip_address']); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo $total; ?> total events</strong>
                                    <div class="meta">
                                        First: <?php echo h($row['first_seen']); ?><br>
                                        Last: <?php echo h($row['last_seen']); ?><br>
                                        <?php echo h($row['event_types']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['latitude'] !== null && $row['longitude'] !== null): ?>
                                        <?php echo h($row['latitude']); ?>, <?php echo h($row['longitude']); ?>
                                        <div class="meta">
                                            Accuracy: <?php echo h($row['location_accuracy']); ?>m<br>
                                            <a target="_blank" href="https://maps.google.com/?q=<?php echo h($row['latitude']); ?>,<?php echo h($row['longitude']); ?>">Open map</a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $blocked ? '<span class="badge-soft-danger">Blocked</span>' : '<span class="badge-soft-success">Allowed</span>'; ?>
                                </td>
                                <td>
                                    <a class="btn btn-info btn-sm mb-2" href="<?php echo h($detailUrl); ?>">
                                        <i class="fa fa-list"></i> View All Issues
                                    </a>
                                    <?php if ($userId > 0): ?>
                                        <form method="post" action="update_user_block.php" class="message-box">
                                            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                            <input type="hidden" name="return_url" value="<?php echo h($returnUrl); ?>">
                                            <textarea name="message" class="form-control mb-2" rows="3"><?php echo h($defaultMessage); ?></textarea>
                                            <div class="d-flex" style="gap: 8px;">
                                                <button class="btn btn-danger btn-sm" name="is_blocked" value="1" type="submit">Block</button>
                                                <button class="btn btn-success btn-sm" name="is_blocked" value="0" type="submit">Unblock</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Login user required</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No security users/devices found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('selectAllGroups');
    var checkboxes = document.querySelectorAll('.group-checkbox');
    if (!selectAll || !checkboxes.length) {
        return;
    }

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var allChecked = Array.from(checkboxes).every(function (item) { return item.checked; });
            selectAll.checked = allChecked;
        });
    });
});
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
