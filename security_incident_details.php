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

function current_request_url() {
    $url = "security_incident_details.php";
    $query = http_build_query($_GET);
    if ($query !== '') {
        $url .= "?" . $query;
    }
    return $url;
}

$flash = $_SESSION['security_flash'] ?? null;
unset($_SESSION['security_flash']);

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$device_id = trim($_GET['device_id'] ?? '');
$event = trim($_GET['event'] ?? '');
$severity = trim($_GET['severity'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

$where = [];
$params = [];
$types = "";

if ($user_id > 0) {
    $where[] = "si.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
} else {
    $where[] = "(si.user_id IS NULL OR si.user_id = 0)";
}
if ($device_id !== '' && $device_id !== 'unknown') {
    $where[] = "si.device_id = ?";
    $params[] = $device_id;
    $types .= "s";
} elseif ($device_id === 'unknown') {
    $where[] = "(si.device_id IS NULL OR si.device_id = '')";
}
if ($event !== '') {
    $where[] = "si.incident_type LIKE ?";
    $params[] = "%" . $event . "%";
    $types .= "s";
}
if (in_array($severity, ['info', 'warning', 'critical'], true)) {
    $where[] = "si.severity = ?";
    $params[] = $severity;
    $types .= "s";
}
if ($date_from !== '') {
    $where[] = "si.created_at >= ?";
    $params[] = date('Y-m-d H:i:s', strtotime($date_from));
    $types .= "s";
}
if ($date_to !== '') {
    $where[] = "si.created_at <= ?";
    $params[] = date('Y-m-d H:i:s', strtotime($date_to));
    $types .= "s";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT si.*, u.username, u.email, COALESCE(usb.is_blocked, 0) AS is_blocked, usb.message AS block_message
    FROM security_incidents si
    LEFT JOIN users u ON u.id = si.user_id
    LEFT JOIN user_security_blocks usb ON usb.user_id = si.user_id
    $whereSql
    ORDER BY si.created_at DESC
    LIMIT 1000
";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$first = $result && $result->num_rows > 0 ? $result->fetch_assoc() : null;
if ($first) {
    $result->data_seek(0);
}
$returnUrl = "security_incidents.php";
$currentUrl = current_request_url();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Security Details</title>
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
        .meta { font-size: 12px; color: #667085; line-height: 1.6; }
        .badge-risk { display:inline-block; padding: 5px 9px; border-radius: 999px; font-size: 12px; font-weight:700; }
        .risk-critical { background:#fde8e8; color:#b42318; }
        .risk-warning { background:#fff4d6; color:#9a6700; }
        .risk-info { background:#e7f6ec; color:#027a48; }
        .event-card { border:1px solid #edf0f5; border-radius:8px; padding:16px; margin-bottom:12px; background:#fff; }
        .event-card.critical { border-left:5px solid #dc3545; }
        .event-card.warning { border-left:5px solid #f59f00; }
        .filters-grid { display:grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap:10px; }
        @media (max-width: 992px) { .filters-grid { grid-template-columns: repeat(2, minmax(150px, 1fr)); } }
        @media (max-width: 576px) { .filters-grid { grid-template-columns: 1fr; } }
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
                        <h4 class="mb-0">All Security Issues</h4>
                        <small>
                            User #<?php echo $user_id ?: 'Guest'; ?>,
                            Device <?php echo h($device_id ?: 'Any'); ?>
                        </small>
                    </div>
                    <a class="btn btn-outline-secondary" href="<?php echo h($returnUrl); ?>">Back To Security</a>
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

        <?php if ($first): ?>
            <div class="pd-20 card-box mb-30">
                <div class="row">
                    <div class="col-md-3">
                        <strong>User</strong>
                        <div>#<?php echo h($first['user_id'] ?: 'Guest'); ?></div>
                        <div class="meta"><?php echo h($first['username'] ?? 'Unknown'); ?><br><?php echo h($first['email'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3">
                        <strong>Device</strong>
                        <div><?php echo h(trim(($first['manufacturer'] ?? '') . ' ' . ($first['device_model'] ?? ''))); ?></div>
                        <div class="meta">ID: <?php echo h($first['device_id']); ?><br>Hardware: <?php echo h($first['device_hardware']); ?></div>
                    </div>
                    <div class="col-md-3">
                        <strong>App</strong>
                        <div><?php echo h($first['app_version']); ?> (<?php echo h($first['app_version_code']); ?>)</div>
                        <div class="meta"><?php echo h($first['package_name']); ?><br>Android <?php echo h($first['android_version']); ?></div>
                    </div>
                    <div class="col-md-3">
                        <strong>Status</strong><br>
                        <?php echo intval($first['is_blocked']) === 1 ? '<span class="badge-risk risk-critical">Blocked</span>' : '<span class="badge-risk risk-info">Allowed</span>'; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="pd-20 card-box mb-30">
            <form method="get">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <input type="hidden" name="device_id" value="<?php echo h($device_id); ?>">
                <div class="filters-grid">
                    <input class="form-control" name="event" placeholder="Event type" value="<?php echo h($event); ?>">
                    <select class="form-control" name="severity">
                        <option value="">All risk levels</option>
                        <option value="critical" <?php echo $severity === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        <option value="warning" <?php echo $severity === 'warning' ? 'selected' : ''; ?>>Warning</option>
                        <option value="info" <?php echo $severity === 'info' ? 'selected' : ''; ?>>Info</option>
                    </select>
                    <input class="form-control" type="datetime-local" name="date_from" value="<?php echo h($date_from); ?>">
                    <input class="form-control" type="datetime-local" name="date_to" value="<?php echo h($date_to); ?>">
                </div>
                <button class="btn btn-primary mt-3" type="submit"><i class="fa fa-filter"></i> Filter Details</button>
            </form>
        </div>

        <form id="bulkDeleteIncidentsForm" method="post" action="security_delete_incidents.php" onsubmit="return confirm('Delete the selected issue records?');">
            <input type="hidden" name="mode" value="incident">
            <input type="hidden" name="return_url" value="<?php echo h($currentUrl); ?>">
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="toolbar-row mb-3">
                <div>
                    <strong>Issue Records</strong><br>
                    <span class="meta">Newest issues are already on top. Select one or all visible issue cards to delete them.</span>
                </div>
                <div class="selection-tools">
                    <label class="mb-0 d-flex align-items-center" style="gap:8px;">
                        <input type="checkbox" id="selectAllIncidents" class="table-check">
                        <span>Select all</span>
                    </label>
                    <button class="btn btn-danger" type="submit" form="bulkDeleteIncidentsForm">
                        <i class="fa fa-trash"></i> Delete Selected
                    </button>
                </div>
            </div>

            <?php while ($row = $result->fetch_assoc()): ?>
                <?php $risk = in_array($row['severity'], ['critical', 'warning', 'info'], true) ? $row['severity'] : 'info'; ?>
                <div class="event-card <?php echo h($risk); ?>">
                    <div class="d-flex flex-wrap justify-content-between">
                        <div class="d-flex align-items-start" style="gap:12px;">
                            <input type="checkbox" class="table-check incident-checkbox mt-1" name="incident_ids[]" value="<?php echo intval($row['id']); ?>" form="bulkDeleteIncidentsForm">
                            <div>
                                <span class="badge-risk risk-<?php echo h($risk); ?>"><?php echo h(strtoupper($risk)); ?></span>
                                <strong class="ml-2"><?php echo h($row['incident_label'] ?: $row['incident_type']); ?></strong>
                                <div class="meta mt-2">
                                    Type: <?php echo h($row['incident_type']); ?> |
                                    Activity: <?php echo h($row['app_area']); ?> |
                                    Time: <?php echo h($row['created_at']); ?> |
                                    IP: <?php echo h($row['ip_address']); ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <?php if ($row['latitude'] !== null && $row['longitude'] !== null): ?>
                                <a class="btn btn-sm btn-outline-primary" target="_blank" href="https://maps.google.com/?q=<?php echo h($row['latitude']); ?>,<?php echo h($row['longitude']); ?>">
                                    <i class="fa fa-map-marker-alt"></i> Map
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Device Details</strong>
                            <div class="meta">
                                Manufacturer: <?php echo h($row['manufacturer']); ?><br>
                                Model: <?php echo h($row['device_model']); ?><br>
                                Brand: <?php echo h($row['device_brand']); ?><br>
                                Product: <?php echo h($row['device_product']); ?><br>
                                Hardware: <?php echo h($row['device_hardware']); ?><br>
                                Fingerprint: <?php echo h($row['device_fingerprint']); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <strong>App / OS</strong>
                            <div class="meta">
                                Package: <?php echo h($row['package_name']); ?><br>
                                App version: <?php echo h($row['app_version']); ?><br>
                                Version code: <?php echo h($row['app_version_code']); ?><br>
                                Android: <?php echo h($row['android_version']); ?><br>
                                Device ID: <?php echo h($row['device_id']); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <strong>Location / Extra</strong>
                            <div class="meta">
                                Lat/Lng: <?php echo h($row['latitude']); ?>, <?php echo h($row['longitude']); ?><br>
                                Accuracy: <?php echo h($row['location_accuracy']); ?>m<br>
                                Extra: <?php echo nl2br(h($row['extra'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="pd-20 card-box text-center text-muted">No detailed incidents found for this user/device.</div>
        <?php endif; ?>
    </div>
</div>

<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('selectAllIncidents');
    var checkboxes = document.querySelectorAll('.incident-checkbox');
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
