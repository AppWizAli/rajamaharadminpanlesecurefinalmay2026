<?php
// Include your database connection file (config.php or similar)
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page or any other page you prefer
    header("Location: login.php");
    exit;
}

// Retrieve user ID from query string parameter
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    echo "Invalid user ID.";
    exit;
}

// Retrieve user details from database
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $username = $row['username'];
    $email = $row['email'];
    $password = $row['password'];
    $profile_image = $row['profile_image'];
} else {
    echo "User not found.";
    exit;
}

$stmt->close();
$conn->close();
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
	<!-- Global site tag (gtag.js) - Google Analytics -->
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
							<h4 class="mb-0">Edit User</h4>
							<small>Update user information using the form below</small>
						</div>
						<nav aria-label="breadcrumb">
							<ol class="breadcrumb bg-transparent mb-0 p-0">
								<li class="breadcrumb-item"><a href="index.php">Home</a></li>
								<li class="breadcrumb-item active" aria-current="page">Edit User</li>
							</ol>
						</nav>
					</div>
				</div>
			</div>
			
          <!-- Default Basic Forms Start -->
          <div class="pd-20 card-box mb-30">
			<div class="clearfix">
				<div class="section-header-custom">
					<h4 class="section-title-custom">EDIT USER</h4>
					<p class="section-subtitle-custom">Update existing user information</p>
				</div>
			</div>
			
            <form action="update_user.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
    
    <div class="form-group row">
        <label class="col-sm-12 col-md-2 col-form-label">User</label>
        <div class="col-sm-12 col-md-10">
            <input class="form-control" type="text" name="username" placeholder="Enter User Name" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-12 col-md-2 col-form-label">Email</label>
        <div class="col-sm-12 col-md-10">
            <input class="form-control" type="text" name="email" placeholder="xyz@gmail.com" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-12 col-md-2 col-form-label">Password</label>
        <div class="col-sm-12 col-md-10">
            <input class="form-control" type="password" name="password"  value="<?php echo htmlspecialchars($password); ?>"  placeholder="Enter Password">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-12 col-md-2 col-form-label">Current Profile Image</label>
        <div class="col-sm-12 col-md-10">
            <?php if (!empty($profile_image)) : ?>
                <img src='users/<?php echo $profile_image; ?>' alt="Current Profile Image" style="width: 100px;">
            <?php else : ?>
                <p>No image uploaded.</p>
            <?php endif; ?>
        </div>
    </div>
	<div class="form-group row custom-file-upload-group">
		<label class="col-sm-12 col-md-2 col-form-label">New Profile Image</label>
		<div class="col-sm-12 col-md-10">
			<label for="profile-image-upload" class="custom-file-icon-label" title="Upload Profile Image">
				<img src="img/pngwing.com.png" alt="Upload Icon" class="upload-icon-img">
			</label>
			<input type="file" class="custom-file-input" id="profile-image-upload" name="profile_image">
		</div>
	</div>
	
	<div class="form-group row">
		<div class="col-sm-12 col-md-2"></div>
		<div class="col-sm-12 col-md-10">
			<button type="submit" class="btn btn-custom-red">Update</button>
		</div>
	</div>
	
</form>

            <div class="collapse collapse-box" id="basic-form1">
              <div class="code-box">
                
                <pre><code class="xml copy-pre" id="copy-pre">
<form>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Text</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" type="text" placeholder="Johnny Brown">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Search</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" placeholder="Search Here" type="search">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Email</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" value="bootstrap@example.com" type="email">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">URL</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" value="https://getbootstrap.com" type="url">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Telephone</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" value="1-(111)-111-1111" type="tel">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Password</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" value="password" type="password">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Number</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" value="100" type="number">
		</div>
	</div>
	<div class="form-group row">
		<label for="example-datetime-local-input" class="col-sm-12 col-md-2 col-form-label">Date and time</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control datetimepicker" placeholder="Choose Date anf time" type="text">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Date</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control date-picker" placeholder="Select Date" type="text">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Month</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control month-picker" placeholder="Select Month" type="text">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Time</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control time-picker" placeholder="Select time" type="text">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Select</label>
		<div class="col-sm-12 col-md-10">
			<select class="custom-select col-12">
				<option selected="">Choose...</option>
				<option value="1">One</option>
				<option value="2">Two</option>
				<option value="3">Three</option>
			</select>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Color</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" value="#563d7c" type="color">
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-12 col-md-2 col-form-label">Input Range</label>
		<div class="col-sm-12 col-md-10">
			<input class="form-control" value="50" type="range">
		</div>
	</div>
</form>
							</code></pre>
              </div>
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
