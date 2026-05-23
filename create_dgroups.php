<?php
include "config.php";
include "subscription_schema.php";
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch the current admin's type
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

// Handle group creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_group'])) {
    $group_name = isset($_POST['group_name']) ? $_POST['group_name'] : '';
    $group_comment = isset($_POST['group_comment']) ? $_POST['group_comment'] : '';

    if (!empty($group_name) && !empty($group_comment)) {
        $sql = "INSERT INTO `groups` (group_name, group_comment) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing SQL: " . $conn->error);
        }
        $stmt->bind_param("ss", $group_name, $group_comment);
        if ($stmt->execute()) {
            echo "Group created successfully.";
        } else {
            echo "Error creating group: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Please fill in all required fields.";
    }
}

// Handle adding user to group
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user_to_group'])) {
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $subscription = 0;

    // Use start_date from POST or default to today
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    // Use end_date from POST or default to 31 days after start_date
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime($start_date . ' +31 days'));
    $created_at = date('Y-m-d H:i:s'); // Keep the current timestamp

    if ($group_id > 0 && $user_id > 0) {
        // Check if user is already in the group
        $sql = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing SQL: " . $conn->error);
        }
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Insert user into group
            $sql = "INSERT INTO group_members (group_id, user_id, comment, subscription, start_date, end_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("Error preparing SQL: " . $conn->error);
            }
            $stmt->bind_param("iisisss", $group_id, $user_id, $comment, $subscription, $start_date, $end_date, $created_at);
            if ($stmt->execute()) {
                $settings = get_subscription_settings($conn);
                subscription_create_approved_invoice_record($conn, [
                    'user_id' => $user_id,
                    'group_id' => $group_id,
                    'amount' => $settings['monthly_amount'] ?? '0.00',
                    'currency' => $settings['currency'] ?? 'PKR',
                    'payment_method' => 'Admin Assignment',
                    'note' => $comment,
                    'admin_note' => 'User added to group manually from direct group management.',
                    'details_snapshot' => subscription_build_details_snapshot($settings),
                    'subscription_start_date' => $start_date,
                    'subscription_end_date' => $end_date,
                    'approved_by' => intval($_SESSION['admin_id'] ?? 0)
                ]);
                echo "User added to group successfully.";
            } else {
                echo "Error adding user to group: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "User is already in the group.";
        }
    } else {
        echo "Invalid group or user selected.";
    }
}

// Fetch groups and users for the form
$groups1 = $conn->query("SELECT * FROM `groups`");
$users = $conn->query("SELECT * FROM users");
if ($groups1 === false || $users === false) {
    die("Error fetching data: " . $conn->error);
}

$groups2 = $conn->query("SELECT * FROM `groups`");
$private_episodes = $conn->query("SELECT * FROM episode WHERE privacy = 'private'");
if ($groups2 === false || $private_episodes === false) {
    die("Error fetching data: " . $conn->error);
}

$dramas = $conn->query("SELECT * FROM drama");
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
    <style>
        .assign-mode-tabs .nav-link {
            border: 1px solid #d8deea;
            border-radius: 999px;
            color: #334155;
            background: #f8fafc;
            font-weight: 600;
            padding: 10px 18px;
            margin-right: 10px;
        }

        .assign-mode-tabs .nav-link.active {
            background: #2f66c7;
            border-color: #2f66c7;
            color: #fff;
            box-shadow: 0 10px 25px rgba(47, 102, 199, 0.18);
        }

        .assign-mode-panel {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fbfdff;
            padding: 24px;
        }

        .assign-mode-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .assign-mode-copy {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 20px;
        }
    </style>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
</head>

<body>
    <?php include "header.php"; ?>
    <div class="container">

        <!-- Group Creation Form -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="clearfix">
                    <div class="section-header-custom">
                        <h4 class="section-title-custom">Create Group</h4>
                        <p class="section-subtitle-custom">Add All Types of Dramas</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="group_name">Group Name:</label>
                        <input type="text" id="group_name" name="group_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="group_comment">Group Comment:</label>
                        <textarea id="group_comment" name="group_comment" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="create_group" class="btn btn-custom-red">Create Group</button>
                </form>
            </div>
        </div>

        <!-- Add Users to Groups -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="clearfix">
                    <div class="section-header-custom">
                        <h4 class="section-title-custom">Add User to Group</h4>
                        <p class="section-subtitle-custom">Add Users to Your Group</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="group_id">Select Group:</label>
                        <select id="group_id" name="group_id" class="form-control" required>
                            <?php while ($group = $groups1->fetch_assoc()) { ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo $group['group_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="user_id">Select User:</label>
                        <select id="user_id" name="user_id" class="form-control select2" required>
                            <?php while ($user = $users->fetch_assoc()) { ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo $user['id'] . ' - ' . $user['username'] . ' - ' . $user['email']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comment">Comment (Optional):</label>
                        <textarea id="comment" name="comment" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="from-group d-flex">
                        <div class="form-group mr-2">
                            <label for="start_date">Subscription Start Date:</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" required>

                        </div>

                        <div class="form-group">
                            <label for="end_date">Subscription End Date:</label>
                            <input type="date" id="end_date" name="end_date" class="form-control">

                        </div>
                    </div>
                    <button type="submit" name="add_user_to_group" class="btn btn-custom-red">Add User to Group</button>
                </form>
            </div>



            <!-- Assign Videos to Groups -->
            <div class="card mb-3">
                <div class="card-header">
                    <div class="clearfix">
                        <div class="section-header-custom">
                            <h4 class="section-title-custom">Assign Videos to Group</h4>
                            <p class="section-subtitle-custom">Assign Videos to the Selected Group</p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <ul class="nav nav-pills assign-mode-tabs mb-4" id="assignModeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="manual-assign-tab" data-toggle="pill" href="#manual-assign-pane" role="tab" aria-controls="manual-assign-pane" aria-selected="true">Single Manual</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="bulk-assign-tab" data-toggle="pill" href="#bulk-assign-pane" role="tab" aria-controls="bulk-assign-pane" aria-selected="false">Multiple Dramas</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="assignModeTabsContent">
                        <div class="tab-pane fade show active" id="manual-assign-pane" role="tabpanel" aria-labelledby="manual-assign-tab">
                            <div class="assign-mode-panel">
                                <div class="assign-mode-title">Manual drama, season, and episode selection</div>
                                <p class="assign-mode-copy">Use this tab when you want full control over one drama. You can choose a single drama, pick one season or all seasons, then assign selected episodes.</p>

                                <form method="post" action="add_video.php" id="manualAssignForm">
                                    <input type="hidden" name="return_to" value="create_dgroups.php">
                                    <input type="hidden" id="manual_assign_all_dramas" name="assign_all_dramas" value="0">
                                    <input type="hidden" id="manual_assign_all_seasons" name="assign_all_seasons" value="0">

                                    <div class="form-group">
                                        <label for="manual_group_id">Select Group:</label>
                                        <select id="manual_group_id" name="group_id" class="form-control" required>
                                            <?php while ($group = $groups2->fetch_assoc()) { ?>
                                                <option value="<?php echo $group['id']; ?>"><?php echo $group['group_name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="manual_darama_id">Select Drama:</label>
                                        <select id="manual_darama_id" name="darama_id" class="form-control" required>
                                            <option value="">Please select a drama</option>
                                            <?php while ($drama = $dramas->fetch_assoc()) { ?>
                                                <option value="<?php echo $drama['id']; ?>"><?php echo $drama['name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="manual_season_id">Select Season:</label>
                                        <select id="manual_season_id" name="season_id" class="form-control" required>
                                            <option value="">Please select a drama first</option>
                                        </select>
                                    </div>

                                    <input type="hidden" id="manual_season_number" name="season_number" value="">

                                    <div class="form-group">
                                        <label for="manual_video_id">Select Episodes:</label>
                                        <div id="selectAllManualEpisodesWrapper">
                                            <input type="checkbox" id="selectAllManualEpisodes"> <label for="selectAllManualEpisodes">Select All</label>
                                        </div>

                                        <select id="manual_video_id" name="video_id[]" class="form-control" multiple required>
                                            <option value="">Please select a season first</option>
                                        </select>

                                        <small class="form-text text-muted">
                                            Hold down the Ctrl (Windows) or Command (Mac) key to select multiple options.
                                        </small>
                                        <small id="manualAssignHint" class="form-text text-muted" style="display: none;">
                                            All episodes from the selected scope will be assigned automatically when you submit.
                                        </small>
                                    </div>

                                    <button type="submit" name="assign_video_to_group" class="btn btn-custom-red">Assign Videos to Group</button>
                                </form>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="bulk-assign-pane" role="tabpanel" aria-labelledby="bulk-assign-tab">
                            <div class="assign-mode-panel">
                                <div class="assign-mode-title">Fast bulk assignment for many dramas</div>
                                <p class="assign-mode-copy">Use this tab when you want to assign many dramas at once. The system will automatically add all seasons and all episodes of the selected dramas to the chosen group.</p>

                                <form method="post" action="add_video.php" id="bulkAssignForm">
                                    <input type="hidden" name="return_to" value="create_dgroups.php">
                                    <input type="hidden" id="bulk_assign_all_dramas" name="assign_all_dramas" value="0">
                                    <input type="hidden" name="assign_all_seasons" value="0">

                                    <div class="form-group">
                                        <label for="bulk_group_id">Select Group:</label>
                                        <select id="bulk_group_id" name="group_id" class="form-control" required>
                                            <?php
                                            $groups_bulk = $conn->query("SELECT * FROM `groups`");
                                            if ($groups_bulk) {
                                                while ($group_bulk = $groups_bulk->fetch_assoc()) {
                                            ?>
                                                    <option value="<?php echo $group_bulk['id']; ?>"><?php echo $group_bulk['group_name']; ?></option>
                                            <?php
                                                }
                                                $groups_bulk->free();
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="bulk_assign_all_toggle">
                                            <label class="custom-control-label" for="bulk_assign_all_toggle">Assign every drama in one click</label>
                                        </div>
                                        <small id="bulkAssignAllHelp" class="form-text text-muted" style="display: none;">
                                            This will assign every drama, every season, and every episode to the selected group.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="bulk_drama_ids">Select Multiple Dramas:</label>
                                        <select id="bulk_drama_ids" name="bulk_drama_ids[]" class="form-control select2" multiple required>
                                            <?php
                                            $dramas_multi = $conn->query("SELECT * FROM drama");
                                            if ($dramas_multi) {
                                                while ($drama_multi = $dramas_multi->fetch_assoc()) {
                                            ?>
                                                    <option value="<?php echo $drama_multi['id']; ?>"><?php echo $drama_multi['name']; ?></option>
                                            <?php
                                                }
                                                $dramas_multi->free();
                                            }
                                            ?>
                                        </select>
                                        <small id="bulkAssignSelectionHelp" class="form-text text-muted">
                                            Select one or more dramas here. All seasons and all episodes of those dramas will be assigned automatically.
                                        </small>
                                    </div>

                                    <button type="submit" name="assign_video_to_group" class="btn btn-custom-red">Assign Selected Dramas</button>
                                </form>
                            </div>
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

            <!-- Initialize Select2 on the user dropdown -->
            <script>
                $(document).ready(function() {
                    $('#user_id').select2({
                        placeholder: 'Select a user',
                        allowClear: true
                    });

                    $('#manual_darama_id').select2({
                        placeholder: 'Select one drama',
                        allowClear: true,
                        width: '100%'
                    });

                    $('#bulk_drama_ids').select2({
                        placeholder: 'Select multiple dramas',
                        allowClear: true,
                        width: '100%'
                    });
                });
            </script>

            <script>
                $(document).ready(function() {
                    function resetManualEpisodes(message, disableSelection) {
                        $('#manual_video_id').html('<option value="">' + message + '</option>');
                        $('#manual_video_id').prop('disabled', disableSelection);
                        $('#manual_video_id').prop('required', !disableSelection);
                        $('#selectAllManualEpisodes').prop('checked', false);
                        $('#selectAllManualEpisodesWrapper').toggle(!disableSelection);
                        $('#manualAssignHint').toggle(disableSelection);
                    }

                    function applyManualState() {
                        var seasonId = $('#manual_season_id').val();
                        var isAllSeasons = seasonId === '-1';

                        $('#manual_assign_all_seasons').val(isAllSeasons ? '1' : '0');

                        if (isAllSeasons) {
                            resetManualEpisodes('All episodes from every season of this drama will be assigned on submit.', true);
                            return true;
                        }

                        $('#manual_video_id').prop('disabled', false);
                        $('#manual_video_id').prop('required', true);
                        $('#selectAllManualEpisodesWrapper').show();
                        $('#manualAssignHint').hide();
                        return false;
                    }

                    $('#manual_darama_id').change(function() {
                        var dramaId = $(this).val();

                        if (dramaId) {
                            $.ajax({
                                url: 'getseasoningroup.php',
                                type: 'POST',
                                data: {
                                    darama_id: dramaId
                                },
                                success: function(response) {
                                    $('#manual_season_id').html(response);
                                    resetManualEpisodes('Please select a season first', false);
                                    applyManualState();
                                },
                                error: function() {
                                    alert('Failed to fetch seasons. Please try again.');
                                }
                            });
                        } else {
                            $('#manual_season_id').html('<option value="">Please select a drama first</option>');
                            resetManualEpisodes('Please select a season first', false);
                            applyManualState();
                        }
                    });

                    $('#manual_season_id').change(function() {
                        var seasonId = $(this).val();

                        if (applyManualState()) {
                            return;
                        }

                        if (seasonId) {
                            $.ajax({
                                url: 'getepisodesingroup.php',
                                type: 'POST',
                                data: {
                                    season_id: seasonId
                                },
                                success: function(response) {
                                    $('#manual_video_id').html(response);
                                    $('#manual_video_id').prop('disabled', false);
                                    $('#manual_video_id').prop('required', true);
                                },
                                error: function() {
                                    alert('Failed to fetch episodes. Please try again.');
                                }
                            });
                        } else {
                            resetManualEpisodes('Please select a season first', false);
                        }
                    });

                    $('#selectAllManualEpisodes').change(function() {
                        var isChecked = $(this).prop("checked");

                        $("#manual_video_id option").each(function() {
                            $(this).prop("selected", isChecked);
                        });

                        $("#manual_video_id").trigger("change");
                    });

                    $('#bulk_assign_all_toggle').change(function() {
                        var assignAll = $(this).is(':checked');
                        $('#bulk_assign_all_dramas').val(assignAll ? '1' : '0');
                        $('#bulk_drama_ids').prop('disabled', assignAll).prop('required', !assignAll);

                        if (assignAll) {
                            $('#bulk_drama_ids').val(null).trigger('change');
                            $('#bulkAssignAllHelp').show();
                            $('#bulkAssignSelectionHelp').hide();
                        } else {
                            $('#bulkAssignAllHelp').hide();
                            $('#bulkAssignSelectionHelp').show();
                        }
                    });

                    applyManualState();
                });
            </script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const startInput = document.getElementById('start_date');
                    const endInput = document.getElementById('end_date');

                    const today = new Date();
                    const yyyyMmDd = today.toISOString().split('T')[0];
                    startInput.value = yyyyMmDd;

                    const nextMonth = new Date(today);
                    nextMonth.setMonth(nextMonth.getMonth() + 1);

                    if (nextMonth.getDate() !== today.getDate()) {
                        nextMonth.setDate(0);
                    }

                    const endDateFormatted = nextMonth.toISOString().split('T')[0];
                    endInput.value = endDateFormatted;
                });
            </script>
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
