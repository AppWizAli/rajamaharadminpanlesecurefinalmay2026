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
        :root {
            --sub-accent: #f59300;
            --sub-accent-dark: #cf7c00;
            --sub-ink: #13254b;
            --sub-soft: #fff8ec;
            --sub-border: #e8edf4;
            --sub-muted: #667085;
            --sub-bg: #ffffff;
            --sub-panel: #f8fafc;
        }
        .subscription-card { border-radius: 18px; border: 1px solid var(--sub-border); box-shadow: 0 12px 35px rgba(15, 23, 42, 0.05); }
        .section-note { color: var(--sub-muted); font-size: 13px; line-height: 1.6; }
        .meta { font-size: 12px; color: var(--sub-muted); line-height: 1.7; }
        .badge-soft { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-pending { background: #fff4d6; color: #9a6700; }
        .badge-approved { background: #e7f6ec; color: #027a48; }
        .badge-rejected { background: #fde8e8; color: #b42318; }
        .settings-grid { display: grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 14px; }
        .filters-grid { display: grid; grid-template-columns: minmax(240px, 2fr) minmax(180px, 1fr); gap: 12px; }
        .sub-tabbar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .sub-tab {
            border: 1px solid var(--sub-border);
            background: #fff;
            color: var(--sub-ink);
            border-radius: 999px;
            padding: 10px 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .sub-tab.active {
            background: var(--sub-accent);
            border-color: var(--sub-accent);
            color: #111827;
            box-shadow: 0 10px 24px rgba(245, 147, 0, 0.25);
        }
        .sub-tabsection { display: none; }
        .sub-tabsection.active { display: block; }
        .sub-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 700;
            border: 1px solid transparent;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }
        .sub-btn-primary {
            background: var(--sub-accent);
            color: #111827 !important;
        }
        .sub-btn-primary:hover {
            background: var(--sub-accent-dark);
            color: #111827 !important;
        }
        .sub-btn-dark {
            background: var(--sub-ink);
            color: #fff !important;
        }
        .sub-btn-light {
            background: #fff;
            color: var(--sub-ink) !important;
            border-color: var(--sub-border);
        }
        .sub-btn-danger {
            background: #b42318;
            color: #fff !important;
        }
        .invoice-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .sub-hidden-template {
            display: none !important;
        }
        .invoice-row {
            display: grid;
            grid-template-columns: 78px minmax(220px, 1.4fr) minmax(130px, 0.75fr) minmax(150px, 0.9fr) minmax(180px, 1fr) auto;
            gap: 14px;
            align-items: center;
            background: var(--sub-bg);
            border: 1px solid var(--sub-border);
            border-radius: 18px;
            padding: 14px;
        }
        .invoice-row-thumb {
            width: 72px;
            height: 72px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid var(--sub-border);
            background: #f4f4f5;
        }
        .invoice-row-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--sub-ink);
            margin-top: 4px;
        }
        .invoice-row-main strong,
        .invoice-row-summary strong,
        .invoice-row-live strong {
            color: var(--sub-ink);
        }
        .invoice-row-top {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .invoice-row-summary-label {
            display: inline-block;
            color: var(--sub-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .invoice-row-summary-value {
            color: var(--sub-ink);
            font-size: 15px;
            font-weight: 700;
            line-height: 1.35;
        }
        .invoice-row-live {
            font-size: 13px;
            line-height: 1.6;
            color: var(--sub-muted);
        }
        .invoice-row-live strong {
            display: block;
            margin-bottom: 2px;
            font-size: 11px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .invoice-row-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 160px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }
        .detail-screenshot {
            width: 100%;
            border-radius: 16px;
            border: 1px solid var(--sub-border);
        }
        .detail-box {
            background: var(--sub-panel);
            border: 1px solid var(--sub-border);
            border-radius: 16px;
            padding: 14px;
            margin-top: 14px;
        }
        .detail-box-title {
            color: var(--sub-ink);
            font-weight: 800;
            margin-bottom: 8px;
        }
        .detail-membership-line {
            font-size: 13px;
            color: #344054;
            line-height: 1.7;
            margin-bottom: 6px;
        }
        .detail-membership-line:last-child { margin-bottom: 0; }
        .approve-state-text { min-height: 20px; }
        #invoiceDetailModal .subscription-detail-dialog {
            width: min(1520px, calc(100vw - 40px)) !important;
            max-width: min(1520px, calc(100vw - 40px)) !important;
            margin: 1rem auto;
        }
        .modal-content {
            border-radius: 22px;
            border: 0;
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(135deg, #13254b 0%, #1f3c76 100%);
            color: #fff;
            border-bottom: 0;
        }
        .modal-header .close {
            color: #fff;
            opacity: 0.9;
        }
        .modal-body {
            background: #fff;
            padding: 22px;
            overflow-x: hidden;
        }
        .request-detail-layout {
            display: grid;
            grid-template-columns: minmax(320px, 380px) minmax(680px, 1fr);
            gap: 22px;
            align-items: start;
        }
        .request-media-panel {
            position: sticky;
            top: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .request-main-panel {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }
        .request-top-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }
        .request-stat-card {
            border: 1px solid var(--sub-border);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 14px;
            min-height: 96px;
        }
        .request-stat-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--sub-muted);
            margin-bottom: 6px;
        }
        .request-stat-value {
            color: var(--sub-ink);
            font-size: 18px;
            font-weight: 800;
            line-height: 1.35;
        }
        .request-stat-sub {
            margin-top: 4px;
            color: var(--sub-muted);
            font-size: 12px;
            line-height: 1.5;
        }
        .request-panel {
            border: 1px solid var(--sub-border);
            border-radius: 18px;
            background: #fff;
            overflow: hidden;
        }
        .request-panel-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--sub-border);
            background: #fbfcfe;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .request-panel-title {
            color: var(--sub-ink);
            font-size: 16px;
            font-weight: 800;
            margin: 0;
        }
        .request-panel-body {
            padding: 16px;
        }
        .request-overview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 16px;
        }
        .request-overview-item {
            min-width: 0;
        }
        .request-overview-item strong {
            display: block;
            color: var(--sub-ink);
            margin-bottom: 4px;
        }
        .request-overview-item span,
        .request-overview-item div {
            color: #344054;
            line-height: 1.6;
            word-break: break-word;
        }
        .request-actions-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(280px, 0.9fr);
            gap: 16px;
        }
        .request-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .request-form-grid .request-form-span-2 {
            grid-column: 1 / -1;
        }
        .request-live-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
        }
        .request-live-status.active {
            background: #e7f6ec;
            color: #027a48;
        }
        .request-live-status.inactive {
            background: #fff4d6;
            color: #9a6700;
        }
        .live-membership-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .live-membership-item {
            border: 1px solid var(--sub-border);
            border-radius: 14px;
            padding: 12px 14px;
            background: var(--sub-panel);
        }
        .live-membership-item-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 6px;
        }
        .live-membership-name {
            color: var(--sub-ink);
            font-weight: 800;
        }
        .live-membership-date {
            color: #344054;
            font-size: 13px;
            line-height: 1.6;
        }
        .live-membership-tools {
            display: grid;
            grid-template-columns: 170px 1fr 1fr auto;
            gap: 10px;
            margin-top: 12px;
            align-items: end;
        }
        .live-membership-tools .form-control {
            min-height: 42px;
        }
        .live-membership-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .membership-action-feedback {
            margin-top: 10px;
            font-size: 12px;
            color: var(--sub-muted);
            min-height: 18px;
        }
        .membership-action-feedback.error {
            color: #b42318;
        }
        .membership-action-feedback.success {
            color: #027a48;
        }
        .request-reject-box textarea,
        .request-main-panel textarea {
            resize: vertical;
        }
        .invoice-row-live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }
        .invoice-row-live-badge.active {
            background: #e7f6ec;
            color: #027a48;
        }
        .invoice-row-live-badge.inactive {
            background: #fff4d6;
            color: #9a6700;
        }
        @media (max-width: 768px) {
            .filters-grid, .settings-grid, .detail-grid { grid-template-columns: 1fr; }
            .invoice-row { grid-template-columns: 1fr; justify-items: start; }
            .invoice-row-actions { justify-content: flex-start; }
            .subscription-detail-dialog {
                max-width: calc(100vw - 16px);
                margin: 0.5rem auto;
            }
            .request-detail-layout,
            .request-top-summary,
            .request-overview-grid,
            .request-actions-grid,
            .request-form-grid,
            .live-membership-tools {
                grid-template-columns: 1fr;
            }
            .request-media-panel {
                position: static;
            }
            .live-membership-actions {
                justify-content: stretch;
            }
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
                        <small>Separate payment account setup from invoice handling, and manage subscriptions with the same group rows used across the panel.</small>
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

        <div class="sub-tabbar">
            <button type="button" class="sub-tab active" data-sub-tab="settingsSection">Payment Details</button>
            <button type="button" class="sub-tab" data-sub-tab="invoicesSection">Invoices & Requests</button>
        </div>

        <div id="settingsSection" class="sub-tabsection active">
            <div class="pd-20 card-box subscription-card mb-30">
                <h5 class="mb-1">Payment Account Settings</h5>
                <div class="section-note mb-3">Only monthly amount and account details live here. Group assignment and subscription actions are handled per invoice inside the detail popup.</div>
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
                    <button class="sub-btn sub-btn-primary mt-3" type="submit">
                        <i class="fa fa-save"></i> Save Payment Details
                    </button>
                </form>
            </div>
        </div>

        <div id="invoicesSection" class="sub-tabsection">
            <div class="pd-20 card-box subscription-card mb-30">
                <h5 class="mb-1">Invoices & Subscription Management</h5>
                <div class="section-note mb-3">Each row stays compact. Open details to see notes, payment snapshot, screenshot, current group rows, and all add/increase/change-group actions.</div>
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
                    <button class="sub-btn sub-btn-light mt-3" type="submit">
                        <i class="fa fa-filter"></i> Filter Requests
                    </button>
                </form>
            </div>

            <div class="pd-20 card-box subscription-card mb-30">
                <h5 class="mb-3">Sent Invoices & Requests</h5>
                <?php if (!empty($requestRows)): ?>
                    <div class="invoice-list">
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
                            $hasActiveMembership = false;
                            foreach ($membershipMeta as $membershipInfo) {
                                if (intval($membershipInfo['is_active'] ?? 0) === 1) {
                                    $hasActiveMembership = true;
                                    break;
                                }
                            }
                            $liveSummary = $primaryMembership
                                ? (($primaryMembership['group_name'] ?? 'Group') . ' | ' . ($primaryMembership['end_date'] ?: '-') . ' | ' . (intval($primaryMembership['is_active'] ?? 0) === 1 ? 'Active' : 'Expired'))
                                : 'No group row yet';
                            $snapshotSummary = render_snapshot_summary($row['details_snapshot'] ?? '');
                            ?>
                            <div class="invoice-row" data-request-id="<?php echo intval($row['id']); ?>" data-request-user-id="<?php echo $userId; ?>">
                                <div>
                                    <?php if (!empty($row['screenshot_url'])): ?>
                                        <img src="<?php echo h($row['screenshot_url']); ?>" alt="Payment screenshot" class="invoice-row-thumb">
                                    <?php else: ?>
                                        <div class="invoice-row-thumb d-flex align-items-center justify-content-center text-muted">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="invoice-row-main">
                                    <div class="invoice-row-top">
                                        <span class="badge-soft badge-<?php echo h($statusClass); ?>"><?php echo h(strtoupper($row['status'])); ?></span>
                                        <span class="meta">#<?php echo intval($row['id']); ?></span>
                                    </div>
                                    <div class="invoice-row-title"><?php echo h($row['username'] ?? 'Unknown User'); ?></div>
                                    <div class="meta">User #<?php echo $userId; ?> | <?php echo h($row['email'] ?? ''); ?></div>
                                </div>
                                <div class="invoice-row-summary">
                                    <span class="invoice-row-summary-label">Amount</span>
                                    <div class="invoice-row-summary-value"><?php echo h($row['amount']); ?> <?php echo h($row['currency']); ?></div>
                                    <div class="meta"><?php echo h($row['payment_method'] ?: 'Not specified'); ?></div>
                                </div>
                                <div class="invoice-row-summary">
                                    <span class="invoice-row-summary-label">Invoice</span>
                                    <div class="invoice-row-summary-value"><?php echo h($row['invoice_no'] ?: ('Request #' . intval($row['id']))); ?></div>
                                    <div class="meta"><?php echo h($row['created_at']); ?></div>
                                </div>
                                <div class="invoice-row-live">
                                    <div><strong>Live Row</strong></div>
                                    <div class="js-live-row-summary"><?php echo h($liveSummary); ?></div>
                                    <div class="invoice-row-live-badge js-live-row-badge <?php echo $hasActiveMembership ? 'active' : 'inactive'; ?>">
                                        <?php echo $hasActiveMembership ? 'Subscription Active' : 'No Active Subscription'; ?>
                                    </div>
                                </div>
                                <div class="invoice-row-actions">
                                    <button
                                        type="button"
                                        class="sub-btn sub-btn-primary js-view-request"
                                        data-title="<?php echo h(($row['invoice_no'] ?: ('Request #' . intval($row['id']))) . ' - ' . ($row['username'] ?? 'Unknown')); ?>"
                                        data-target="#requestDetailTemplate<?php echo intval($row['id']); ?>"
                                        data-request-id="<?php echo intval($row['id']); ?>"
                                        data-user-id="<?php echo $userId; ?>">
                                        <i class="fa fa-eye"></i> View Details
                                    </button>
                                </div>
                            </div>

                            <div id="requestDetailTemplate<?php echo intval($row['id']); ?>" class="sub-hidden-template">
                                <div class="request-detail-layout" data-request-id="<?php echo intval($row['id']); ?>" data-user-id="<?php echo $userId; ?>">
                                    <div class="request-media-panel">
                                        <div class="request-panel">
                                            <div class="request-panel-head">
                                                <h6 class="request-panel-title mb-0">Payment Proof</h6>
                                                <?php if (!empty($row['screenshot_url'])): ?>
                                                    <a href="<?php echo h($row['screenshot_url']); ?>" target="_blank" class="sub-btn sub-btn-light">
                                                        <i class="fa fa-image"></i> Open
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="request-panel-body">
                                                <?php if (!empty($row['screenshot_url'])): ?>
                                                    <a href="<?php echo h($row['screenshot_url']); ?>" target="_blank">
                                                        <img src="<?php echo h($row['screenshot_url']); ?>" alt="Payment screenshot" class="detail-screenshot">
                                                    </a>
                                                <?php else: ?>
                                                    <div class="detail-box text-muted text-center mb-0">No screenshot uploaded.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($snapshotSummary !== ''): ?>
                                            <div class="request-panel">
                                                <div class="request-panel-head">
                                                    <h6 class="request-panel-title mb-0">Payment Details Snapshot</h6>
                                                </div>
                                                <div class="request-panel-body">
                                                    <div class="meta"><?php echo nl2br(h($snapshotSummary)); ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['note'])): ?>
                                            <div class="request-panel">
                                                <div class="request-panel-head">
                                                    <h6 class="request-panel-title mb-0">User Note</h6>
                                                </div>
                                                <div class="request-panel-body">
                                                    <div class="meta"><?php echo nl2br(h($row['note'])); ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="request-main-panel">
                                        <div class="request-top-summary">
                                            <div class="request-stat-card">
                                                <div class="request-stat-label">Status</div>
                                                <div class="request-stat-value"><span class="badge-soft badge-<?php echo h($statusClass); ?>"><?php echo h(strtoupper($row['status'])); ?></span></div>
                                                <div class="request-stat-sub">Request #<?php echo intval($row['id']); ?></div>
                                            </div>
                                            <div class="request-stat-card">
                                                <div class="request-stat-label">User</div>
                                                <div class="request-stat-value">#<?php echo $userId; ?></div>
                                                <div class="request-stat-sub"><?php echo h($row['username'] ?? 'Unknown'); ?><?php echo !empty($row['email']) ? ' | ' . h($row['email']) : ''; ?></div>
                                            </div>
                                            <div class="request-stat-card">
                                                <div class="request-stat-label">Amount</div>
                                                <div class="request-stat-value"><?php echo h($row['amount']); ?> <?php echo h($row['currency']); ?></div>
                                                <div class="request-stat-sub"><?php echo h($row['payment_method'] ?: 'Not specified'); ?></div>
                                            </div>
                                            <div class="request-stat-card">
                                                <div class="request-stat-label">Live Subscription</div>
                                                <div class="request-stat-value">
                                                    <span class="request-live-status js-live-status-badge <?php echo $hasActiveMembership ? 'active' : 'inactive'; ?>">
                                                        <?php echo $hasActiveMembership ? 'Subscription Active' : 'No Active Subscription'; ?>
                                                    </span>
                                                </div>
                                                <div class="request-stat-sub js-live-primary-summary"><?php echo h($liveSummary); ?></div>
                                            </div>
                                        </div>

                                        <div class="request-panel">
                                            <div class="request-panel-head">
                                                <h6 class="request-panel-title mb-0">Request Overview</h6>
                                            </div>
                                            <div class="request-panel-body">
                                                <div class="request-overview-grid">
                                                    <div class="request-overview-item">
                                                        <strong>Requested At</strong>
                                                        <div><?php echo h($row['created_at']); ?></div>
                                                    </div>
                                                    <div class="request-overview-item">
                                                        <strong>Invoice Number</strong>
                                                        <div><?php echo h($row['invoice_no'] ?: 'Not generated yet'); ?></div>
                                                    </div>
                                                    <div class="request-overview-item">
                                                        <strong>Selected Group In Request</strong>
                                                        <div><?php echo h($row['group_name'] ?: 'Not selected yet'); ?></div>
                                                    </div>
                                                    <div class="request-overview-item">
                                                        <strong>Method</strong>
                                                        <div><?php echo h($row['payment_method'] ?: 'Not specified'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="request-panel">
                                            <div class="request-panel-head">
                                                <h6 class="request-panel-title mb-0">Current Group Subscription Rows</h6>
                                                <span class="meta">Live sync from group rows</span>
                                            </div>
                                            <div class="request-panel-body">
                                                <div class="live-membership-list js-live-memberships">
                                                    <?php if (!empty($memberships)): ?>
                                                        <?php foreach ($memberships as $membership): ?>
                                                            <div class="live-membership-item">
                                                                <div class="live-membership-item-top">
                                                                    <div class="live-membership-name"><?php echo h($membership['group_name'] ?? 'Group'); ?></div>
                                                                    <span class="badge-soft badge-<?php echo intval($membership['is_active'] ?? 0) === 1 ? 'approved' : 'pending'; ?>">
                                                                        <?php echo intval($membership['is_active'] ?? 0) === 1 ? 'ACTIVE' : 'EXPIRED'; ?>
                                                                    </span>
                                                                </div>
                                                                <div class="live-membership-date"><?php echo h($membership['start_date'] ?: '-'); ?> to <?php echo h($membership['end_date'] ?: '-'); ?></div>
                                                                <form class="js-membership-form live-membership-tools" data-user-id="<?php echo $userId; ?>" data-group-id="<?php echo intval($membership['group_id']); ?>" data-request-id="<?php echo intval($row['id']); ?>">
                                                                    <div>
                                                                        <label>Status</label>
                                                                        <select class="form-control" name="membership_status">
                                                                            <option value="active" <?php echo intval($membership['is_active'] ?? 0) === 1 ? 'selected' : ''; ?>>Active</option>
                                                                            <option value="inactive" <?php echo intval($membership['is_active'] ?? 0) === 1 ? '' : 'selected'; ?>>Inactive</option>
                                                                        </select>
                                                                    </div>
                                                                    <div>
                                                                        <label>Start Date</label>
                                                                        <input type="date" class="form-control" name="start_date" value="<?php echo h($membership['start_date'] ?: ''); ?>">
                                                                    </div>
                                                                    <div>
                                                                        <label>End Date</label>
                                                                        <input type="date" class="form-control" name="end_date" value="<?php echo h($membership['end_date'] ?: ''); ?>">
                                                                    </div>
                                                                    <div class="live-membership-actions">
                                                                        <button type="button" class="sub-btn sub-btn-light js-membership-increase">
                                                                            <i class="fa fa-plus-circle"></i> +31 Days
                                                                        </button>
                                                                        <button type="submit" class="sub-btn sub-btn-primary">
                                                                            <i class="fa fa-save"></i> Save
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                                <div class="membership-action-feedback"></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="detail-membership-line text-muted">No group subscription row exists yet for this user.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($row['status'] === 'pending'): ?>
                                            <div class="request-actions-grid">
                                                <div class="request-panel">
                                                    <div class="request-panel-head">
                                                        <h6 class="request-panel-title mb-0">Assign Group Subscription</h6>
                                                        <span class="meta">Same style as Add User to Group</span>
                                                    </div>
                                                    <div class="request-panel-body">
                                                        <form method="post" action="subscription_request_action.php" class="subscription-approval-form" data-membership-meta="<?php echo h(json_encode($membershipMeta)); ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="request_id" value="<?php echo intval($row['id']); ?>">
                                                            <input type="hidden" name="return_url" value="<?php echo h($currentUrl); ?>">
                                                            <div class="request-form-grid">
                                                                <div>
                                                                    <label>Select Group</label>
                                                                    <select class="form-control approval-group-select" name="group_id" required>
                                                                        <option value="0">Select group</option>
                                                                        <?php foreach ($groups as $group): ?>
                                                                            <option value="<?php echo intval($group['id']); ?>" <?php echo $selectedGroupId === intval($group['id']) ? 'selected' : ''; ?>>
                                                                                <?php echo h($group['group_name']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label>Subscription End Date</label>
                                                                    <input type="date" class="form-control" name="subscription_end_date" min="<?php echo date('Y-m-d'); ?>">
                                                                    <small class="text-muted">Leave empty to add 31 days automatically from today or the active expiry date.</small>
                                                                </div>
                                                                <div class="request-form-span-2">
                                                                    <div class="meta approve-state-text mb-2"></div>
                                                                </div>
                                                                <div class="request-form-span-2">
                                                                    <label>Admin Note</label>
                                                                    <textarea class="form-control mb-3" name="admin_note" rows="3" placeholder="Optional note for approval"></textarea>
                                                                </div>
                                                                <div class="request-form-span-2">
                                                                    <button class="sub-btn sub-btn-primary approve-action-btn" type="submit" <?php echo empty($groups) ? 'disabled' : ''; ?>>
                                                                        <i class="fa fa-check"></i> Assign Subscription
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                                <div class="request-panel request-reject-box">
                                                    <div class="request-panel-head">
                                                        <h6 class="request-panel-title mb-0">Reject With Note</h6>
                                                    </div>
                                                    <div class="request-panel-body">
                                                        <form method="post" action="subscription_request_action.php">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="request_id" value="<?php echo intval($row['id']); ?>">
                                                            <input type="hidden" name="return_url" value="<?php echo h($currentUrl); ?>">
                                                            <label>Reject Note</label>
                                                            <textarea class="form-control mb-3" name="admin_note" rows="6" placeholder="Write why this request is rejected" required></textarea>
                                                            <button class="sub-btn sub-btn-danger" type="submit">
                                                                <i class="fa fa-times"></i> Reject Request
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="request-panel">
                                                <div class="request-panel-head">
                                                    <h6 class="request-panel-title mb-0">Subscription Result</h6>
                                                </div>
                                                <div class="request-panel-body">
                                                    <div class="meta">
                                                        <?php if ($row['status'] === 'approved'): ?>
                                                            Approved: <?php echo h($row['approved_at']); ?><br>
                                                            Invoice Subscription: <?php echo h($row['subscription_start_date']); ?> to <?php echo h($row['subscription_end_date']); ?><br>
                                                            Months Added: <?php echo intval($row['months_added']); ?><br>
                                                            Approved By: <?php echo h($row['approved_by_name'] ?: ('Admin #' . intval($row['approved_by']))); ?><br>
                                                            <?php if ($primaryMembership): ?>
                                                                Live Group Row: <?php echo h($primaryMembership['group_name'] ?? 'Group'); ?> | <?php echo h($primaryMembership['start_date'] ?: '-'); ?> to <?php echo h($primaryMembership['end_date'] ?: '-'); ?>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            Rejected: <?php echo h($row['rejected_at']); ?><br>
                                                            Live Status: <span class="js-live-primary-summary"><?php echo h($liveSummary); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($row['admin_note'])): ?>
                                                            <br><br>Admin Note:<br><?php echo nl2br(h($row['admin_note'])); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted">No subscription requests found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="invoiceDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable subscription-detail-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script>
function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderLiveMembershipRows(memberships, userId, requestId) {
    if (!Array.isArray(memberships) || memberships.length === 0) {
        return '<div class="detail-membership-line text-muted">No group subscription row exists yet for this user.</div>';
    }

    return memberships.map(function (membership) {
        var isActive = Number(membership.is_active || 0) === 1;
        return '' +
            '<div class="live-membership-item">' +
                '<div class="live-membership-item-top">' +
                    '<div class="live-membership-name">' + escapeHtml(membership.group_name || 'Group') + '</div>' +
                    '<span class="badge-soft badge-' + (isActive ? 'approved' : 'pending') + '">' + (isActive ? 'ACTIVE' : 'EXPIRED') + '</span>' +
                '</div>' +
                '<div class="live-membership-date">' + escapeHtml(membership.start_date || '-') + ' to ' + escapeHtml(membership.end_date || '-') + '</div>' +
                '<form class="js-membership-form live-membership-tools" data-user-id="' + escapeHtml(userId) + '" data-group-id="' + escapeHtml(Number(membership.group_id || 0)) + '" data-request-id="' + escapeHtml(requestId) + '">' +
                    '<div>' +
                        '<label>Status</label>' +
                        '<select class="form-control" name="membership_status">' +
                            '<option value="active"' + (isActive ? ' selected' : '') + '>Active</option>' +
                            '<option value="inactive"' + (!isActive ? ' selected' : '') + '>Inactive</option>' +
                        '</select>' +
                    '</div>' +
                    '<div>' +
                        '<label>Start Date</label>' +
                        '<input type="date" class="form-control" name="start_date" value="' + escapeHtml(membership.start_date || '') + '">' +
                    '</div>' +
                    '<div>' +
                        '<label>End Date</label>' +
                        '<input type="date" class="form-control" name="end_date" value="' + escapeHtml(membership.end_date || '') + '">' +
                    '</div>' +
                    '<div class="live-membership-actions">' +
                        '<button type="button" class="sub-btn sub-btn-light js-membership-increase"><i class="fa fa-plus-circle"></i> +31 Days</button>' +
                        '<button type="submit" class="sub-btn sub-btn-primary"><i class="fa fa-save"></i> Save</button>' +
                    '</div>' +
                '</form>' +
                '<div class="membership-action-feedback"></div>' +
            '</div>';
    }).join('');
}

function buildLiveSummary(memberships) {
    if (!Array.isArray(memberships) || memberships.length === 0) {
        return 'No group row yet';
    }

    var firstMembership = memberships[0];
    var statusText = Number(firstMembership.is_active || 0) === 1 ? 'Active' : 'Expired';
    return (firstMembership.group_name || 'Group') + ' | ' + (firstMembership.end_date || '-') + ' | ' + statusText;
}

function updateLiveStatusUI(container, memberships, userId, requestId) {
    if (!container) {
        return;
    }

    var hasActiveMembership = Array.isArray(memberships) && memberships.some(function (membership) {
        return Number(membership.is_active || 0) === 1;
    });
    var summary = buildLiveSummary(memberships);

    container.querySelectorAll('.js-live-memberships').forEach(function (node) {
        node.innerHTML = renderLiveMembershipRows(memberships, userId, requestId);
    });

    container.querySelectorAll('.js-live-primary-summary').forEach(function (node) {
        node.textContent = summary;
    });

    container.querySelectorAll('.js-live-status-badge').forEach(function (node) {
        node.textContent = hasActiveMembership ? 'Subscription Active' : 'No Active Subscription';
        node.classList.remove('active', 'inactive');
        node.classList.add(hasActiveMembership ? 'active' : 'inactive');
    });
}

function updateInvoiceRowLiveState(requestId, memberships) {
    var row = document.querySelector('.invoice-row[data-request-id="' + requestId + '"]');
    if (!row) {
        return;
    }

    var summary = buildLiveSummary(memberships);
    var hasActiveMembership = Array.isArray(memberships) && memberships.some(function (membership) {
        return Number(membership.is_active || 0) === 1;
    });
    var summaryNode = row.querySelector('.js-live-row-summary');
    var badgeNode = row.querySelector('.js-live-row-badge');

    if (summaryNode) {
        summaryNode.textContent = summary;
    }
    if (badgeNode) {
        badgeNode.textContent = hasActiveMembership ? 'Subscription Active' : 'No Active Subscription';
        badgeNode.classList.remove('active', 'inactive');
        badgeNode.classList.add(hasActiveMembership ? 'active' : 'inactive');
    }
}

function refreshRequestLiveData(modal, userId, requestId) {
    if (!modal || !userId) {
        return;
    }

    fetch('get_subscription_overview.php?user_id=' + encodeURIComponent(userId), {
        credentials: 'same-origin'
    })
        .then(function (response) {
            return response.json();
        })
        .then(function (payload) {
            if (!payload || payload.status !== true) {
                return;
            }

            var memberships = Array.isArray(payload.current_subscriptions) ? payload.current_subscriptions : [];
            updateLiveStatusUI(modal, memberships, userId, requestId);
            updateInvoiceRowLiveState(requestId, memberships);
            initializeMembershipActions(modal);

            var approvalForm = modal.querySelector('.subscription-approval-form');
            if (approvalForm) {
                approvalForm.dataset.membershipMeta = JSON.stringify(memberships.map(function (membership) {
                    return {
                        group_id: Number(membership.group_id || 0),
                        group_name: membership.group_name || 'Group',
                        start_date: membership.start_date || '',
                        end_date: membership.end_date || '',
                        is_active: Number(membership.is_active || 0)
                    };
                }));

                var select = approvalForm.querySelector('.approval-group-select');
                if (select) {
                    select.dispatchEvent(new Event('change'));
                }
            }
        })
        .catch(function () {
        });
}

function setMembershipFeedback(form, message, type) {
    if (!form) {
        return;
    }
    var feedback = form.parentElement ? form.parentElement.querySelector('.membership-action-feedback') : null;
    if (!feedback) {
        return;
    }
    feedback.textContent = message || '';
    feedback.classList.remove('error', 'success');
    if (type) {
        feedback.classList.add(type);
    }
}

function initializeMembershipActions(scope) {
    (scope || document).querySelectorAll('.js-membership-form').forEach(function (form) {
        if (form.dataset.initialized === '1') {
            return;
        }
        form.dataset.initialized = '1';

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var modal = form.closest('#invoiceDetailModal');
            var requestId = form.dataset.requestId || '';
            var userId = form.dataset.userId || '';
            var groupId = form.dataset.groupId || '';
            var formData = new FormData(form);
            formData.append('action', 'save_membership');
            formData.append('user_id', userId);
            formData.append('group_id', groupId);

            setMembershipFeedback(form, 'Saving subscription row...', '');

            fetch('subscription_membership_action.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (!payload || payload.success !== true) {
                        setMembershipFeedback(form, (payload && payload.message) ? payload.message : 'Unable to save this subscription row.', 'error');
                        return;
                    }
                    setMembershipFeedback(form, payload.message || 'Subscription row updated.', 'success');
                    refreshRequestLiveData(modal, userId, requestId);
                })
                .catch(function () {
                    setMembershipFeedback(form, 'Unable to save this subscription row right now.', 'error');
                });
        });

        var increaseButton = form.querySelector('.js-membership-increase');
        if (increaseButton) {
            increaseButton.addEventListener('click', function () {
                var modal = form.closest('#invoiceDetailModal');
                var requestId = form.dataset.requestId || '';
                var userId = form.dataset.userId || '';
                var groupId = form.dataset.groupId || '';
                var formData = new FormData();
                formData.append('action', 'increase_membership');
                formData.append('user_id', userId);
                formData.append('group_id', groupId);

                setMembershipFeedback(form, 'Increasing date by 31 days...', '');

                fetch('subscription_membership_action.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        if (!payload || payload.success !== true) {
                            setMembershipFeedback(form, (payload && payload.message) ? payload.message : 'Unable to increase date right now.', 'error');
                            return;
                        }
                        setMembershipFeedback(form, payload.message || 'Subscription date increased.', 'success');
                        refreshRequestLiveData(modal, userId, requestId);
                    })
                    .catch(function () {
                        setMembershipFeedback(form, 'Unable to increase date right now.', 'error');
                    });
            });
        }
    });
}

function initializeApprovalForms(scope) {
    (scope || document).querySelectorAll('.subscription-approval-form').forEach(function (form) {
        if (form.dataset.initialized === '1') {
            return;
        }
        form.dataset.initialized = '1';

        var select = form.querySelector('.approval-group-select');
        var button = form.querySelector('.approve-action-btn');
        var stateText = form.querySelector('.approve-state-text');

        function syncApprovalState() {
            var membershipMeta = [];
            var selectedGroupId = select ? select.value : '0';
            if (!button || !stateText) {
                return;
            }

            try {
                membershipMeta = JSON.parse(form.dataset.membershipMeta || '[]');
            } catch (error) {
                membershipMeta = [];
            }

            if (!selectedGroupId || selectedGroupId === '0') {
                button.disabled = true;
                button.innerHTML = '<i class="fa fa-check"></i> Select Group First';
                stateText.textContent = 'Choose the target group in this popup before approving the invoice.';
                return;
            }

            var selectedMembership = membershipMeta.find(function (item) {
                return String(item.group_id) === String(selectedGroupId);
            });

            button.disabled = false;
            if (selectedMembership) {
                button.innerHTML = '<i class="fa fa-plus-circle"></i> Increase Date + Approve';
                stateText.textContent = 'User is already in ' + selectedMembership.group_name + '. Approval will increase the date on the same subscription row unless you choose a custom end date.';
            } else {
                button.innerHTML = '<i class="fa fa-user-plus"></i> Add To Group + Approve';
                stateText.textContent = 'User is not in this group yet. Approval will add the user to this group and assign the selected subscription date.';
            }
        }

        if (select) {
            select.addEventListener('change', syncApprovalState);
            syncApprovalState();
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sub-tab').forEach(function (tabButton) {
        tabButton.addEventListener('click', function () {
            var targetId = tabButton.dataset.subTab;
            document.querySelectorAll('.sub-tab').forEach(function (button) {
                button.classList.toggle('active', button === tabButton);
            });
            document.querySelectorAll('.sub-tabsection').forEach(function (section) {
                section.classList.toggle('active', section.id === targetId);
            });
        });
    });

    initializeApprovalForms(document);

    document.querySelectorAll('.js-view-request').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetSelector = button.dataset.target;
            var modal = document.getElementById('invoiceDetailModal');
            var template = targetSelector ? document.querySelector(targetSelector) : null;
            if (!modal || !template) {
                return;
            }

            modal.querySelector('.modal-title').textContent = button.dataset.title || 'Invoice Details';
            modal.querySelector('.modal-body').innerHTML = template.innerHTML;
            initializeApprovalForms(modal);
            initializeMembershipActions(modal);
            refreshRequestLiveData(modal, button.dataset.userId, button.dataset.requestId);
            if (window.jQuery) {
                window.jQuery(modal).modal('show');
            }
        });
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
