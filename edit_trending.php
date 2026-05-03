<?php
include "config.php";
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);

$fetch_sql = "
    SELECT td.id, td.position, d.id AS drama_id, d.name 
    FROM trending_dramas td
    JOIN drama d ON td.drama_id = d.id
    WHERE td.id = ?
";
$stmt = $conn->prepare($fetch_sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();
$stmt->close();

$drama_result = $conn->query("SELECT id, name FROM drama ORDER BY name ASC");

if (isset($_POST['update_trending'])) {
    $drama_id = intval($_POST['drama_id']);
    $position = intval($_POST['position']);

    $check_position = $conn->prepare("SELECT id FROM trending_dramas WHERE position = ? AND id != ?");
    $check_position->bind_param("ii", $position, $id);
    $check_position->execute();
    $check_position_result = $check_position->get_result();

    if ($check_position_result->num_rows > 0) {
        $_SESSION['error'] = "This position is already assigned.";
    } else {
        $check_drama = $conn->prepare("SELECT id FROM trending_dramas WHERE drama_id = ? AND id != ?");
        $check_drama->bind_param("ii", $drama_id, $id);
        $check_drama->execute();
        $check_drama_result = $check_drama->get_result();

        if ($check_drama_result->num_rows > 0) {
            $_SESSION['error'] = "This drama is already assigned a position.";
        } else {
            $update_sql = "UPDATE trending_dramas SET drama_id = ?, position = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iii", $drama_id, $position, $id);
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Trending drama updated successfully.";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'trendingdrama.php';
                    }, 1000);
                </script>";
                exit;
            } else {
                $_SESSION['error'] = "Error updating record.";
            }
            $update_stmt->close();
        }
        $check_drama->close();
    }
    $check_position->close();
}
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
    <div class="container mt-4">
        <h4>Edit Trending Drama</h4>

        <?php
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success'>" . $_SESSION['success'] . "</div>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-danger'>" . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="drama_id">Drama</label>
                <select name="drama_id" id="drama_id" class="form-control" required>
                    <?php while ($drama = $drama_result->fetch_assoc()) { ?>
                        <option value="<?php echo $drama['id']; ?>" <?php echo ($drama['id'] == $entry['drama_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($drama['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="position">Position</label>
                <input type="number" name="position" id="position" class="form-control" min="1" required value="<?php echo $entry['position']; ?>">
            </div>

            <button type="submit" name="update_trending" class="btn btn-primary">Update</button>
            <a href="your_main_page.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
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