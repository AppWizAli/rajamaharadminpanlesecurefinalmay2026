<?php
include "config.php";
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch the current admin's type
$current_admin_id = $_SESSION['admin_id'];
$sql = "SELECT admin_type FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
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

// Fetch groups and users for the first form
$groups1 = $conn->query("SELECT * FROM groups");
$users = $conn->query("SELECT * FROM users");

if ($groups1 === false || $users === false) {
    die("Error fetching data: " . $conn->error);
}

// Fetch dramas for the drama selection
$dramas = $conn->query("SELECT * FROM drama");

if ($dramas === false) {
    die("Error fetching dramas: " . $conn->error);
}

// Fetch groups and private episodes for the second form
$groups2 = $conn->query("SELECT * FROM groups");
$private_episodes = $conn->query("SELECT * FROM episode WHERE privacy = 'private'");

if ($groups2 === false || $private_episodes === false) {
    die("Error fetching data: " . $conn->error);
}

// Process form submission to assign videos to user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_video_to_group'])) {
    // Retrieve submitted data
    $user_id = $_POST['user_id'];
    $drama_id = $_POST['darama_id'];
    $season_id = $_POST['season_id'];
    $video_ids = $_POST['video_id'] ?? [];

    // Validate the inputs
    if (empty($video_ids)) {
        echo "No videos selected. Please select at least one video.";
        exit;
    }

    if (empty($drama_id) || empty($season_id) || empty($user_id)) {
        echo "Please select drama, season, and user.";
        exit;
    }

    // Prepare the SQL to insert video assignments
    $sql = "INSERT INTO user_videos (user_id, video_id, drama_id, season_id) VALUES (?, ?, ?, ?)";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing SQL: " . $conn->error);
    }

    // Bind and execute for each video
    foreach ($video_ids as $video_id) {
        $stmt->bind_param("iiii", $user_id, $video_id, $drama_id, $season_id);
        if (!$stmt->execute()) {
            echo "Error assigning video with ID $video_id to user ID $user_id: " . $stmt->error;
        }
    }

    // Close the statement
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

<div class="container  media11" style="margin-top: 100px;">
    <!-- Assign Videos to Users -->
    <div class="card mb-3">
        <div class="clearfix">
            <div class="section-header-custom">
                <h4 class="section-title-custom">Assign Videos to User</h4>
                <p class="section-subtitle-custom">Assign uploaded videos to any user</p>
            </div>
        </div>
        
        <div class="card-body">
            <form method="post" action="">
                <div class="form-group">
                    <label for="user_id">Select User:</label>
                    <select id="user_id" name="user_id" class="form-control select2" required>
                        <?php while ($user = $users->fetch_assoc()) { ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo $user['id'] . ' - ' . $user['username'] . ' - ' . $user['email']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

              <div class="form-group">
        <label for="darama_id">Select Dramas:</label>
        <select id="darama_id" name="darama_id" class="form-control" required>
          <option value="">Please select a drama</option>
            <?php while ($drama = $dramas->fetch_assoc()) { ?>
                <option value="<?php echo $drama['id']; ?>"><?php echo $drama['name']; ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="form-group">
        <label for="season_id">Select Season:</label>
        <select id="season_id" name="season_id" class="form-control" required>
            <option value="">Please select a drama first</option>
            <!-- Season options will be populated via AJAX -->
        </select>
    </div>
    <input type="hidden" id="season_number" name="season_number" value="">
 <div class="form-group">
    <label for="video_id">Select Episodes:</label>
    
    <!-- "Select All" Checkbox -->
    <div>
        <input type="checkbox" id="selectAllEpisodes"> <label for="selectAllEpisodes">Select All</label>
    </div>

    <select id="video_id" name="video_id[]" class="form-control" multiple required>
        <option value="">Please select a season first</option>
    </select>

    <small class="form-text text-muted">
        Hold down the Ctrl (Windows) or Command (Mac) key to select multiple options.
    </small>
</div>


<button type="submit" name="assign_video_to_group" class="btn btn-custom-red">
    <i class="fas fa-film me-1"></i> Assign Videos
</button>

            </form>
        </div>
    </div>
</div>

<!-- Include jQuery and Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#user_id').select2({
            placeholder: 'Select a user',
            allowClear: true
        });
    });
</script>
   
<script>
    $(document).ready(function () {
        // Fetch seasons based on selected drama
        $('#darama_id').change(function () {
            var dramaId = $(this).val();

            if (dramaId) {
                $.ajax({
                    url: 'getseasoningroup.php', 
                    type: 'POST',
                    data: { darama_id: dramaId },
                    success: function (response) {
                        $('#season_id').html(response);
                        $('#video_id').html('<option value="">Please select a season first</option>'); // Reset episodes
                    },
                    error: function () {
                        alert('Failed to fetch seasons. Please try again.');
                    }
                });
            } else {
                $('#season_id').html('<option value="">Please select a drama first</option>');
                $('#video_id').html('<option value="">Please select a season first</option>');
            }
        });

        // Fetch episodes based on selected season
        $('#season_id').change(function () {
            var seasonId = $(this).val();

            if (seasonId) {
                $.ajax({
                    url: 'getepisodesingroup.php',
                    type: 'POST',
                    data: { season_id: seasonId },
                    success: function (response) {
                        $('#video_id').html(response);
                    },
                    error: function () {
                        alert('Failed to fetch episodes. Please try again.');
                    }
                });
            } else {
                $('#video_id').html('<option value="">Please select a season first</option>');
            }
        });
    });
    // "Select All" Checkbox Functionality
  $(document).ready(function () {
  
    $("#selectAllEpisodes").change(function () {
        var isChecked = $(this).prop("checked"); 

        $("#video_id option").each(function () {
            $(this).prop("selected", isChecked);
        });

        $("#video_id").trigger("change");
    });
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