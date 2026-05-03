<?php
include "config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get the group ID from the URL
$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch group details
$sql = "SELECT * FROM `groups` WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $group = $result->fetch_assoc();
} else {
    echo "Group not found.<br>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_group'])) {
    $group_name = $_POST['group_name'];
    $group_comment = $_POST['group_comment']; 

    // Prepare and execute the update statement
    $update_sql = "UPDATE `groups` SET group_name = ?, group_comment = ? WHERE id = ?"; 
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $group_name, $group_comment, $group_id);

    if ($update_stmt->execute()) {
        header("Location: show_groups.php");
        exit;
    } else {
        echo "Error updating group details: " . $update_stmt->error . "<br>";
    }

    $update_stmt->close();
}

$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <!-- Page metadata and styles -->
    <meta charset="utf-8">
    <title>Edit Group</title>
    <!-- CSS files -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">

         <link rel="stylesheet" href="vendors/styles//css/custom.css">
			   <!--google material icon-->
			   <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">
               <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
</head>
<body>
<?php include "header.php"; ?>
<div class="main-container" >
    <div class="xs-pd-20-10 pd-ltr-20">
    <div class="page-header">
    <div class="dashboard-header mb-4 p-3 rounded shadow-sm bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="page-title">
                <h4 class="mb-0">Edit Group</h4>
                <small>Make changes to your group information</small>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Group</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

        <div class="pd-20 card-box mb-30">
            <div class="clearfix">
                <div class="section-header-custom">
                    <h4 class="section-title-custom">EDIT GROUP</h4>
                    <p class="section-subtitle-custom">Edit the details of the group</p>
                </div>
            </div>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="group_name">Group Name:</label>
                    <input type="text" id="group_name" name="group_name" class="form-control" value="<?php echo htmlspecialchars($group['group_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="group_comment">Group Comment:</label>
                    <textarea id="group_comment" name="group_comment" class="form-control" required><?php echo htmlspecialchars($group['group_comment']); ?></textarea>
                </div>
                <button type="submit" name="update_group" class="btn btn-custom-red">Update Group</button>
            </form>
        </div>
    </div>
</div>
<!-- JS Scripts -->
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
