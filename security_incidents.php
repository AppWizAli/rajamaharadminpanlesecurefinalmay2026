<?php
session_start();
include "config.php";
include "security_schema.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

ensure_security_tables($conn);

$filter_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$where = "";
$params = [];
$types = "";
if ($filter_user_id > 0) {
    $where = "WHERE si.user_id = ?";
    $params[] = $filter_user_id;
    $types .= "i";
}

$sql = "
    SELECT
        si.*,
        u.username,
        u.email,
        COALESCE(usb.is_blocked, 0) AS is_blocked,
        usb.message AS block_message
    FROM security_incidents si
    LEFT JOIN users u ON u.id = si.user_id
    LEFT JOIN user_security_blocks usb ON usb.user_id = si.user_id
    $where
    ORDER BY si.created_at DESC
    LIMIT 300
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Security Incidents</title>
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
        .incident-meta {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
        }
        .message-box {
            min-width: 260px;
        }
        .badge-soft-danger {
            background: #fde8e8;
            color: #b42318;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
        }
        .badge-soft-success {
            background: #e7f6ec;
            color: #027a48;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
        }
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
                        <h4 class="mb-0">Security Incidents</h4>
                        <small>Recent app protection events, sorted by newest first</small>
                    </div>
                    <form method="get" class="d-flex align-items-center" style="gap: 8px;">
                        <input type="number" name="user_id" class="form-control" placeholder="User ID" value="<?php echo $filter_user_id > 0 ? $filter_user_id : ''; ?>">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a class="btn btn-light" href="security_incidents.php">Clear</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="pd-20 card-box mb-30">
            <div class="table-responsive">
                <table class="table table-striped table-borderless">
                    <thead class="bg-dark text-white">
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Device</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Block / Message</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $userId = intval($row['user_id']);
                            $blocked = intval($row['is_blocked']) === 1;
                            $defaultMessage = $row['block_message'] ?: 'Your app access is currently paused. Please contact Urdu Bolo support.';
                            ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row['created_at']); ?>
                                    <div class="incident-meta">IP: <?php echo htmlspecialchars($row['ip_address'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <strong>#<?php echo $userId ?: 'Guest'; ?></strong><br>
                                    <?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?><br>
                                    <span class="incident-meta"><?php echo htmlspecialchars($row['email'] ?? ''); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['incident_label'] ?: $row['incident_type']); ?></strong>
                                    <div class="incident-meta">
                                        Type: <?php echo htmlspecialchars($row['incident_type']); ?><br>
                                        Area: <?php echo htmlspecialchars($row['app_area'] ?? ''); ?><br>
                                        <?php echo htmlspecialchars($row['extra'] ?? ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(trim(($row['manufacturer'] ?? '') . ' ' . ($row['device_model'] ?? ''))); ?>
                                    <div class="incident-meta">
                                        Android: <?php echo htmlspecialchars($row['android_version'] ?? ''); ?><br>
                                        App: <?php echo htmlspecialchars($row['app_version'] ?? ''); ?><br>
                                        Device ID: <?php echo htmlspecialchars($row['device_id'] ?? ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['latitude'] !== null && $row['longitude'] !== null): ?>
                                        <?php echo htmlspecialchars($row['latitude']); ?>, <?php echo htmlspecialchars($row['longitude']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($blocked): ?>
                                        <span class="badge-soft-danger">Blocked</span>
                                    <?php else: ?>
                                        <span class="badge-soft-success">Allowed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($userId > 0): ?>
                                        <form method="post" action="update_user_block.php" class="message-box">
                                            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                            <input type="hidden" name="return_user_id" value="<?php echo $filter_user_id; ?>">
                                            <textarea name="message" class="form-control mb-2" rows="3"><?php echo htmlspecialchars($defaultMessage); ?></textarea>
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
                        <tr><td colspan="7" class="text-center text-muted">No incidents found.</td></tr>
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
<script src="vendors/js/jquery-3.3.1.min.js"></script>
<script>
    $(document).ready(function() {
        $(".xp-menubar").on('click', function() {
            $("#sidebar").toggleClass('active');
            $("#content").toggleClass('active');
        });
        $('.xp-menubar,.body-overlay').on('click', function() {
            $("#sidebar,.body-overlay").toggleClass('show-nav');
        });
    });
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
