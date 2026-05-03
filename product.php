
<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page or any other page you prefer
    header("Location: login.php");
    exit;
}
$sql = "SELECT id, name, thumbnail FROM drama";
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
	<link rel="stylesheet" href="src/styles/style.css">
<!----css3---->
<link rel="stylesheet" href="vendors/styles//css/custom.css">
			   <!--google material icon-->
			   <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">

<link rel="stylesheet" href="vendors/styles/mycss/view-daramas.css">
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-...your-hash..." crossorigin="anonymous" referrerpolicy="no-referrer" />

	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
</head>
<body>
<?php include "header.php"; ?>
	<div class="mobile-menu-overlay"></div>
	<div class="main-container" >
		<div class="pd-ltr-20 xs-pd-20-10">
			<div class="min-height-200px">
            <div class="page-header">
    <div class="bg-white p-4 rounded-3 border mb-4">
        <div class="row align-items-center justify-content-between">
            <div class="col-md-8">
                <h4 class="main-heading mb-1">Manage <span class="highlight-text">Drama Types</span></h4>
                <p class="sub-text mb-2">Add, edit, or remove drama categories from your platform’s content list.</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 m-0">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Drama Types</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="adddrama.php" class="btn btn-outline-green">
                    <i class="fas fa-layer-group me-2 small"></i>
                    <i class="fas fa-plus me-1 small"></i>
                    Add Drama
                </a>
            </div>
        </div>
    </div>
</div>

        </div>
    </div>
</div>

<div class="product-wrap">
    <div class="product-list">
        <ul class="row">
            <?php 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<li class="col-lg-3 col-md-6 col-sm-12">';
                    echo '    <div class="product-box">';
                    echo '        <div class="product-img">';
                    echo '            <img src="' . htmlspecialchars($row["thumbnail"]) . '" alt="Thumbnail" class="img-thumbnail">';
                    echo '        </div>';
                    echo '        <div class="product-caption">';
                    echo '            <div style="display: flex; justify-content: space-between;">';
                    echo '                <h4><a href="#">' . htmlspecialchars($row["name"]) . '</a></h4>';
                    echo '                <div>';
                    echo '                    <a href="edit_drama.php?id=' . htmlspecialchars($row["id"]) . '"><i class="fa fa-pencil fa-1x"></i></a>';
                    echo '                    <a href="delete_drama.php?id=' . htmlspecialchars($row["id"]) . '"><i class="fa fa-trash"></i></a>';
                    echo '                </div>';
                    echo '            </div>';
                    echo '           <a href="view_season.php?drama_id=' . htmlspecialchars($row["id"]) . '" class="btn btn-view-seasons mt-2">
    <i class="fas fa-layer-group me-2"></i>View
</a>
     '                                                             ;
                    echo '        </div>';
                    echo '    </div>';
                    echo '</li>';
                }
            } else {
                echo "No dramas found.";
            }
            $conn->close();
            ?>
        </ul>
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