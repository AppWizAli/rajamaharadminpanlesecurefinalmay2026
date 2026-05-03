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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "Invalid season ID.";
    exit;
}

// Retrieve season details with associated drama details from database
$sql = "SELECT s.*, d.id AS drama_id FROM season s INNER JOIN drama d ON s.drama_id = d.id WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $season_number = $row['season_number'];
    $total_episodes = $row['total_episodes'];
    $thumbnail = $row['thumbnail'];
    $drama_id = $row['drama_id']; // Fetch drama ID here

    // Display form for editing season details
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
        <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
        <!----css3---->
        <link rel="stylesheet" href="vendors/styles//css/custom.css">
        <!--google material icon-->
        <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
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
                                <h4 class="mb-0">Edit Seasons</h4>
                                <small>Modify details for existing seasons below</small>
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb bg-transparent mb-0 p-0">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Edit Seasons</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Default Basic Forms Start -->
                <div class="pd-20 card-box mb-30">
                    <div class="clearfix">
                        <div class="section-header-custom">
                            <h4 class="section-title-custom">Edit Seasons</h4>
                            <p class="section-subtitle-custom">Modify or update existing seasons</p>
                        </div>
                    </div>

                    <form action="update_season.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="season_id" value="<?php echo htmlspecialchars($id); ?>">
                        <input type="hidden" name="drama_id" value="<?php echo htmlspecialchars($drama_id); ?>">


                        <div class="form-group row">
                            <label class="col-sm-12 col-md-2 col-form-label">Season No:</label>
                            <div class="col-sm-12 col-md-10">
                                <input
                                    class="form-control"
                                    name="season_number"
                                    placeholder="Enter Season Number"
                                    type="number"
                                    required
                                    value="<?php echo htmlspecialchars($season_number); ?>" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-12 col-md-2 col-form-label">Total Episodes:</label>
                            <div class="col-sm-12 col-md-10">
                                <input
                                    class="form-control"
                                    name="total_episodes"
                                    type="number"
                                    placeholder="Enter total number of Episodes"
                                    required
                                    value="<?php echo htmlspecialchars($total_episodes); ?>" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-12 col-md-2 col-form-label">Thumbnail Path:</label>
                            <div class="col-sm-12 col-md-10">
                                <input type="text" class="form-control" id="thumbnail-upload" placeholder="https://example.com/season.jpg or uploads/thumbnails/season/file.jpg" name="thumbnail" value="<?php echo htmlspecialchars($thumbnail); ?>">
                                <small class="form-text text-muted">Paste an image link/path or choose a replacement image below.</small>
                                <input class="form-control mt-2" name="thumbnail_file" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-12 col-md-2"></div>
                            <div class="col-sm-12 col-md-10">
                                <button type="submit" class="btn btn-custom-red">
                                    <i class="fas fa-sync-alt"></i> Update Season
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Default Basic Forms End -->


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
<?php
} else {
    echo "No drama found with ID: " . $id;
}

// Close the statement and database connection
$stmt->close();
$conn->close();
?>
