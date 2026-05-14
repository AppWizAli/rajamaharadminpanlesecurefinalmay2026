<?php
session_start();
include "config.php";
include "subscription_schema.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$schemaStatus = ensure_subscription_tables($conn);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function parse_subscription_snapshot($rawValue) {
    if (!is_string($rawValue) || trim($rawValue) === '') {
        return [];
    }

    $decoded = json_decode($rawValue, true);
    return is_array($decoded) ? $decoded : [];
}

function render_snapshot_summary($rawValue) {
    $snapshot = parse_subscription_snapshot($rawValue);
    if (empty($snapshot)) {
        return '';
    }

    $lines = [];
    if (!empty($snapshot['monthly_amount']) || !empty($snapshot['currency'])) {
        $lines[] = 'Plan Amount: ' . trim(($snapshot['monthly_amount'] ?? '0') . ' ' . ($snapshot['currency'] ?? 'PKR'));
    }
    if (!empty($snapshot['jazzcash_number']) || !empty($snapshot['jazzcash_title'])) {
        $lines[] = 'JazzCash: ' . trim(($snapshot['jazzcash_number'] ?? '') . ' ' . ($snapshot['jazzcash_title'] ?? ''));
    }
    if (!empty($snapshot['easypaisa_number']) || !empty($snapshot['easypaisa_title'])) {
        $lines[] = 'EasyPaisa: ' . trim(($snapshot['easypaisa_number'] ?? '') . ' ' . ($snapshot['easypaisa_title'] ?? ''));
    }
    if (!empty($snapshot['bank_name']) || !empty($snapshot['bank_account_number']) || !empty($snapshot['bank_iban'])) {
        $bankBits = array_filter([
            $snapshot['bank_name'] ?? '',
            $snapshot['bank_account_number'] ?? '',
            $snapshot['bank_iban'] ?? ''
        ], static function ($value) {
            return trim((string)$value) !== '';
        });
        if (!empty($bankBits)) {
            $lines[] = 'Bank: ' . implode(' | ', $bankBits);
        }
    }

    return implode("\n", $lines);
}

function current_request_url() {
    $url = "subscription_requests.php";
    $query = http_build_query($_GET);
    if ($query !== '') {
        $url .= "?" . $query;
    }
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
    $monthlyAmount = max(0, floatval($_POST['monthly_amount'] ?? 0));
    $currency = trim($_POST['currency'] ?? 'PKR');
    $defaultGroupId = intval($_POST['default_group_id'] ?? 0);
    $jazzcashNumber = trim($_POST['jazzcash_number'] ?? '');
    $jazzcashTitle = trim($_POST['jazzcash_title'] ?? '');
    $easypaisaNumber = trim($_POST['easypaisa_number'] ?? '');
    $easypaisaTitle = trim($_POST['easypaisa_title'] ?? '');
    $bankName = trim($_POST['bank_name'] ?? '');
    $bankAccountTitle = trim($_POST['bank_account_title'] ?? '');
    $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
    $bankIban = trim($_POST['bank_iban'] ?? '');
    $paymentInstructions = trim($_POST['payment_instructions'] ?? '');
    $adminId = intval($_SESSION['admin_id']);

    if ($defaultGroupId <= 0) {
        $defaultGroupId = null;
    }
    if ($currency === '') {
        $currency = 'PKR';
    }

    $stmt = $conn->prepare("
        UPDATE subscription_settings
        SET monthly_amount = ?,
            currency = ?,
            default_group_id = ?,
            jazzcash_number = ?,
            jazzcash_title = ?,
            easypaisa_number = ?,
            easypaisa_title = ?,
            bank_name = ?,
            bank_account_title = ?,
            bank_account_number = ?,
            bank_iban = ?,
            payment_instructions = ?,
            updated_by = ?,
            updated_at = NOW()
        WHERE id = 1
    ");
    if ($stmt) {
        $stmt->bind_param(
            "dsisssssssssi",
            $monthlyAmount,
            $currency,
            $defaultGroupId,
            $jazzcashNumber,
            $jazzcashTitle,
            $easypaisaNumber,
            $easypaisaTitle,
            $bankName,
            $bankAccountTitle,
            $bankAccountNumber,
            $bankIban,
            $paymentInstructions,
            $adminId
        );
        $stmt->execute();
        $stmt->close();
        $_SESSION['subscription_flash'] = [
            'type' => 'success',
            'message' => 'Subscription settings updated successfully.'
        ];
    } else {
        $_SESSION['subscription_flash'] = [
            'type' => 'danger',
            'message' => 'Unable to save subscription settings: ' . $conn->error
        ];
    }

    header("Location: subscription_requests.php");
    exit;
}

$flash = $_SESSION['subscription_flash'] ?? null;
unset($_SESSION['subscription_flash']);

if ((!is_array($flash) || empty($flash['message'])) && !empty($schemaStatus['message'])) {
    $flash = [
        'type' => 'warning',
        'message' => 'Subscription setup warning: ' . $schemaStatus['message']
    ];
}

$filters = [
    'status' => trim($_GET['status'] ?? ''),
    'q' => trim($_GET['q'] ?? '')
];

$where = [];
$params = [];
$types = '';

if (in_array($filters['status'], ['pending', 'approved', 'rejected'], true)) {
    $where[] = "sr.status = ?";
    $params[] = $filters['status'];
    $types .= 's';
}
if ($filters['q'] !== '') {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR sr.invoice_no LIKE ? OR sr.payment_method LIKE ?)";
    $like = '%' . $filters['q'] . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$groups = [];
$groupsResult = $conn->query("SELECT id, group_name FROM `groups` ORDER BY group_name ASC");
if ($groupsResult) {
    while ($groupRow = $groupsResult->fetch_assoc()) {
        $groups[] = $groupRow;
    }
} elseif (empty($flash['message'])) {
    $flash = [
        'type' => 'danger',
        'message' => 'Unable to load groups list: ' . $conn->error
    ];
}

$settings = get_subscription_settings($conn);
$currentUrl = current_request_url();

$requestsSql = "
    SELECT
        sr.*,
        u.username,
        u.email,
        g.group_name,
        approver.username AS approved_by_name
    FROM subscription_requests sr
    LEFT JOIN users u ON u.id = sr.user_id
    LEFT JOIN `groups` g ON g.id = sr.group_id
    LEFT JOIN admin approver ON approver.id = sr.approved_by
    $whereSql
    ORDER BY CASE sr.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END, sr.created_at DESC, sr.id DESC
";
$requestsStmt = $conn->prepare($requestsSql);
if ($requestsStmt && !empty($params)) {
    $requestsStmt->bind_param($types, ...$params);
}
$requestsResult = false;
if ($requestsStmt) {
    $requestsStmt->execute();
    $requestsResult = $requestsStmt->get_result();
} elseif (empty($flash['message'])) {
    $flash = [
        'type' => 'danger',
        'message' => 'Unable to load subscription requests: ' . $conn->error
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="vendors/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="vendors/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="vendors/images/favicon-16x16.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <link rel="stylesheet" href="vendors/styles/css/custom.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .subscription-card { border-radius: 10px; border: 1px solid #edf0f5; }
        .request-card { border: 1px solid #edf0f5; border-radius: 10px; padding: 18px; background: #fff; margin-bottom: 16px; }
        .request-card.pending { border-left: 5px solid #f59f00; }
        .request-card.approved { border-left: 5px solid #027a48; }
        .request-card.rejected { border-left: 5px solid #b42318; }
        .meta { font-size: 12px; color: #667085; line-height: 1.7; }
        .badge-soft { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-pending { background: #fff4d6; color: #9a6700; }
        .badge-approved { background: #e7f6ec; color: #027a48; }
        .badge-rejected { background: #fde8e8; color: #b42318; }
        .filters-grid { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 10px; }
        .settings-grid { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 12px; }
        .request-image { width: 110px; height: 110px; object-fit: cover; border-radius: 10px; border: 1px solid #e5e7eb; }
        @media (max-width: 768px) {
            .filters-grid, .settings-grid { grid-template-columns: 1fr; }
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
                        <h4 class="mb-0">Subscriptions</h4>
                        <small>Manage payment account details, review screenshots, and manually approve one-month subscriptions.</small>
                    </div>
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

        <div class="pd-20 card-box subscription-card mb-30">
            <h5 class="mb-3">Subscription Settings</h5>
            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <div class="settings-grid">
                    <div>
                        <label>Monthly Amount</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="monthly_amount" value="<?php echo h($settings['monthly_amount']); ?>">
                    </div>
                    <div>
                        <label>Currency</label>
                        <input class="form-control" name="currency" value="<?php echo h($settings['currency']); ?>">
                    </div>
                    <div>
                        <label>Default Group</label>
                        <select class="form-control" name="default_group_id">
                            <option value="0">Select group</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo intval($group['id']); ?>" <?php echo intval($settings['default_group_id']) === intval($group['id']) ? 'selected' : ''; ?>>
                                    <?php echo h($group['group_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>JazzCash Number</label>
                        <input class="form-control" name="jazzcash_number" value="<?php echo h($settings['jazzcash_number']); ?>">
                    </div>
                    <div>
                        <label>JazzCash Account Title</label>
                        <input class="form-control" name="jazzcash_title" value="<?php echo h($settings['jazzcash_title']); ?>">
                    </div>
                    <div>
                        <label>EasyPaisa Number</label>
                        <input class="form-control" name="easypaisa_number" value="<?php echo h($settings['easypaisa_number']); ?>">
                    </div>
                    <div>
                        <label>EasyPaisa Account Title</label>
                        <input class="form-control" name="easypaisa_title" value="<?php echo h($settings['easypaisa_title']); ?>">
                    </div>
                    <div>
                        <label>Bank Name</label>
                        <input class="form-control" name="bank_name" value="<?php echo h($settings['bank_name']); ?>">
                    </div>
                    <div>
                        <label>Bank Account Title</label>
                        <input class="form-control" name="bank_account_title" value="<?php echo h($settings['bank_account_title']); ?>">
                    </div>
                    <div>
                        <label>Bank Account Number</label>
                        <input class="form-control" name="bank_account_number" value="<?php echo h($settings['bank_account_number']); ?>">
                    </div>
                    <div>
                        <label>Bank IBAN</label>
                        <input class="form-control" name="bank_iban" value="<?php echo h($settings['bank_iban']); ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <label>Extra Instructions</label>
                    <textarea class="form-control" name="payment_instructions" rows="4"><?php echo h($settings['payment_instructions']); ?></textarea>
                </div>
                <button class="btn btn-primary mt-3" type="submit">
                    <i class="fa fa-save"></i> Save Settings
                </button>
            </form>
        </div>

        <div class="pd-20 card-box subscription-card mb-30">
            <form method="get">
                <div class="filters-grid">
                    <input class="form-control" name="q" placeholder="User, email, invoice, method" value="<?php echo h($filters['q']); ?>">
                    <select class="form-control" name="status">
                        <option value="">All statuses</option>
                        <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filters['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filters['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <button class="btn btn-outline-primary mt-3" type="submit">
                    <i class="fa fa-filter"></i> Filter Requests
                </button>
            </form>
        </div>

        <div class="pd-20 card-box subscription-card mb-30">
            <h5 class="mb-3">Subscription Requests</h5>
            <?php if ($requestsResult && $requestsResult->num_rows > 0): ?>
                <?php while ($row = $requestsResult->fetch_assoc()): ?>
                    <?php $statusClass = in_array($row['status'], ['pending', 'approved', 'rejected'], true) ? $row['status'] : 'pending'; ?>
                    <div class="request-card <?php echo h($statusClass); ?>">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <?php if (!empty($row['screenshot_url'])): ?>
                                    <a href="<?php echo h($row['screenshot_url']); ?>" target="_blank">
                                        <img src="<?php echo h($row['screenshot_url']); ?>" alt="Payment screenshot" class="request-image">
                                    </a>
                                <?php else: ?>
                                    <div class="request-image d-flex align-items-center justify-content-center text-muted">No Image</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5 mb-3">
                                <div class="d-flex align-items-center mb-2" style="gap:8px;">
                                    <span class="badge-soft badge-<?php echo h($statusClass); ?>"><?php echo h(strtoupper($row['status'])); ?></span>
                                    <strong>#<?php echo intval($row['id']); ?></strong>
                                </div>
                                <div><strong>User:</strong> #<?php echo intval($row['user_id']); ?> <?php echo h($row['username'] ?? 'Unknown'); ?></div>
                                <div class="meta"><?php echo h($row['email'] ?? ''); ?></div>
                                <div class="mt-2"><strong>Amount:</strong> <?php echo h($row['amount']); ?> <?php echo h($row['currency']); ?></div>
                                <div><strong>Method:</strong> <?php echo h($row['payment_method'] ?: 'Not specified'); ?></div>
                                <div><strong>Requested:</strong> <?php echo h($row['created_at']); ?></div>
                                <div><strong>Invoice:</strong> <?php echo h($row['invoice_no'] ?: 'Not generated yet'); ?></div>
                                <div><strong>Group:</strong> <?php echo h($row['group_name'] ?: ($settings['default_group_name'] ?: 'Not selected')); ?></div>
                                <?php $snapshotSummary = render_snapshot_summary($row['details_snapshot'] ?? ''); ?>
                                <?php if ($snapshotSummary !== ''): ?>
                                    <div class="meta mt-2"><strong>Payment Details Snapshot:</strong><br><?php echo nl2br(h($snapshotSummary)); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($row['note'])): ?>
                                    <div class="meta mt-2"><strong>User Note:</strong><br><?php echo nl2br(h($row['note'])); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="post" action="subscription_request_action.php" class="mb-3">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="request_id" value="<?php echo intval($row['id']); ?>">
                                        <input type="hidden" name="return_url" value="<?php echo h($currentUrl); ?>">
                                        <label>Approve Into Group</label>
                                        <select class="form-control mb-2" name="group_id">
                                            <option value="0">Select group</option>
                                            <?php foreach ($groups as $group): ?>
                                                <?php $selected = intval($row['group_id']) === intval($group['id']) || (empty($row['group_id']) && intval($settings['default_group_id']) === intval($group['id'])); ?>
                                                <option value="<?php echo intval($group['id']); ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                                    <?php echo h($group['group_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label>Admin Note</label>
                                        <textarea class="form-control mb-2" name="admin_note" rows="3" placeholder="Optional note for approval"></textarea>
                                        <button class="btn btn-success" type="submit">
                                            <i class="fa fa-check"></i> Approve + Add 1 Month
                                        </button>
                                    </form>

                                    <form method="post" action="subscription_request_action.php">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="request_id" value="<?php echo intval($row['id']); ?>">
                                        <input type="hidden" name="return_url" value="<?php echo h($currentUrl); ?>">
                                        <label>Reject Note</label>
                                        <textarea class="form-control mb-2" name="admin_note" rows="2" placeholder="Optional reason"></textarea>
                                        <button class="btn btn-danger" type="submit">
                                            <i class="fa fa-times"></i> Reject
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="meta">
                                        <?php if ($row['status'] === 'approved'): ?>
                                            Approved: <?php echo h($row['approved_at']); ?><br>
                                            Subscription: <?php echo h($row['subscription_start_date']); ?> to <?php echo h($row['subscription_end_date']); ?><br>
                                            Months Added: <?php echo intval($row['months_added']); ?><br>
                                            Approved By: <?php echo h($row['approved_by_name'] ?: ('Admin #' . intval($row['approved_by']))); ?><br>
                                        <?php else: ?>
                                            Rejected: <?php echo h($row['rejected_at']); ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($row['admin_note'])): ?>
                                            <div class="mt-2"><strong>Admin Note:</strong><br><?php echo nl2br(h($row['admin_note'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-muted">No subscription requests found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
</body>
</html>
<?php
if ($requestsStmt) {
    $requestsStmt->close();
}
$conn->close();
?>
