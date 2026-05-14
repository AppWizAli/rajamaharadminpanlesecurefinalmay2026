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

function bind_dynamic_params($stmt, $types, $values) {
    $refs = [];
    foreach ($values as $key => $value) {
        $refs[$key] = &$values[$key];
    }
    array_unshift($refs, $types);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
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

    if ($currency === '') {
        $currency = 'PKR';
    }

    $stmt = $conn->prepare("
        UPDATE subscription_settings
        SET monthly_amount = ?,
            currency = ?,
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
            "dssssssssssi",
            $monthlyAmount,
            $currency,
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
            'message' => 'Payment account settings updated successfully.'
        ];
    } else {
        $_SESSION['subscription_flash'] = [
            'type' => 'danger',
            'message' => 'Unable to save payment account settings: ' . $conn->error
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
        approver.admin_name AS approved_by_name
    FROM subscription_requests sr
    LEFT JOIN users u ON u.id = sr.user_id
    LEFT JOIN `groups` g ON g.id = sr.group_id
    LEFT JOIN admin approver ON approver.id = sr.approved_by
    $whereSql
    ORDER BY CASE sr.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END, sr.created_at DESC, sr.id DESC
";

$requestRows = [];
$requestsStmt = $conn->prepare($requestsSql);
if ($requestsStmt && !empty($params)) {
    bind_dynamic_params($requestsStmt, $types, $params);
}
if ($requestsStmt) {
    $requestsStmt->execute();
    $requestsResult = $requestsStmt->get_result();
    while ($requestsResult && $row = $requestsResult->fetch_assoc()) {
        $requestRows[] = $row;
    }
} elseif (empty($flash['message'])) {
    $flash = [
        'type' => 'danger',
        'message' => 'Unable to load subscription requests: ' . $conn->error
    ];
}

$membershipsByUser = [];
$primaryMembershipByUser = [];
$requestUserIds = [];
foreach ($requestRows as $row) {
    $userId = intval($row['user_id'] ?? 0);
    if ($userId > 0) {
        $requestUserIds[$userId] = $userId;
    }
}

if (!empty($requestUserIds)) {
    $membershipSql = "
        SELECT
            gm.user_id,
            gm.group_id,
            gm.start_date,
            gm.end_date,
            gm.updated_at,
            g.group_name,
            CASE WHEN gm.end_date IS NOT NULL AND gm.end_date >= CURDATE() THEN 1 ELSE 0 END AS is_active
        FROM group_members gm
        LEFT JOIN `groups` g ON g.id = gm.group_id
        WHERE gm.user_id IN (" . implode(',', array_fill(0, count($requestUserIds), '?')) . ")
        ORDER BY gm.user_id ASC, CASE WHEN gm.end_date IS NULL THEN 1 ELSE 0 END ASC, gm.end_date DESC, gm.group_id DESC
    ";

    $membershipStmt = $conn->prepare($membershipSql);
    if ($membershipStmt) {
        $membershipIds = array_values($requestUserIds);
        bind_dynamic_params($membershipStmt, str_repeat('i', count($membershipIds)), $membershipIds);
        $membershipStmt->execute();
        $membershipResult = $membershipStmt->get_result();
        while ($membershipResult && $membership = $membershipResult->fetch_assoc()) {
            $userId = intval($membership['user_id']);
            if (!isset($membershipsByUser[$userId])) {
                $membershipsByUser[$userId] = [];
            }
            $membershipsByUser[$userId][] = $membership;
            if (!isset($primaryMembershipByUser[$userId])) {
                $primaryMembershipByUser[$userId] = $membership;
            }
        }
        $membershipStmt->close();
    } elseif (empty($flash['message'])) {
        $flash = [
            'type' => 'danger',
            'message' => 'Unable to load group subscription rows: ' . $conn->error
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscriptions</title>
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
        .section-note { color: #667085; font-size: 13px; }
        .membership-box { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; margin-top: 10px; }
        .membership-line { font-size: 12px; color: #344054; margin-bottom: 6px; }
        .membership-line:last-child { margin-bottom: 0; }
        .approval-actions { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; }
        .approve-state-text { min-height: 18px; }
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
                        <small>Keep payment account details separate, and manage every invoice/request with the same group subscription rows used everywhere else.</small>
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
            <h5 class="mb-1">Payment Account Settings</h5>
            <div class="section-note mb-3">Only payment amount and account details belong here. Group choice is handled on each invoice/request row below.</div>
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
                    <i class="fa fa-save"></i> Save Payment Details
                </button>
            </form>
        </div>

        <div class="pd-20 card-box subscription-card mb-30">
            <h5 class="mb-1">Invoices & Subscription Management</h5>
            <div class="section-note mb-3">See sent invoices, current group subscription rows, and approve by either adding to group or increasing the existing date from the same row.</div>
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
                    <i class="fa fa-filter"></i> Filter
                </button>
            </form>
        </div>

        <div class="pd-20 card-box subscription-card mb-30">
            <h5 class="mb-3">Sent Invoices & Requests</h5>
            <?php if (!empty($requestRows)): ?>
                <?php foreach ($requestRows as $row): ?>
                    <?php
                    $statusClass = in_array($row['status'], ['pending', 'approved', 'rejected'], true) ? $row['status'] : 'pending';
                    $userId = intval($row['user_id']);
                    $memberships = $membershipsByUser[$userId] ?? [];
                    $primaryMembership = $primaryMembershipByUser[$userId] ?? null;
                    $selectedGroupId = intval($row['group_id'] ?? 0);
                    if ($selectedGroupId <= 0 && $primaryMembership) {
                        $selectedGroupId = intval($primaryMembership['group_id']);
                    }
                    $membershipMeta = [];
                    foreach ($memberships as $membership) {
                        $membershipMeta[] = [
                            'group_id' => intval($membership['group_id']),
                            'group_name' => $membership['group_name'] ?? 'Group',
                            'start_date' => $membership['start_date'] ?? '',
                            'end_date' => $membership['end_date'] ?? '',
                            'is_active' => intval($membership['is_active'] ?? 0)
                        ];
                    }
                    ?>
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
                                <div><strong>User:</strong> #<?php echo $userId; ?> <?php echo h($row['username'] ?? 'Unknown'); ?></div>
                                <div class="meta"><?php echo h($row['email'] ?? ''); ?></div>
                                <div class="mt-2"><strong>Amount:</strong> <?php echo h($row['amount']); ?> <?php echo h($row['currency']); ?></div>
                                <div><strong>Method:</strong> <?php echo h($row['payment_method'] ?: 'Not specified'); ?></div>
                                <div><strong>Requested:</strong> <?php echo h($row['created_at']); ?></div>
                                <div><strong>Invoice:</strong> <?php echo h($row['invoice_no'] ?: 'Not generated yet'); ?></div>
                                <div><strong>Selected Group:</strong> <?php echo h($row['group_name'] ?: 'Not selected yet'); ?></div>
                                <?php $snapshotSummary = render_snapshot_summary($row['details_snapshot'] ?? ''); ?>
                                <?php if ($snapshotSummary !== ''): ?>
                                    <div class="meta mt-2"><strong>Payment Details Snapshot:</strong><br><?php echo nl2br(h($snapshotSummary)); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($row['note'])): ?>
                                    <div class="meta mt-2"><strong>User Note:</strong><br><?php echo nl2br(h($row['note'])); ?></div>
                                <?php endif; ?>

                                <div class="membership-box">
                                    <strong class="d-block mb-2">Current Group Subscription Rows</strong>
                                    <?php if (!empty($memberships)): ?>
                                        <?php foreach ($memberships as $membership): ?>
                                            <div class="membership-line">
                                                <?php echo h($membership['group_name'] ?? 'Group'); ?>
                                                |
                                                <?php echo h($membership['start_date'] ?: '-'); ?> to <?php echo h($membership['end_date'] ?: '-'); ?>
                                                |
                                                <?php echo intval($membership['is_active'] ?? 0) === 1 ? 'Active' : 'Expired'; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="membership-line">No group subscription row exists yet for this user.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="post" action="subscription_request_action.php" class="subscription-approval-form approval-actions mb-3" data-membership-meta="<?php echo h(json_encode($membershipMeta)); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="request_id" value="<?php echo intval($row['id']); ?>">
                                        <input type="hidden" name="return_url" value="<?php echo h($currentUrl); ?>">
                                        <label>Target Group For This Invoice</label>
                                        <select class="form-control mb-2 approval-group-select" name="group_id">
                                            <option value="0">Select group</option>
                                            <?php foreach ($groups as $group): ?>
                                                <option value="<?php echo intval($group['id']); ?>" <?php echo $selectedGroupId === intval($group['id']) ? 'selected' : ''; ?>>
                                                    <?php echo h($group['group_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="meta approve-state-text mb-2"></div>
                                        <label>Admin Note</label>
                                        <textarea class="form-control mb-2" name="admin_note" rows="3" placeholder="Optional note for approval"></textarea>
                                        <button class="btn btn-success approve-action-btn" type="submit" <?php echo empty($groups) ? 'disabled' : ''; ?>>
                                            <i class="fa fa-check"></i> Approve Subscription
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
                                    <div class="approval-actions meta">
                                        <?php if ($row['status'] === 'approved'): ?>
                                            <div><strong>Approved:</strong> <?php echo h($row['approved_at']); ?></div>
                                            <div><strong>Invoice Subscription:</strong> <?php echo h($row['subscription_start_date']); ?> to <?php echo h($row['subscription_end_date']); ?></div>
                                            <div><strong>Months Added:</strong> <?php echo intval($row['months_added']); ?></div>
                                            <div><strong>Approved By:</strong> <?php echo h($row['approved_by_name'] ?: ('Admin #' . intval($row['approved_by']))); ?></div>
                                            <?php if ($primaryMembership): ?>
                                                <div class="mt-2"><strong>Live Group Row:</strong> <?php echo h($primaryMembership['group_name'] ?? 'Group'); ?> | <?php echo h($primaryMembership['start_date'] ?: '-'); ?> to <?php echo h($primaryMembership['end_date'] ?: '-'); ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div><strong>Rejected:</strong> <?php echo h($row['rejected_at']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['admin_note'])): ?>
                                            <div class="mt-2"><strong>Admin Note:</strong><br><?php echo nl2br(h($row['admin_note'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.subscription-approval-form').forEach(function (form) {
        var select = form.querySelector('.approval-group-select');
        var button = form.querySelector('.approve-action-btn');
        var stateText = form.querySelector('.approve-state-text');
        var membershipMeta = [];

        try {
            membershipMeta = JSON.parse(form.dataset.membershipMeta || '[]');
        } catch (error) {
            membershipMeta = [];
        }

        function syncApprovalState() {
            var selectedGroupId = select ? select.value : '0';
            if (!button || !stateText) {
                return;
            }

            if (!selectedGroupId || selectedGroupId === '0') {
                button.disabled = true;
                button.innerHTML = '<i class="fa fa-check"></i> Select Group First';
                stateText.textContent = 'Choose the target group on this same row before approving the invoice.';
                return;
            }

            var selectedMembership = membershipMeta.find(function (item) {
                return String(item.group_id) === String(selectedGroupId);
            });

            button.disabled = false;
            if (selectedMembership) {
                button.innerHTML = '<i class="fa fa-plus-circle"></i> Increase Date + Approve';
                stateText.textContent = 'User is already in ' + selectedMembership.group_name + '. Approval will increase the date on that same group_members row.';
            } else {
                button.innerHTML = '<i class="fa fa-user-plus"></i> Add To Group + Approve';
                stateText.textContent = 'User is not in this group yet. Approval will first add the user to that group and then create the one-month subscription row.';
            }
        }

        if (select) {
            select.addEventListener('change', syncApprovalState);
            syncApprovalState();
        }
    });
});
</script>
</body>
</html>
<?php
if ($requestsStmt) {
    $requestsStmt->close();
}
$conn->close();
?>
