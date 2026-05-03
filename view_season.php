<?php
include "config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get drama ID from URL
$drama_id = isset($_GET['drama_id']) ? intval($_GET['drama_id']) : 0;

if ($drama_id > 0) {
    // Fetch seasons for the drama, along with the drama name
    $sql = "SELECT season.*, drama.name AS drama_name 
            FROM season 
            JOIN drama ON season.drama_id = drama.id 
            WHERE season.drama_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $drama_id);
} else {
    // If no drama_id is provided, fetch all seasons
    $sql = "SELECT season.*, drama.name AS drama_name 
            FROM season 
            JOIN drama ON season.drama_id = drama.id";
    $stmt = $conn->prepare($sql);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
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
                            <h4 class="mb-0">Dashboard</h4>
                            <small>Overview and manage seasons</small>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb bg-transparent mb-0 p-0">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Seasons</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="page-header">
                <div class="row">
                    <div class="col-md-12 col-sm-12 d-flex justify-content-between">
                        <div class="title">
                            <h3 class="section-title-red text-center">All Seasons</h3>
                        </div>
                        <a href="add_season.php?drama_id=<?php echo $drama_id; ?>" class="btn btn-outline-custom-green btn-sm" type="button">
                            <i class="fa fa-plus"></i>
                            ADD Seasons
                        </a>
                    </div>

                    <!-- Desktop/Laptop View (Table) -->
                    <div class="table-responsive mt-4 d-none d-lg-block">
                        <table class="table table-borderless table-striped">
                            <thead class="table-head-custom">
                                <tr>
                                    <th>Thumbnail</th>
                                    <th>Season No:</th>
                                    <th>Publish Date</th>
                                    <th>View</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '    <td>';
                                        echo '        <div class="product-img">';
                                        echo '            <img src="' . htmlspecialchars($row["thumbnail"]) . '" alt="Thumbnail" class="img-thumbnail" style="width: 80px; height: auto;">';
                                        echo '        </div>';
                                        echo '    </td>';

                                        echo '    <td>' . $row["season_number"] . '</td>';
                                        echo '    <td>' . date('d M Y - h:i A', strtotime($row["created_at"])) . '</td>';
                                        echo '    <td>
                                            <a href="add_episode.php?season_id=' . $row["id"] . '" class="btn btn-primary btn-sm mb-1">
                                                <i class="fa fa-plus"></i> Add
                                            </a>
                                            <a href="view_episods.php?season_id=' . $row["id"] . '" class="btn btn-info btn-sm">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </td>';
                                        echo '    <td>
                                            <a href="delete_season.php?id=' . $row["id"] . '" class="btn btn-danger btn-sm mb-1">
                                                <i class="fa fa-trash"></i> Del
                                            </a>
                                            <a href="edit_season.php?id=' . $row["id"] . '" class="btn btn-warning btn-sm">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                        </td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5">No seasons found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile/Tablet View (Cards) -->
                    <div class="d-lg-none">
                        <?php
                        if ($result->num_rows > 0) {
                            mysqli_data_seek($result, 0); // Reset result pointer
                            while ($row = $result->fetch_assoc()) {
                                echo '<div class="card mb-3 shadow-sm">';
                                echo '  <div class="card-body">';
                                echo '      <div class="d-flex align-items-center mb-3">';
                                echo '          <h6 class="mb-1">Thumbnail Path: ' . $row["thumbnail"] . '</h6>';
                                echo '          <div>';
                                echo '              <h6 class="mb-1">Season No: ' . $row["season_number"] . '</h6>';
                                echo '              <small class="text-muted">Published: ' . $row["created_at"] . '</small>';
                                echo '          </div>';
                                echo '      </div>';

                                echo '      <hr>';

                                echo '      <div class="d-flex justify-content-between flex-wrap">';
                                echo '          <div class="mb-2">';
                                echo '              <a href="add_episode.php?season_id=' . $row["id"] . '" class="btn btn-primary btn-sm me-1 mb-1"><i class="fa fa-plus"></i> Add</a>';
                                echo '              <a href="view_episods.php?season_id=' . $row["id"] . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-eye"></i> View</a>';
                                echo '          </div>';
                                echo '          <div>';
                                echo '              <a href="delete_season.php?id=' . $row["id"] . '" class="btn btn-danger btn-sm me-1 mb-1"><i class="fa fa-trash"></i> Del</a>';
                                echo '              <a href="edit_season.php?id=' . $row["id"] . '" class="btn btn-warning btn-sm mb-1"><i class="fa fa-edit"></i> Edit</a>';
                                echo '          </div>';
                                echo '      </div>';
                                echo '  </div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="text-center text-muted">No seasons found.</div>';
                        }
                        ?>
                    </div>



                </div>
            </div>
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