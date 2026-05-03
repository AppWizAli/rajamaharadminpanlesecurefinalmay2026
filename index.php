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
// Now, continue with your index.php content
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jvectormap/2.0.3/jquery-jvectormap.css" />
<link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
<link rel="stylesheet" href="vendors/styles/mycss/Dashboard.css">
<link rel="stylesheet" href="vendors/styles/css/custom.css">
<!-- Google Material Icon -->
<link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
<script src="https://cdn.jsdelivr.net/npm/@highcharts/grid-lite/grid-lite.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@highcharts/grid-lite/css/grid.css" />
<!-- Custom CSS for hover effects -->
<style>
/* General hover effect for all cards */
.custom-card-hover .card-box {
    transition: background-color 0.3s ease, color 0.3s ease;
}

.custom-card-hover:hover .card-box {
    background-color: #f0f0f0; /* Light gray background for other cards */
}

.custom-card-hover:hover .card-box .progress-box h5,
.custom-card-hover:hover .card-box .progress-box span,
.custom-card-hover:hover .card-box .progress-box i {
    color: #333333 !important; /* Dark text/icon for contrast */
}

/* Specific hover effect for Check Video Views card */
.card-7 .card-box {
    transition: background-color 0.3s ease, color 0.3s ease;
}

.card-7:hover .card-box {
    background-color: #007bff; /* Primary blue background on hover */
}

.card-7:hover .card-box .progress-box h5,
.card-7:hover .card-box .progress-box span,
.card-7:hover .card-box .progress-box i {
    color: #ffffff !important; /* White text/icon for contrast */
}

/* Embedded Groups Section */
.embedded-groups-section {
    background: white;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.embedded-groups-section iframe {
    width: 100%;
    height: 600px;
    border: none;
    display: block;
}

/* Live Views Statistics Cards */
.live-views-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    padding: 20px;
    color: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    cursor: pointer;
}

.live-views-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.live-views-card h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    opacity: 0.9;
}

.live-views-card .view-count {
    font-size: 36px;
    font-weight: 700;
    margin: 10px 0;
    animation: pulse 2s ease-in-out infinite;
}

.live-views-card .view-label {
    font-size: 14px;
    opacity: 0.8;
}

.live-views-card.monthly {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.live-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: #00ff00;
    border-radius: 50%;
    margin-left: 5px;
    animation: blink 1.5s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Episode Details Modal */
.episode-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.7);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.episode-modal-content {
    background-color: #fefefe;
    margin: 2% auto;
    padding: 0;
    border-radius: 10px;
    width: 95%;
    max-width: 1400px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 30px;
    border-radius: 10px 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 24px;
}

.close-modal {
    color: white;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s;
}

.close-modal:hover {
    transform: scale(1.2);
}

.modal-body {
    padding: 20px 30px 30px;
}

.episode-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    display: flex;
    gap: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.episode-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.episode-thumbnail {
    flex-shrink: 0;
    width: 200px;
    height: 120px;
    border-radius: 6px;
    overflow: hidden;
    background: #f0f0f0;
}

.episode-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.episode-info {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.episode-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.episode-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #666;
}

.meta-item i {
    color: #667eea;
}

.episode-description {
    color: #555;
    font-size: 14px;
    line-height: 1.6;
    margin: 5px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.view-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    align-self: flex-start;
}

.privacy-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.privacy-badge.public {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.privacy-badge.private {
    background-color: #fff3e0;
    color: #e65100;
}

.loading-spinner {
    text-align: center;
    padding: 40px;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 16px;
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
<h4 class="mb-0">Dashboard</h4>
<small>Welcome to your admin panel overview</small>
</div>
<nav aria-label="breadcrumb">
<ol class="breadcrumb bg-transparent mb-0 p-0">
<li class="breadcrumb-item"><a href="index.php">Home</a></li>
<li class="breadcrumb-item active" aria-current="page">Dashboard</li>
</ol>
</nav>
</div>
</div>
</div>

<!-- Embedded Groups Section -->
<div class="embedded-groups-section">
<iframe src="show_groups.php" title="Groups Management" scrolling="auto"></iframe>
</div>

<!-- Live Video Views Statistics -->
<div class="row mb-30">
<div class="col-lg-6 col-md-6 col-sm-12 mb-30">
<div class="live-views-card" id="daily-views-card">
<h3>Last 24 Hours Views <span class="live-indicator"></span></h3>
<div class="view-count" id="daily-views">Loading...</div>
<div class="view-label">Click to view episode details</div>
</div>
</div>
<div class="col-lg-6 col-md-6 col-sm-12 mb-30">
<div class="live-views-card monthly" id="monthly-views-card">
<h3>Current Month Views <span class="live-indicator"></span></h3>
<div class="view-count" id="monthly-views">Loading...</div>
<div class="view-label">Click to view episode details</div>
</div>
</div>
</div>

<div class="row clearfix progress-box">
<div class="col-lg-2 col-md-6 col-sm-12 mb-30 custom-card-hover card-1">
<div class="card-box pd-30 height-100-p">
<div class="progress-box text-center">
<h5 class="text-warning padding-top-10 h5">All Dramas</h5>
<a href="product.php"><span class="d-block">Average <i class="fa text-warning fa-line-chart"></i></span></a>
</div>
</div>
</div>
<div class="col-lg-2 col-md-6 col-sm-12 mb-30 custom-card-hover card-2">
<div class="card-box pd-30 height-100-p">
<div class="progress-box text-center">
<h5 class="text-light-green padding-top-10 h5">All Admins</h5>
<a href="admin_records.php"><span class="d-block">Average <i class="fa text-light-green fa-line-chart"></i></span></a>
</div>
</div>
</div>
<div class="col-lg-2 col-md-6 col-sm-12 mb-30 custom-card-hover card-3">
<div class="card-box pd-30 height-100-p">
<div class="progress-box text-center">
<h5 class="text-light-orange padding-top-10 h5">All Users</h5>
<a href="usersrecords.php"><span class="d-block">Average <i class="fa text-light-orange fa-line-chart"></i></span></a>
</div>
</div>
</div>
<div class="col-lg-2 col-md-6 col-sm-12 mb-30 custom-card-hover card-4">
<div class="card-box pd-30 height-100-p">
<div class="progress-box text-center">
<h5 class="text-secondary padding-top-10 h5">Assign Video to User</h5>
<a href="users_video.php"><span class="d-block">Average <i class="fa text-light-orange fa-line-chart"></i></span></a>
</div>
</div>
</div>
<div class="col-lg-2 col-md-6 col-sm-12 mb-30 custom-card-hover card-5">
<div class="card-box pd-30 height-100-p">
<div class="progress-box text-center">
<h5 class="text-info padding-top-10 h5">Assign Video to group</h5>
<a href="create_groups.php"><span class="d-block">Average <i class="fa text-light-orange fa-line-chart"></i></span></a>
</div>
</div>
</div>
<div class="col-lg-2 col-md-6 col-sm-12 mb-30 custom-card-hover card-6">
<div class="card-box pd-30 height-100-p">
<div class="progress-box text-center">
<h5 class="text-light-purple padding-top-10 h5">All Groups</h5>
<a href="show_groups.php"><span class="d-block">Average <i class="fa text-light-purple fa-line-chart"></i></span></a>
</div>
</div>
</div>
<div class="col-lg-2 col-md-6 col-sm-12 mb-30 custom-card-hover card-7">
<div class="card-box pd-30 height-100-p">
<div class="progress-box text-center">
<h5 class="text-primary padding-top-10 h5">Check Video Views</h5>
<a href="show_video_views.php"><span class="d-block">Average <i class="fa text-primary fa-line-chart"></i></span></a>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Episode Details Modal -->
<div id="episodeModal" class="episode-modal">
<div class="episode-modal-content">
<div class="modal-header">
<h2 id="modal-title">Episode Details</h2>
<span class="close-modal">&times;</span>
</div>
<div class="modal-body" id="modal-body">
<div class="loading-spinner">
<div class="spinner"></div>
<p>Loading episodes...</p>
</div>
</div>
</div>
</div>

<!-- js -->
<!-- jQuery (full version, needed for plugins) -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<!-- Popper.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
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
<!-- Custom Dashboard Script -->
<script type="text/javascript">
$(document).ready(function(){
    $(".xp-menubar").on('click', function(){
        $("#sidebar").toggleClass('active');
        $("#content").toggleClass('active');
    });
    $('.xp-menubar,.body-overlay').on('click', function(){
        $("#sidebar,.body-overlay").toggleClass('show-nav');
    });
    
    // Function to fetch live video views
    function fetchLiveViews() {
        $.ajax({
            url: 'get_live_views.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Update daily views with animation
                    $('#daily-views').fadeOut(200, function() {
                        $(this).text(response.daily_views.toLocaleString()).fadeIn(200);
                    });
                    
                    // Update monthly views with animation
                    $('#monthly-views').fadeOut(200, function() {
                        $(this).text(response.monthly_views.toLocaleString()).fadeIn(200);
                    });
                } else {
                    $('#daily-views').text('Error');
                    $('#monthly-views').text('Error');
                }
            },
            error: function() {
                $('#daily-views').text('Error');
                $('#monthly-views').text('Error');
            }
        });
    }
    
    // Fetch views immediately on page load
    fetchLiveViews();
    
    // Update views every 5 seconds
    setInterval(fetchLiveViews, 5000);
    
    // Modal functionality
    var modal = $('#episodeModal');
    var closeBtn = $('.close-modal');
    
    // Close modal when clicking X
    closeBtn.click(function() {
        modal.fadeOut(300);
    });
    
    // Close modal when clicking outside
    $(window).click(function(event) {
        if (event.target.id === 'episodeModal') {
            modal.fadeOut(300);
        }
    });
    
    // Function to load episode details
    function loadEpisodeDetails(period) {
        modal.fadeIn(300);
        
        if(period === 'daily') {
            $('#modal-title').text('Last 24 Hours - Episode Views');
        } else {
            $('#modal-title').text('Current Month - Episode Views');
        }
        
        $('#modal-body').html('<div class="loading-spinner"><div class="spinner"></div><p>Loading episodes...</p></div>');
        
        $.ajax({
            url: 'get_episode_details.php',
            type: 'GET',
            data: { period: period },
            dataType: 'json',
            success: function(response) {
                if(response.success && response.episodes.length > 0) {
                    var html = '';
                    $.each(response.episodes, function(index, episode) {
                        html += '<div class="episode-card">';
                        html += '<div class="episode-thumbnail">';
                        if(episode.thumbnail) {
                            html += '<img src="' + episode.thumbnail + '" alt="' + episode.episode_number + '" onerror="this.src=\'vendors/images/default-thumbnail.jpg\'">';
                        } else {
                            html += '<img src="vendors/images/default-thumbnail.jpg" alt="No thumbnail">';
                        }
                        html += '</div>';
                        html += '<div class="episode-info">';
                        html += '<h3 class="episode-title">Episode ' + episode.episode_number + '</h3>';
                        html += '<div class="episode-meta">';
                        html += '<span class="meta-item"><i class="fa fa-eye"></i> ' + episode.view_count + ' views</span>';
                        html += '<span class="meta-item"><i class="fa fa-film"></i> Season ' + episode.season_id + '</span>';
                        html += '<span class="privacy-badge ' + episode.privacy + '">' + episode.privacy.toUpperCase() + '</span>';
                        if(episode.download_access) {
                            html += '<span class="meta-item"><i class="fa fa-download"></i> ' + episode.download_access + '</span>';
                        }
                        html += '</div>';
                        if(episode.description) {
                            html += '<p class="episode-description">' + episode.description + '</p>';
                        }
                        if(episode.video_path) {
                            html += '<div class="meta-item"><i class="fa fa-video-camera"></i> <small>' + episode.video_path.substring(0, 50) + '...</small></div>';
                        }
                        html += '</div>';
                        html += '<div class="view-badge">#' + (index + 1) + '</div>';
                        html += '</div>';
                    });
                    $('#modal-body').html(html);
                } else {
                    $('#modal-body').html('<div class="no-data"><i class="fa fa-inbox" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></i>No episode data available for this period.</div>');
                }
            },
            error: function() {
                $('#modal-body').html('<div class="no-data"><i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #f44336; display: block; margin-bottom: 10px;"></i>Error loading episode details. Please try again.</div>');
            }
        });
    }
    
    // Click event for daily views card
    $('#daily-views-card').click(function() {
        loadEpisodeDetails('daily');
    });
    
    // Click event for monthly views card
    $('#monthly-views-card').click(function() {
        loadEpisodeDetails('monthly');
    });
});
</script>
</body>
</html>