<!DOCTYPE html>
<html>
<head>
    <!-- Basic Page Info -->
    <meta charset="utf-8">
    <title>Admin Panel - Upload APK</title>

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
			   <!--google material icon-->
			   <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">
<link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
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
                            <h4 class="mb-0">Admin Panel</h4>
                            <small>Upload your latest APK build here</small>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb bg-transparent mb-0 p-0">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Upload APK</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            

            <!-- APK Upload Form Start -->
            <div class="pd-20 card-box mb-30">
                <div class="clearfix">
                    <div class="section-header-custom">
                        <h4 class="section-title-custom">Upload APK File</h4>
                        <p class="section-subtitle-custom">Please upload the APK file below.</p>
                    </div>
                </div>
                

                <form action="apk.php" method="POST" enctype="multipart/form-data">
                  <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">APK String</label>
                        <div class="col-sm-12 col-md-10">
                            <input class="form-control" name="apk_str" type="text"  required>
                        </div>
                    </div>
                    <div class="form-group row custom-file-upload-group">
                        <label class="col-sm-12 col-md-2 col-form-label">APK File</label>
                        <div class="col-sm-12 col-md-10">
                            <label for="apk-upload" class="custom-file-icon-label" title="Upload APK">
                                <img src="img/pngwing.com.png" alt="Upload Icon" class="upload-icon-img">
                            </label>
                            <input type="file" class="custom-file-input" id="apk-upload" name="apk_file" accept=".apk" required>
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <div class="col-sm-12 col-md-2"></div>
                        <div class="col-sm-12 col-md-10">
                            <button type="submit" name="upload_apk" class="btn btn-custom-red">Upload APK</button>
                        </div>
                    </div>
                    
                </form>
            </div>
            <!-- APK Upload Form End -->
        </div>
    </div>

    <!-- JS -->
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
