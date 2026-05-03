<?php
session_start();
include "config.php";
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
// Fetch notification details
$id = $_GET['id'];
$sql = "SELECT * FROM notifications WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$notification = $result->fetch_assoc();
if (!$notification) {
    header("Location: show_notifaction.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $message = $_POST['message'] ?? '';
    $delete_image = isset($_POST['delete_image']);
    $image_path = $notification['image'] ?? '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/notifications/';
        // Ensure the upload directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $image_path = $upload_dir . $image_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            // Delete old image if exists
            if (!empty($notification['image']) && file_exists($notification['image'])) {
                unlink($notification['image']);
            }
        } else {
            $image_path = $notification['image']; // Keep old image if upload fails
        }
    } elseif ($delete_image && !empty($notification['image']) && file_exists($notification['image'])) {
        unlink($notification['image']);
        $image_path = '';
    }

    // Update notification
    $sql = "UPDATE notifications SET title = ?, message = ?, image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $title, $message, $image_path, $id);
    if ($stmt->execute()) {
        header("Location: show_notifaction.php");
        exit;
    } else {
        echo "Error updating notification: " . $stmt->error;
    }
    $stmt->close();
}
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
<link rel="stylesheet" href="vendors/styles/css/custom.css">
<!--google material icon-->
<link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
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
                    <h4 class="mb-0">Edit Notification</h4>
                    <small>Update the notification details below</small>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Notification</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
   
    <!-- Default Basic Forms Start -->
    <div class="pd-20 card-box mb-30">
        <div class="clearfix">
            <div class="section-header-custom">
                <h4 class="section-title-custom">EDIT NOTIFICATION</h4>
                <p class="section-subtitle-custom">Update notification with title, message, and/or image</p>
            </div>
        </div>
       
        <form action="" method="POST" enctype="multipart/form-data" id="notificationForm">
            <!-- Notification details fields -->
            <div class="notification-container">
                <div class="notification">
                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Title:</label>
                        <div class="col-sm-12 col-md-10">
                            <input class="form-control" placeholder="Enter Notification Title" type="text" name="title" id="title" value="<?php echo htmlspecialchars($notification['title']); ?>" />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Message:</label>
                        <div class="col-sm-12 col-md-10">
                            <textarea class="form-control" placeholder="Enter Notification Message" rows="4" name="message" id="message"><?php echo htmlspecialchars($notification['message']); ?></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Image:</label>
                        <div class="col-sm-12 col-md-10">
                            <?php if (!empty($notification['image'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($notification['image']); ?>" alt="Current Image" style="max-width:150px; max-height:100px; border-radius:6px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="delete_image" id="deleteImage">
                                        <label class="form-check-label" for="deleteImage">Delete Current Image</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <input class="form-control" type="file" name="image" id="image" accept="image/*" />
                        </div>
                    </div>
                </div>
            </div>
           
            <!-- Submit button -->
            <div class="form-group row">
                <div class="col-sm-12 col-md-2"></div>
                <div class="col-sm-12 col-md-10">
                    <button type="submit" class="btn btn-custom-red">Update Notification</button>
                </div>
            </div>
        </form>
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