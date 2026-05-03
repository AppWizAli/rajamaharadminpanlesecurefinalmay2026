<?php
// Start session and include your database connection file
session_start();
include "config.php";
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page or any other page you prefer
    header("Location: login.php");
    exit;
}
// Fetch all dramas
$dramas_sql = "SELECT id, name FROM drama ORDER BY name ASC";
$dramas_result = $conn->query($dramas_sql);
$dramas = [];
if ($dramas_result->num_rows > 0) {
    while ($row = $dramas_result->fetch_assoc()) {
        $dramas[] = $row;
    }
}
// Get selected filters from GET
$selected_drama_id = isset($_GET['drama_id']) ? intval($_GET['drama_id']) : 0;
$selected_season_id = isset($_GET['season_id']) ? intval($_GET['season_id']) : 0;
$sort = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';
// Fetch seasons if drama is selected
$seasons = [];
if ($selected_drama_id > 0) {
    $seasons_sql = "SELECT id, season_number FROM season WHERE drama_id = ? ORDER BY season_number ASC";
    $stmt = $conn->prepare($seasons_sql);
    $stmt->bind_param("i", $selected_drama_id);
    $stmt->execute();
    $seasons_result = $stmt->get_result();
    if ($seasons_result->num_rows > 0) {
        while ($row = $seasons_result->fetch_assoc()) {
            $seasons[] = $row;
        }
    }
    $stmt->close();
}
// Build main query with filters, only including videos with views
$sql = "SELECT e.id, e.season_id, e.episode_number, e.video_path, e.description, e.privacy, e.created_at,
               e.download_access, e.thumbnail, COUNT(v.id) as views, s.season_number, d.name as drama_name
        FROM episode e
        INNER JOIN video_views v ON v.video_id = e.id
        INNER JOIN season s ON e.season_id = s.id
        INNER JOIN drama d ON s.drama_id = d.id";
$where_clauses = [];
$params = [];
$param_types = "";
if ($selected_drama_id > 0) {
    $where_clauses[] = "s.drama_id = ?";
    $params[] = $selected_drama_id;
    $param_types .= "i";
}
if ($selected_season_id > 0) {
    $where_clauses[] = "e.season_id = ?";
    $params[] = $selected_season_id;
    $param_types .= "i";
}
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " GROUP BY e.id, e.season_id, e.episode_number, e.video_path, e.description, e.privacy, e.created_at,
          e.download_access, e.thumbnail, s.season_number, d.name";
$sql .= " ORDER BY views " . $sort;
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$videos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<!-- Basic Page Info -->
<meta charset="utf-8">
<title>DeskApp - Video Views</title>
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
<!-- Custom CSS for Professional Card Design -->
<style>
.card-box {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    padding: 12px;
}
.card-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}
.thumbnail-img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    margin-bottom: 12px;
    cursor: pointer;
}
.card-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #333;
    display: inline-block;
    width: 110px;
}
.card-value {
    font-size: 0.85rem;
    font-weight: 400;
    color: #555;
}
.card-text {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.views-badge {
    display: inline-block;
    background: linear-gradient(135deg, #007bff, #00b4db);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    margin: 8px 0;
}
.empty-state {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    color: #6c757d;
    font-size: 1rem;
}
.filter-container {
    background: #fff;
    border-radius: 8px;
    padding: 10px 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.filter-container select {
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 0.9rem;
    border: 1px solid #e0e0e0;
    min-width: 150px;
}
.filter-container button {
    background: #007bff;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
}
.user-table {
    width: 100%;
    border-collapse: collapse;
}
.user-table th, .user-table td {
    padding: 10px;
    border: 1px solid #e0e0e0;
    text-align: left;
}
.user-table th {
    background: #f8f9fa;
    font-weight: 600;
}
.user-table tr:nth-child(even) {
    background: #f8f9fa;
}
</style>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
<script src="https://cdn.jsdelivr.net/npm/@highcharts/grid-lite/grid-lite.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@highcharts/grid-lite/css/grid.css" />
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
<h4 class="mb-0">Video Views</h4>
<small>Explore video statistics and details</small>
</div>
<nav aria-label="breadcrumb">
<ol class="breadcrumb bg-transparent mb-0 p-0">
<li class="breadcrumb-item"><a href="index.php">Home</a></li>
<li class="breadcrumb-item active" aria-current="page">Video Views</li>
</ol>
</nav>
</div>
</div>
</div>
<div class="filter-container">
<form method="get" style="display: flex; gap: 15px; align-items: center;">
<select name="drama_id" onchange="this.form.submit()">
<option value="">All Dramas</option>
<?php foreach ($dramas as $drama): ?>
<option value="<?php echo $drama['id']; ?>" <?php if ($selected_drama_id == $drama['id']) echo 'selected'; ?>><?php echo htmlspecialchars($drama['name']); ?></option>
<?php endforeach; ?>
</select>
<?php if ($selected_drama_id > 0): ?>
<select name="season_id" onchange="this.form.submit()">
<option value="">All Seasons</option>
<?php foreach ($seasons as $season): ?>
<option value="<?php echo $season['id']; ?>" <?php if ($selected_season_id == $season['id']) echo 'selected'; ?>>Season <?php echo htmlspecialchars($season['season_number']); ?></option>
<?php endforeach; ?>
</select>
<?php endif; ?>
<select name="sort" onchange="this.form.submit()">
<option value="desc" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Most Views First</option>
<option value="asc" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>Least Views First</option>
</select>
</form>
</div>
<div class="row">
<?php if (empty($videos)): ?>
<div class="col-12">
<div class="empty-state">
<i class="material-icons" style="font-size: 24px; vertical-align: middle;">info</i> No videos with views found.
</div>
</div>
<?php else: ?>
<?php foreach ($videos as $video): ?>
<div class="col-lg-3 col-md-4 col-sm-6 mb-20">
<div class="card-box custom-card-hover">
<div class="text-left">
<img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="Thumbnail" class="img-fluid thumbnail-img" data-toggle="modal" data-target="#videoModal" data-video-src="<?php echo 'admin_secure_media.php?video_id=' . $video['id']; ?>">
<div class="card-text">
<span class="card-label">Drama Name:</span>
<span class="card-value"><?php echo htmlspecialchars($video['drama_name']); ?></span>
</div>
<div class="card-text">
<span class="card-label">Season Number:</span>
<span class="card-value"><?php echo htmlspecialchars($video['season_number']); ?></span>
</div>
<div class="card-text">
<span class="card-label">Episode Number:</span>
<span class="card-value"><?php echo htmlspecialchars($video['episode_number']); ?></span>
</div>
<div class="card-text">
<span class="card-label">Comment:</span>
<span class="card-value"><?php echo htmlspecialchars(substr($video['description'], 0, 30)); ?>...</span>
</div>
<div class="card-text">
<span class="card-label">Created At:</span>
<span class="card-value"><?php echo htmlspecialchars(date('M j, Y', strtotime($video['created_at']))); ?></span>
</div>
<div class="card-text">
<span class="card-label">Views:</span>
<span class="views-badge"><?php echo htmlspecialchars($video['views']); ?></span>
</div>
<div class="card-text">
<a href="view_users.php?video_id=<?php echo $video['id']; ?>" class="btn btn-primary btn-sm">View Users</a>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>
<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" role="dialog" aria-labelledby="videoModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg" role="document">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title" id="videoModalLabel">Video Player</h5>
<button type="button" class="close" data-dismiss="modal" aria-label="Close">
<span aria-hidden="true">&times;</span>
</button>
</div>
<div class="modal-body">
<video controls class="w-100" id="videoPlayer">
<source src="" type="video/mp4">
Your browser does not support the video tag.
</video>
</div>
</div>
</div>
</div>
<!-- js -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<!-- Popper.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<!-- Bootstrap JS for Modal -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script src="vendors/scripts/dashboard2.js"></script>
<!-- jQuery Knob -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-knob/1.2.13/jquery.knob.min.js"></script>
<!-- Highcharts -->
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/highcharts-more.js"></script>
<!-- jVectorMap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.3/jquery-jvectormap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.3/jquery-jvectormap-world-mill-en.js"></script>
<!-- Custom Script for Modals -->
<script type="text/javascript">
$(document).ready(function(){
    // Video Modal
    $('#videoModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var videoSrc = button.data('video-src');
        var videoPlayer = $(this).find('#videoPlayer source');
        videoPlayer.attr('src', videoSrc);
        $(this).find('#videoPlayer')[0].load();
    });
    $('#videoModal').on('hidden.bs.modal', function () {
        $(this).find('#videoPlayer')[0].pause();
    });
    // Sidebar functionality
    $(".xp-menubar").on('click',function(){
        $("#sidebar").toggleClass('active');
        $("#content").toggleClass('active');
    });
    $('.xp-menubar,.body-overlay').on('click',function(){
        $("#sidebar,.body-overlay").toggleClass('show-nav');
    });
});
</script>
</body>
</html>
