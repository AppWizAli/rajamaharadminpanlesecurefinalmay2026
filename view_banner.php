<?php
session_start();
include "config.php"; // Include your database connection file

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Check if delete button is clicked
if (isset($_POST['delete_banner'])) {
    // Delete existing banner from the database
    $delete_sql = "SELECT image_url, video_url FROM banners LIMIT 1";
    $result = $conn->query($delete_sql);
    if ($result && $row = $result->fetch_assoc()) {
        // Delete the previous image and video files if they exist
        if ($row['image_url'] && file_exists($row['image_url'])) {
            unlink($row['image_url']);
        }
        if ($row['video_url'] && file_exists($row['video_url'])) {
            unlink($row['video_url']);
        }
        // Clear the banners table
        $conn->query("DELETE FROM banners");
    }
}

// Query to fetch the latest banner
$sql = "SELECT image_url, video_url FROM banners LIMIT 1";
$result = $conn->query($sql);

// Check if there is a banner to display
if ($result && $row = $result->fetch_assoc()) {
    $image_url = $row['image_url'];
    $video_url = $row['video_url'];
} else {
    $image_url = "";
    $video_url = "";
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Basic Page Info -->
    <meta charset="utf-8">
    <title>DeskApp - Manage Banners</title>

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
        <!----css3---->
        <link rel="stylesheet" href="vendors/styles//css/custom.css">
			   <!--google material icon-->
			   <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTTXRVUOfLQbX3HBi/N+2qxY7M6E3wOe7XclU9z5PQ7qnxHXy5zHsvyVo+PQ4NkT9Kq8B0fFZg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .banner-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .banner-content {
            width: 48%; /* Adjust width as needed */
        }
        img {
            max-width: 100%; /* Responsive image */
            height: auto;
        }
        video {
            max-width: 100%; /* Responsive video */
            height: auto;
        }
    </style>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
</head>
<body>
    <?php include "header.php"; ?>
    <div class="main-container">
        <div class="xs-pd-20-10 pd-ltr-20">
        <div class="page-header">
    <div class="dashboard-header mb-4 p-3 rounded shadow-sm bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="page-title">
                <h4 class="mb-0">Manage Banners</h4>
                <small>View and manage all the uploaded banners</small>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 p-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Manage Banners</li>
                </ol>
            </nav>
        </div>
    </div>
</div>


            <div class="table-responsive mt-4">
                <table class="table table-bordered banner-table">
                    <thead>
                        <tr>
                            <th>Banner Type</th>
                            <th>Banner Content</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Display Banner Image if it exists -->
                        <tr>
                            <td>Banner Image</td>
                            <td>
                                <div class="banner-container">
                                    <div class="banner-content">
                                        <?php if (!empty($image_url)) : ?>
                                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Banner Image">
                                        <?php else : ?>
                                            <p>No banner image available.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <!-- Display Banner Video if it exists -->
                        <tr>
                            <td>Banner Video</td>
                            <td>
                                <div class="banner-container">
                                    <div class="banner-content">
                                        <?php if (!empty($video_url)) : ?>
                                            <video controls>
                                                <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php else : ?>
                                            <p>No banner video available.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <!-- Form to Delete Banner -->
<form action="" method="POST">
    <div class="form-group row">
        <div class="col-sm-12 col-md-2"></div>
        <div class="col-sm-12 col-md-10">
            <button type="submit" name="delete_banner" class="btn btn-custom-red">
                <i class="fas fa-trash-alt"></i> Delete Banner
            </button>
        </div>
    </div>
</form>

        </div>
    </div>

    <!-- JS -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
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
