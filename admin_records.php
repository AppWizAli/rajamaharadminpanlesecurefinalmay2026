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

$sql = "SELECT * FROM admin";
$result = $conn->query($sql);
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
   <!----css3---->
   <link rel="stylesheet" href="vendors/styles//css/custom.css">
			   <!--google material icon-->
			   <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">
			   <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
</head>
<body>
<?php include "header.php"; ?>
	<div class="mobile-menu-overlay"></div>
<div class="main-container" >
		<div class="xs-pd-20-10 pd-ltr-20">
		<div class="page-header">
    <div class="dashboard-header mb-4 p-3 rounded shadow-sm bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="page-title">
                <h4 class="mb-0">Dashboard</h4>
                <small>Manage your dashboard here</small>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Admins</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

				<div class="page-header">
                    <div class="row">
                        <div class="col-md-12 col-sm-12" style="display: flex; justify-content: space-between;">
                            <div class="title">
                                <h3 class="section-title-red">All Admins</h3>
                            </div>
                            <a href="Admin.php" class="btn btn-outline-custom-green btn-sm" type="button">
                                <i class="fa fa-plus"></i>
                                Add Admin
                            </a>
                        </div>
                    </div>
<!-- Desktop View (Visible on Large Screens) -->
<div class="table-responsive mt-4 d-none d-lg-block">
    <table class="table table-bordered table-hover table-striped">
        <thead class="table-head-custom text-nowrap">
            <tr>
                <th>Admin Name</th>
                <th>Admin Email</th>
                <th>Admin Password</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '    <td class="align-middle">' . htmlspecialchars($row['admin_name']) . '</td>';
                    echo '    <td class="align-middle">' . htmlspecialchars($row['admin_email']) . '</td>';
                    echo '    <td class="align-middle text-truncate" style="max-width: 150px;">' . htmlspecialchars($row['admin_password']) . '</td>';
                    echo '    <td class="align-middle">';
                    echo '        <div class="d-flex flex-column flex-lg-row gap-2 w-100">';
                    echo '            <a href="edit_admin.php?id=' . $row['id'] . '" class="btn btn-custom-blue btn-sm w-100 w-lg-auto"><i class="fas fa-edit"></i> Edit</a>';
                    echo '            <a href="delete_admin.php?id=' . $row['id'] . '" class="btn btn-custom-red1 btn-sm w-100 w-lg-auto"><i class="fas fa-trash"></i> Delete</a>';
                    echo '        </div>';
                    echo '    </td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="4" class="text-center">No admins found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Mobile View (Visible on Small Screens) -->
<div class="d-lg-none">
    <?php
    if ($result->num_rows > 0) {
        mysqli_data_seek($result, 0); // Reset result pointer
        while ($row = $result->fetch_assoc()) {
            echo '<div class="mobile-notification mb-3 shadow-sm">';
            echo '  <div class="notification-header">';
            echo '      <h5 class="notification-title">' . htmlspecialchars($row['admin_name']) . '</h5>';
            echo '      <span class="notification-time">' . htmlspecialchars($row['admin_email']) . '</span>';
            echo '  </div>';
            echo '  <div class="notification-body">';
            echo '      <p class="notification-message text-muted">Password: ' . htmlspecialchars($row['admin_password']) . '</p>';
            echo '  </div>';
            echo '  <div class="notification-actions">';
            echo '      <a href="edit_admin.php?id=' . $row['id'] . '" class="btn btn-custom-blue btn-sm"><i class="fas fa-edit"></i> Edit</a>';
            echo '      <a href="delete_admin.php?id=' . $row['id'] . '" class="btn btn-custom-red1 btn-sm"><i class="fas fa-trash"></i> Delete</a>';
            echo '  </div>';
            echo '</div>';
        }
    } else {
        echo '<div class="text-center text-muted">No admins found.</div>';
    }
    ?>
</div>

					
    </div>
   </div>
		</div>
	</div>
	<!-- js -->
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