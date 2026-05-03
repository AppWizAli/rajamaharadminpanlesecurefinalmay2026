<?php
include "config.php";
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch current admin type
$current_admin_id = $_SESSION['admin_id'];
$sql = "SELECT admin_type FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing SQL: " . $conn->error);
}
$stmt->bind_param("i", $current_admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $current_admin = $result->fetch_assoc();
    $current_admin_type = $current_admin['admin_type'];
} else {
    echo "Current admin not found.";
    exit;
}
$stmt->close();

// Fetch data
$groups1 = $conn->query("SELECT * FROM `groups`");
$users = $conn->query("SELECT * FROM `users`");
$dramas = $conn->query("SELECT * FROM drama");
$groups2 = $conn->query("SELECT * FROM `groups`"); // FIXED: escape table name
$private_episodes = $conn->query("SELECT * FROM episode WHERE privacy = 'private'");

// Error handling
if (!$groups1 || !$users || !$dramas || !$groups2 || !$private_episodes) {
    die("Error fetching data: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_video_to_group'])) {
    $user_id = intval($_POST['user_id']);
    $drama_id = intval($_POST['darama_id']);
    $season_id = intval($_POST['season_id']);
    $video_ids = $_POST['video_id'] ?? [];

    // Default date range to 30 days if not set
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
$end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime($start_date . ' +30 days'));


    // Validate required fields
    if (empty($video_ids)) {
        echo "No videos selected. Please select at least one video.";
        exit;
    }
    if (empty($drama_id) || empty($season_id) || empty($user_id)) {
        echo "Please select drama, season, and user.";
        exit;
    }

    // Insert assignments
    $sql = "INSERT INTO user_videos (user_id, video_id, drama_id, season_id, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing SQL: " . $conn->error);
    }

    foreach ($video_ids as $video_id) {
        $stmt->bind_param("iiiiss", $user_id, $video_id, $drama_id, $season_id, $start_date, $end_date);
        if (!$stmt->execute()) {
            echo "Error assigning video ID $video_id to user ID $user_id: " . $stmt->error;
        }
    }

    $stmt->close();
    echo "Videos successfully assigned to the user!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>DeskApp - Bootstrap Admin Dashboard HTML Template</title>
    <link rel="apple-touch-icon" sizes="180x180" href="vendors/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="vendors/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="vendors/images/favicon-16x16.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="src/plugins/jvectormap/jquery-jvectormap-2.0.3.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
          <link rel="stylesheet" href="vendors/styles//css/custom.css">
			   <!--google material icon-->
			   <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
</head>
<body>

<?php include "header.php"; ?>

<div class="container media11" style="margin-top: 100px;">
    <div class="card mb-3">
        <div class="clearfix">
            <div class="section-header-custom">
                <h4 class="section-title-custom">Assign Videos to User</h4>
                <p class="section-subtitle-custom">Assign uploaded videos with access period</p>
            </div>
        </div>

        <div class="card-body">
            <form method="post" action="">
                <div class="form-group">
                    <label for="user_id">Select User:</label>
                    <select id="user_id" name="user_id" class="form-control select2" required>
                        <?php while ($user = $users->fetch_assoc()) { ?>
                            <option value="<?= $user['id'] ?>"><?= $user['id'] . ' - ' . $user['username'] . ' - ' . $user['email']; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="darama_id">Select Drama:</label>
                    <select id="darama_id" name="darama_id" class="form-control" required>
                        <option value="">Please select a drama</option>
                        <?php while ($drama = $dramas->fetch_assoc()) { ?>
                            <option value="<?= $drama['id'] ?>"><?= $drama['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="season_id">Select Season:</label>
                    <select id="season_id" name="season_id" class="form-control" required>
                        <option value="">Please select a drama first</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="video_id">Select Episodes:</label>
                    <div>
                        <input type="checkbox" id="selectAllEpisodes"> <label for="selectAllEpisodes">Select All</label>
                    </div>
                    <select id="video_id" name="video_id[]" class="form-control" multiple required>
                        <option value="">Please select a season first</option>
                    </select>
                    <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple.</small>
                </div>
                     <div class="form-group d-flex">
                <div class="form-group mr-2">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" >
                </div>
                        </div>
                <button type="submit" name="assign_video_to_group" class="btn btn-custom-red">
                    <i class="fas fa-film me-1"></i> Assign Videos
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('#user_id').select2({ placeholder: 'Select a user', allowClear: true });

        $('#darama_id').change(function () {
            var dramaId = $(this).val();
            if (dramaId) {
                $.post('getseasoningroup.php', { darama_id: dramaId }, function (data) {
                    $('#season_id').html(data);
                    $('#video_id').html('<option value="">Please select a season first</option>');
                });
            } else {
                $('#season_id').html('<option value="">Please select a drama first</option>');
                $('#video_id').html('<option value="">Please select a season first</option>');
            }
        });

        $('#season_id').change(function () {
            var seasonId = $(this).val();
            if (seasonId) {
                $.post('getepisodesingroup.php', { season_id: seasonId }, function (data) {
                    $('#video_id').html(data);
                });
            } else {
                $('#video_id').html('<option value="">Please select a season first</option>');
            }
        });

        $('#selectAllEpisodes').change(function () {
            $('#video_id option').prop('selected', this.checked).trigger('change');
        });
    });
</script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateInput = document.getElementById('start_date');
        const today = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD
        dateInput.value = today;
    });
</script>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script src="src/plugins/jQuery-Knob-master/jquery.knob.min.js"></script>
<script src="src/plugins/highcharts-6.0.7/code/highcharts.js"></script>
<script src="src/plugins/highcharts-6.0.7/code/highcharts-more.js"></script>
<script src="src/plugins/jvectormap/jquery-jvectormap-2.0.3.min.js"></script>
<script src="src/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
<script src="vendors/scripts/dashboard2.js"></script>
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
