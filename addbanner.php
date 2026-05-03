<?php
include "config.php";
$banner_image_url = '';
$banner_video_url = '';
$query = "SELECT image_url, video_url FROM banners LIMIT 1";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $banner_image_url = $row['image_url'];
    $banner_video_url = $row['video_url'];
}
?>

<!DOCTYPE html>
<html>

<head>
    <!-- Basic Page Info -->
    <meta charset="utf-8">
    <title>Admin Panel - Add Banner</title>

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
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <!----css3---->
    <link rel="stylesheet" href="vendors/styles//css/custom.css">
    <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
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
                            <h4 class="mb-0">Add Banner</h4>
                            <small>Fill the form below to add a new banner entry</small>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb bg-transparent mb-0 p-0">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add Banner</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>


            <!-- Banner Form Start -->
            <div class="pd-20 card-box mb-30">
                <div class="clearfix">
                    <div class="section-header-custom">
                        <h4 class="section-title-custom">Add Banner</h4>
                        <p class="section-subtitle-custom">Upload Banner Image and Video</p>
                    </div>
                </div>


                <form action="add_banner.php" method="POST" enctype="multipart/form-data">
                    <h4 class="text-blue h4" style="color: rgb(55, 66, 55);">Add Banner Image</h4>
                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Choose Banner Image</label>
                        <div class="col-sm-12 col-md-10">
                            <input type="text" class="form-control" id="banner-image-upload" name="banner_image" required value="<?php echo htmlspecialchars($banner_image_url); ?>">
                            
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-12 col-md-2"></div>
                        <div class="col-sm-12 col-md-10">
                            <button type="submit" class="btn btn-outline-custom-green" name="submit_image">Upload Image</button>
                        </div>
                    </div>

                </form>

                <form action="add_banner.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                    <h4 class="text-blue h4" style="color:rgb(31, 76, 121);">Add Banner Video</h4>
                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Choose Banner Video</label>
                        <div class="col-sm-12 col-md-10">
                            <input type="text" class="form-control" id="banner-video-upload" name="banner_video" required value="<?php echo htmlspecialchars($banner_video_url); ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-12 col-md-2"></div>
                        <div class="col-sm-12 col-md-10">
                            <button type="submit" class="btn btn-custom-red" name="submit_video">Upload Video</button>
                        </div>
                    </div>

                </form>


            </div>
            <!-- Banner Form End -->
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
        $(document).ready(function() {
            $(".xp-menubar").on('click', function() {
                $("#sidebar").toggleClass('active');
                $("#content").toggleClass('active');
            });

            $('.xp-menubar,.body-overlay').on('click', function() {
                $("#sidebar,.body-overlay").toggleClass('show-nav');
            });

        });
    </script>
</body>

</html>