<?php
// Start session and include your database connection file
session_start();
// Set default timezone offset to +5 hours (Pakistan Time)
date_default_timezone_set('Asia/Karachi');

include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get video_id from query parameter
$video_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : 0;

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

// Search and time filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Validate and format dates
$start_date_sql = !empty($start_date) ? date('Y-m-d H:i:s', strtotime($start_date)) : '';
$end_date_sql = !empty($end_date) ? date('Y-m-d H:i:s', strtotime($end_date)) : '';

$users = [];
$total_records = 0;
$recent_users = [];

if ($video_id > 0) {
    try {
        // Count total unique users for pagination
        $sql_count = "SELECT COUNT(DISTINCT u.id) as total
                      FROM video_views v
                      INNER JOIN users u ON v.user_id = u.id
                      WHERE v.video_id = ? AND (u.id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR v.view_time LIKE ?)";
        $params = [$video_id, "%$search%", "%$search%", "%$search%", "%$search%"];
        $types = "issss";

        if (!empty($start_date_sql) && !empty($end_date_sql)) {
            $sql_count .= " AND v.view_time BETWEEN ? AND ?";
            $params[] = $start_date_sql;
            $params[] = $end_date_sql;
            $types .= "ss";
        }

        $stmt_count = $conn->prepare($sql_count);
        if ($stmt_count === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt_count->bind_param($types, ...$params);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $total_records = $result_count->fetch_assoc()['total'];
        $stmt_count->close();

        // Fetch unique users with their view times
        $sql = "SELECT DISTINCT u.id, u.username, u.email, GROUP_CONCAT(v.view_time ORDER BY v.view_time SEPARATOR ', ') as view_times
                FROM video_views v
                INNER JOIN users u ON v.user_id = u.id
                WHERE v.video_id = ? AND (u.id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR v.view_time LIKE ?)";
        $params = [$video_id, "%$search%", "%$search%", "%$search%", "%$search%"];

        if (!empty($start_date_sql) && !empty($end_date_sql)) {
            $sql .= " AND v.view_time BETWEEN ? AND ?";
            $params[] = $start_date_sql;
            $params[] = $end_date_sql;
        }
        $sql .= " GROUP BY u.id, u.username, u.email LIMIT ? OFFSET ?";
        $params[] = $records_per_page;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $types .= "ii";
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['view_times'] = explode(',', $row['view_times']);
                $users[] = $row;
            }
        }
        $stmt->close();

        // Fetch recently viewed users (top 5)
        $sql_recent = "SELECT DISTINCT u.id, u.username, u.email, v.view_time
                       FROM video_views v
                       INNER JOIN users u ON v.user_id = u.id
                       WHERE v.video_id = ?
                       ORDER BY v.view_time DESC
                       LIMIT 5";
        $stmt_recent = $conn->prepare($sql_recent);
        if ($stmt_recent === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt_recent->bind_param("i", $video_id);
        $stmt_recent->execute();
        $result_recent = $stmt_recent->get_result();
        if ($result_recent->num_rows > 0) {
            while ($row = $result_recent->fetch_assoc()) {
                $recent_users[] = $row;
            }
        }
        $stmt_recent->close();
    } catch (Exception $e) {
        error_log("Error in view_users.php: " . $e->getMessage());
        // Do not display error to user, just proceed with empty data
    }
}

$total_pages = ceil($total_records / $records_per_page);
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<!-- Basic Page Info -->
<meta charset="utf-8">
<title>Admin Panel - Users Who Viewed Video ID <?php echo htmlspecialchars($video_id); ?></title>
<!-- Site favicon -->
<link rel="apple-touch-icon" sizes="180x180" href="vendors/images/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="vendors/images/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="vendors/images/favicon-16x16.png">
<!-- Mobile Specific Metas -->
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<!-- CSS -->
<link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
<link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.3/jquery-jvectormap.css" />
<link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
<link rel="stylesheet" href="vendors/styles/mycss/Dashboard.css">
<link rel="stylesheet" href="vendors/styles/css/custom.css">
<!-- Google Material Icon -->
<link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
<!-- Bootstrap Modal CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<!-- Custom CSS for Professional Design -->
<style>
body {
    background: #f4f6f9;
    color: #333;
}
.page-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
}
.dashboard-header {
    background: #007bff;
    color: #fff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.page-title h4 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #fff;
}
.page-title p {
    font-size: 0.9rem;
    color: #e9ecef;
}
.breadcrumb a {
    color: #dee2e6;
}
.breadcrumb .active {
    color: #fff;
}
.card-box {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    padding: 15px;
    border-left: 4px solid #007bff;
}
.card-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}
.card-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    width: 120px;
}
.card-value {
    font-size: 0.9rem;
    font-weight: 500;
    color: #212529;
    word-break: break-word;
}
.card-text {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}
.empty-state {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    color: #6c757d;
    font-size: 1.1rem;
    border: 1px solid #e9ecef;
}
.filter-container {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.filter-container input, .filter-container select {
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 0.95rem;
    border: 1px solid #ced4da;
    min-width: 200px;
}
.filter-container button {
    background: #007bff;
    color: #fff;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
}
.filter-container button:hover {
    background: #0056b3;
}
.pagination {
    margin-top: 20px;
}
.pagination .page-item .page-link {
    color: #007bff;
    border: 1px solid #dee2e6;
}
.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
    color: #fff;
}
.recent-users {
    margin-top: 20px;
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}
.recent-users h5 {
    color: #007bff;
    font-size: 1.2rem;
    margin-bottom: 10px;
    font-weight: 600;
}
</style>
</head>
<body>
<?php include "header.php"; ?>
<div class="mobile-menu-overlay"></div>
<div class="main-container">
<div class="xs-pd-20-10 pd-ltr-20">
<div class="page-header">
<div class="dashboard-header">
<div class="d-flex flex-wrap align-items-center justify-content-between">
<div class="page-title">
<h4 class="mb-0">Users Who Viewed Video ID <?php echo htmlspecialchars($video_id); ?></h4>
<p class="mb-0">Manage and analyze viewer statistics</p>
</div>
<nav aria-label="breadcrumb">
<ol class="breadcrumb bg-transparent mb-0 p-0">
<li class="breadcrumb-item"><a href="index.php">Home</a></li>
<li class="breadcrumb-item"><a href="show_video_views.php">Video Views</a></li>
<li class="breadcrumb-item active" aria-current="page">View Users</li>
</ol>
</nav>
</div>
</div>
</div>
<div class="filter-container">
<form method="get" style="display: flex; gap: 15px; align-items: center;">
    <input type="hidden" name="video_id" value="<?php echo htmlspecialchars($video_id); ?>">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search by ID, Username, Email, or View Times...">
    <input type="datetime-local" name="start_date" value="<?php echo !empty($start_date) ? date('Y-m-d\TH:i', strtotime($start_date)) : ''; ?>" class="form-control">
    <input type="datetime-local" name="end_date" value="<?php echo !empty($end_date) ? date('Y-m-d\TH:i', strtotime($end_date)) : ''; ?>" class="form-control">
    <button type="submit" class="btn btn-primary">Filter</button>
</form>
</div>
<div class="row">
<?php if (empty($users)): ?>
<div class="col-12">
<div class="empty-state">
<i class="material-icons" style="font-size: 24px; vertical-align: middle;">info</i> No users found for this video within the selected time frame.
</div>
</div>
<?php else: ?>
<?php foreach ($users as $user): ?>
<div class="col-lg-3 col-md-4 col-sm-6 mb-20">
<div class="card-box custom-card-hover">
<div class="text-left">
<div class="card-text">
<span class="card-label">User ID:</span>
<span class="card-value"><?php echo htmlspecialchars($user['id']); ?></span>
</div>
<div class="card-text">
<span class="card-label">Username:</span>
<span class="card-value"><?php echo htmlspecialchars($user['username']); ?></span>
</div>
<div class="card-text">
<span class="card-label">Email:</span>
<span class="card-value"><?php echo htmlspecialchars($user['email']); ?></span>
</div>
<div class="card-text">
<span class="card-label">View Times:</span>
<?php 
echo htmlspecialchars(implode(', ', array_map(function($time) {
    $dt = new DateTime($time);
    $dt->modify('+5 hours');
    return $dt->format('d M Y h:i A');
}, $user['view_times']))); 
?>

</div>
</div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?video_id=<?php echo htmlspecialchars($video_id); ?>&page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>&start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<!-- Recently Viewed Users -->
<?php if (!empty($recent_users)): ?>
<div class="recent-users">
<h5>Recently Viewed Users</h5>
<div class="row">
<?php foreach ($recent_users as $recent_user): ?>
<div class="col-12 mb-2">
<div class="card-box">
<div class="card-text">
<span class="card-label">User ID:</span>
<span class="card-value"><?php echo htmlspecialchars($recent_user['id']); ?></span>
</div>
<div class="card-text">
<span class="card-label">Username:</span>
<span class="card-value"><?php echo htmlspecialchars($recent_user['username']); ?></span>
</div>
<div class="card-text">
<span class="card-label">Email:</span>
<span class="card-value"><?php echo htmlspecialchars($recent_user['email']); ?></span>
</div>
<div class="card-text">
<span class="card-label">Last Viewed:</span>
<span class="card-value"><?php echo date('d M Y h:i A', strtotime($recent_user['view_time'] . ' +5 hours')); ?></span>

</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
</div>
</div>
<!-- js -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<!-- Popper.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://stackpath.cloudflare.com/ajax/libs/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script src="vendors/scripts/dashboard2.js"></script>
<!-- jQuery Knob -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-knob/1.2.13/jquery.knob.min.js"></script>
<!-- jVectorMap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.3/jquery-jvectormap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.3/jquery-jvectormap-world-mill-en.js"></script>
<!-- Custom Script for Sidebar -->
<script type="text/javascript">
$(document).ready(function(){
    // Sidebar functionality
    $(".xp-menabar").on('click',function(){
        $("#sidebar").toggleClass('active');
        $("#content").toggleClass('active');
    });
    $('.xp-menabar,.body-overlay').on('click',function(){
        $("#sidebar,.body-overlay").toggleClass('show-nav');
    });
});
</script>
</body>
</html>