<?php
include "config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get the group ID from the URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch all videos assigned to the group along with episode details and drama name
$sql = "SELECT uv.user_id,uv.start_date,uv.end_date, e.id as video_id, e.video_path, e.episode_number, s.season_number, d.name as drama_name
        FROM user_videos uv 
        JOIN episode e ON uv.video_id = e.id 
        JOIN season s ON e.season_id = s.id
        JOIN drama d ON s.drama_id = d.id
        WHERE uv.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <!-- Basic Page Info -->
    <meta charset="utf-8">
    <title>DeskApp - Bootstrap Admin Dashboard HTML Template</title>

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
    <link rel="stylesheet" type="text/css" href="src/plugins/jvectormap/jquery-jvectormap-2.0.3.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
    <link rel="stylesheet" href="vendors/styles/mycss/show-group.css">
	    <!----css3---->
        <link rel="stylesheet" href="vendors/styles//css/custom.css">
			   <!--google material icon-->
			   <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
</head>
<body>
<?php include "header.php"; ?>
<div class="main-container">
    <div class="pd-ltr-20 xs-pd-20-10">
        <div class="min-height-200px">
        <div class="page-header">
    <div class="dashboard-header mb-4 p-3 rounded shadow-sm bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="page-title">
                <h4 class="mb-0">User Videos</h4>
                <small>View and manage user-uploaded videos</small>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">User Videos</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="gallery-wrap">
    <ul class="row">
        <?php 
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                // Video Display with Episode, Season, and Drama Information
                echo '<li class="col-lg-3 col-md-6 col-sm-12">';
                echo '    <div class="da-card box-shadow">';
                echo '        <div class="da-card-photo">';
                echo '            <div class="video-container">';
                echo '                <video src="admin_secure_media.php?video_id=' . $row['video_id'] . '" controls type="video/mp4" width="100%" height="200"></video>';
                echo '            </div>';
                echo '        </div>';
                echo '        <div class="product-caption">';
                // Display Drama Name, Episode, and Season Info
                echo '            <p>';
                echo '                <strong>' . htmlspecialchars($row['drama_name']) . '</strong> - ';
                echo '                Episode ' . (isset($row['episode_number']) ? htmlspecialchars($row['episode_number']) : 'N/A') . ' - ';
                echo '                Season ' . (isset($row['season_number']) ? htmlspecialchars($row['season_number']) : 'N/A');
                echo '            </p>';
$start_date = $row['start_date'] ?? null;
$end_date = $row['end_date'] ?? null;
$today = date('Y-m-d');

if ($start_date && $end_date) {
    $start_ts = strtotime($start_date);
    $end_ts = strtotime($end_date);
    $today_ts = strtotime($today);

    $total_days = ($end_ts - $start_ts) / (60 * 60 * 24);

    if ($today_ts < $start_ts) {
        // Subscription hasn't started yet
        $days_until_start = ($start_ts - $today_ts) / (60 * 60 * 24);
        echo '<span class="text-warning">Subscription will start in ' . intval($days_until_start) . ' day(s)</span><br>';
        echo '<small class="text-muted">Total duration: ' . intval($total_days) . ' day(s)</small>';
    } elseif ($today_ts > $end_ts) {
        // Subscription already ended
        echo '<span class="text-danger">Subscription ended</span><br>';
        echo '<small class="text-muted">Total duration was ' . intval($total_days) . ' day(s)</small>';
    } else {
        // Subscription is active
        $days_left = ($end_ts - $today_ts) / (60 * 60 * 24);
        echo '<span class="text-success">Ends in ' . intval($days_left) . ' day(s)</span><br>';
        echo '<small class="text-muted">Total duration: ' . intval($total_days) . ' day(s)</small>';
    }

} elseif (!$start_date) {
    echo '<span class="text-muted">No start date set</span>';
} elseif (!$end_date) {
    echo '<span class="text-muted">No end date set</span>';
}

                // Delete Video Button with Icon and Class `btn btn-blue`
                echo '            <a href="delete_user_video.php?user_id=' . $row['user_id'] . '&video_id=' . $row['video_id'] . '" onclick="return confirm(\'Are you sure you want to delete this video?\')" class="btn btn-blue1">';
                echo '                <i class="fas fa-trash-alt me-2"></i> Video';
                echo '            </a>';
                echo '        </div>';
                echo '    </div>';
                echo '</li>';
            }
        } else {
            echo "No videos assigned to this user.";
        }
        $stmt->close();
        $conn->close();
        ?>          
    </ul>
</div>

        </div>
    </div>
</div>
<div class="mobile-menu-overlay"></div>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script src="vendors/js/jquery-3.3.1.slim.min.js"></script>
   <script src="vendors/js/popper.min.js"></script>
  
   <script src="vendors/js/jquery-3.3.1.min.js"></script>
   <script type="text/javascript">
       $(document).ready(function(){
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

