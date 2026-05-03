<?php
include "config.php";
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
// Fetch notifications
$sql = "SELECT * FROM notifications ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<!-- Basic Page Info -->
<meta charset="utf-8">
<title>DeskApp - Notifications</title>
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
                    <h4 class="mb-0">Notifications</h4>
                    <small>Manage your notifications here</small>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-0 p-0">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Notifications</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
   
    <!-- Notifications Section -->
    <div class="pd-20 card-box mb-30">
        <div class="clearfix">
            <div class="section-header-custom d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="section-title-custom">All Notifications</h4>
                    <p class="section-subtitle-custom">View and manage all notifications</p>
                </div>
                <a href="add_notifaction.php" class="btn btn-custom-red btn-sm">
                    <i class="fa fa-plus"></i> Add Notification
                </a>
            </div>
        </div>
       
        <!-- Desktop View -->
        <div class="table-responsive mt-4 d-none d-lg-block">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-head-custom text-nowrap">
                    <tr>
                        <th>Notification</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo ' <td class="align-middle">';
                            // Show image (if present), title, and message
                            if (!empty($row["image"])) {
                                echo '<img src="' . htmlspecialchars($row["image"]) . '" alt="Notification Image" style="max-width:150px; max-height:100px; border-radius:6px; margin-bottom:10px;">';
                            }
                            if (!empty($row["title"]) || !empty($row["message"])) {
                                echo '<div>';
                                if (!empty($row["title"])) {
                                    echo '<strong>' . htmlspecialchars($row["title"]) . '</strong><br>';
                                }
                                if (!empty($row["message"])) {
                                    echo '<span>' . nl2br(htmlspecialchars($row["message"])) . '</span>';
                                }
                                echo '</div>';
                            }
                            if (empty($row["image"]) && empty($row["title"]) && empty($row["message"])) {
                                echo '<span class="text-muted">No content</span>';
                            }
                            echo ' </td>';
                            echo ' <td class="align-middle">' . htmlspecialchars($row["created_at"]) . '</td>';
                            echo ' <td class="align-middle">';
                            echo ' <div class="d-flex flex-column flex-lg-row gap-2 w-100">';
                            echo ' <a href="edit_notification.php?id=' . $row["id"] . '" class="btn btn-custom-red btn-sm w-100 w-lg-auto">Edit</a>';
                            echo ' <a href="delete_notification.php?id=' . $row["id"] . '" class="btn btn-custom-red btn-sm w-100 w-lg-auto">Delete</a>';
                            echo ' </div>';
                            echo ' </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3" class="text-center">No notifications found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
       
        <!-- Mobile View -->
        <div class="d-lg-none mt-4">
            <?php
            if ($result->num_rows > 0) {
                mysqli_data_seek($result, 0);
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="card p-3 mb-3 shadow-sm">';
                    echo ' <div class="d-flex flex-column">';
                    // Show image (if present), title, and message
                    if (!empty($row["image"])) {
                        echo '<img src="' . htmlspecialchars($row["image"]) . '" alt="Notification Image" style="max-width:100%; border-radius:6px; margin-bottom:10px;">';
                    }
                    echo '<div>';
                    if (!empty($row["title"])) {
                        echo '<h5 class="mb-1">' . htmlspecialchars($row["title"]) . '</h5>';
                    }
                    if (!empty($row["message"])) {
                        echo '<p class="mb-2">' . nl2br(htmlspecialchars($row["message"])) . '</p>';
                    }
                    if (empty($row["image"]) && empty($row["title"]) && empty($row["message"])) {
                        echo '<p class="mb-2 text-muted">No content</p>';
                    }
                    echo ' <div class="small text-muted">' . htmlspecialchars($row["created_at"]) . '</div>';
                    echo '</div>';
                    echo ' </div>';
                    echo ' <div class="mt-2">';
                    echo ' <a href="edit_notification.php?id=' . $row["id"] . '" class="btn btn-custom-red btn-sm">Edit</a>';
                    echo ' <a href="delete_notification.php?id=' . $row["id"] . '" class="btn btn-custom-red btn-sm">Delete</a>';
                    echo ' </div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="text-center text-muted">No notifications found.</div>';
            }
            ?>
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