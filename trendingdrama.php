<?php
include "config.php";
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['assign_position'])) {
    $drama_id = intval($_POST['drama_id']);
    $position = intval($_POST['position']);

    $check_position_sql = "SELECT id FROM trending_dramas WHERE position = ?";
    $check_position_stmt = $conn->prepare($check_position_sql);
    $check_position_stmt->bind_param("i", $position);
    $check_position_stmt->execute();
    $check_position_result = $check_position_stmt->get_result();

    if ($check_position_result->num_rows > 0) {
        $_SESSION['error'] = "This position is already assigned to another drama.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    $check_position_stmt->close();

    $check_drama_sql = "SELECT id FROM trending_dramas WHERE drama_id = ?";
    $check_drama_stmt = $conn->prepare($check_drama_sql);
    $check_drama_stmt->bind_param("i", $drama_id);
    $check_drama_stmt->execute();
    $check_drama_result = $check_drama_stmt->get_result();

    if ($check_drama_result->num_rows > 0) {
        $_SESSION['error'] = "This drama is already assigned a position.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    $check_drama_stmt->close();

    $insert_sql = "INSERT INTO trending_dramas (drama_id, position, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param("ii", $drama_id, $position);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Drama assigned successfully';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $_SESSION['error'] = "Error inserting data: " . $stmt->error;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Error preparing insert: " . $conn->error;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

if (isset($_SESSION['success'])) {
    echo "<script>alert('" . $_SESSION['success'] . "');</script>";
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo "<script>alert('" . $_SESSION['error'] . "');</script>";
    unset($_SESSION['error']);
}

$trending_sql = "
    SELECT td.id, td.position, d.name AS drama_name, d.thumbnail 
    FROM trending_dramas td
    JOIN drama d ON td.drama_id = d.id
    ORDER BY td.position ASC
";
$trending_result = $conn->query($trending_sql);

$current_admin_id = $_SESSION['admin_id'];
$sql = "SELECT admin_type FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing SQL: " . $conn->error);
}
$stmt->bind_param("i", $current_admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $current_admin = $result->fetch_assoc();
    $current_admin_type = $current_admin['admin_type'];
} else {
    echo "Current admin not found.";
    exit;
}
$stmt->close();

$drama_query = "SELECT id, name FROM drama ORDER BY name ASC";
$drama_result = $conn->query($drama_query);
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
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
</head>

<body>
    <?php include "header.php"; ?>
    <div class="container">

        <div class="card mb-3">
            <div class="card-header">
                <div class="clearfix">
                    <div class="section-header-custom">
                        <h4 class="section-title-custom">Trending Dramas</h4>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="drama_id">Select Drama:</label>
                        <select id="drama_id" name="drama_id" class="form-control" required>
                            <option value="" disabled selected>Select Drama</option>
                            <?php while ($drama = $drama_result->fetch_assoc()) { ?>
                                <option value="<?php echo $drama['id']; ?>">
                                    <?php echo htmlspecialchars($drama['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="position">Assign Position:</label>
                        <input type="number" id="position" name="position" class="form-control" min="1" required>
                    </div>

                    <button type="submit" name="assign_position" class="btn btn-custom-red">Assign</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Assigned Trending Dramas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Drama Name</th>
                                    <th>Thumbnail</th>
                                    <th>Position</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $index = 1;
                                while ($row = $trending_result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . $index++ . '</td>';
                                    echo '<td>' . htmlspecialchars($row['drama_name']) . '</td>';
                                    echo '<td><img src="' . htmlspecialchars($row['thumbnail']) . '" alt="Thumbnail" style="height: 60px;"></td>';
                                    echo '<td>' . $row['position'] . '</td>';
                                    echo '<td>
                                                <a href="edit_trending.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="delete_trending.php?id=' . $row['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this entry?\');">Delete</a>
                                            </td>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Include jQuery and Select2 JS -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script src="vendors/js/jquery-3.3.1.slim.min.js"></script>
        <script src="vendors/js/popper.min.js"></script>

        <script src="vendors/js/jquery-3.3.1.min.js"></script>

        <script src="vendors/scripts/core.js"></script>
        <script src="vendors/scripts/script.min.js"></script>
        <script src="vendors/scripts/process.js"></script>
        <script src="vendors/scripts/layout-settings.js"></script>
        <script src="src/plugins/jQuery-Knob-master/jquery.knob.min.js"></script>
        <script src="src/plugins/highcharts-6.0.7/code/highcharts.js"></script>
        <script src="src/plugins/highcharts-6.0.7/code/highcharts-more.js"></script>
        <script src="src/plugins/jvectormap/jquery-jvectormap-2.0.3.min.js"></script>
        <script src="src/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
        <script src="vendors/scripts/dashboard2.js"></script>

</body>

</html>