<?php
session_start();
include "config.php";
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page or any other page you prefer
    header("Location: login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_log'])) {
    $userId = intval($_POST['reset_log_id']);
    // Update query to reset logged_number
    $sql = "UPDATE users SET logged_number = 0 WHERE id = $userId";
    if ($conn->query($sql) === TRUE) {
        $message = "Log reset successful.";
    } else {
        $message = "Error resetting log: " . $conn->error;
    }
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
    <link rel="stylesheet" type="text/text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="src/plugins/jvectormap/jquery-jvectormap-2.0.3.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <!----css3---->
    <link rel="stylesheet" href="vendors/styles/css/custom.css">
    <!--google material icon-->
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="vendors/styles/mycss/show-group.css">
    <!-- Font Awesome CDN link -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
    <!-- Custom CSS for Search Bar Styling -->
    <style>
        .search-container {
            display: flex;
            align-items: center;
            background-color: #f0f0f0;
            /* Light gray background similar to the image */
            border-radius: 5px;
            /* Slightly rounded corners */
            padding: 5px;
            /* Some padding around the elements */
            margin-top: 30px;
            width: 100%;
            /* Make it responsive within its container */
        }
        .search-input-wrapper {
            flex-grow: 1;
            position: relative;
            /* To position the icon */
            display: flex;
            /* For aligning icon and input */
            align-items: center;
            background-color: white;
            border-radius: 5px 0 0 5px;
        }
        .search-icon {
            padding: 10px;
            color: #777;
            /* Gray color for the icon */
        }
        .search-input {
            border: none;
            padding: 10px;
            padding-left: 35px;
            /* Add left padding to accommodate the icon */
            font-size: 16px;
            border-radius: 5px 0 0 5px;
            /* Rounded left corners */
            outline: none;
            /* Remove default focus outline */
            flex-grow: 1;
            /* Input takes remaining space */
            background-color: transparent;
            /* Make input background transparent */
        }
        .search-button {
            background-color: #e0e0e0;
            /* Light gray for the button */
            color: #333;
            /* Dark text color */
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 0 5px 5px 0;
            /* Rounded right corners */
            cursor: pointer;
            outline: none;
            /* Remove default focus outline */
        }
        /* Responsive adjustments */
        @media (max-width: 600px) {
            .search-container {
                max-width: 100%;
                /* Adjust for smaller screens */
                margin: 10px auto;
                /* Center on smaller screens */
            }
            .search-input,
            .search-button,
            .search-icon {
                padding: 8px;
                font-size: 13px;
            }
            .search-input {
                padding-left: 30px;
                /* Adjust icon padding on smaller screens */
            }
            .search-icon {
                padding-left: 8px;
                padding-right: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    <div class="mobile-menu-overlay"></div>
    <div class="main-container">
        <div class="xs-pd-20-10 pd-ltr-20">
            <div class="page-header">
                <div class="bg-white p-4 rounded-3 border mb-4">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-md-8">
                            <h4 class="main-heading mb-1">
                                Manage <span class="highlight-text">Users</span>
                            </h4>
                            <p class="sub-text mb-2">View, create, or manage your users efficiently.</p>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb bg-transparent p-0 m-0">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Users</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="user.php" class="btn btn-outline-green">
                                <i class="fas fa-user-plus me-2 small"></i>
                                Add User
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-header">
                <div class="row">
                    <div class="col-md-12 col-sm-12" style="display: flex; justify-content: space-between;">
                        <div class="title">
                            <h3 class="highlighted-heading text-secondary">All Users</h3>
                        </div>
                    </div>
                <!-- Search Form -->
                <div class="search-container">
                    <form method="get" action="" class="d-flex w-100">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="search-input" placeholder="Search by ID, username, or email..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        <button class="search-btn search-button">Search</button>
                    </form>
                </div>
                <!-- Desktop View (Table) -->
                <div class="d-none d-lg-block mt-4">
                    <div class="table-responsive">
                        <table class="table table-borderless table-striped">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>User ID</th>
                                    <th>User Name</th>
                                    <th>User Email</th>
                                    <th>Subscription</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                include "config.php";
                                $search = isset($_GET['search']) ? $_GET['search'] : '';
                                if (!empty($search)) {
                                    // Modified search query to include ID
                                    $sql = "SELECT * FROM users WHERE id LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%'";
                                } else {
                                    $sql = "SELECT * FROM users";
                                }
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php 
                                                $end_date = $row['end_date'] ?? null;
                                                $today = date('Y-m-d');
                                                if ($end_date) {
                                                    if ($end_date <= $today) {
                                                        echo '<span class="text-danger">Subscription ended</span>';
                                                    } else {
                                                        $days_left = (strtotime($end_date) - strtotime($today)) / (60 * 60 * 24);
                                                        echo '<span class="text-success">Ends in ' . intval($days_left) . ' day(s)</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">No end date set</span>';
                                                }
                                                ?></td>
                                            <td>
                                                <a href="view_videos.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm action-btn">
                                                    <i class="fas fa-eye me-1 mx-1"></i>View
                                                </a>
                                                <button class="btn btn-warning btn-sm action-btn snd-msg" id='<?php echo $row['id']; ?>'>
                                                    <i class="fas fa-paper-plane me-1 mx-1"></i>Msg
                                                </button>
                                                <a href="delete_user.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm action-btn">
                                                    <i class="fas fa-trash-alt me-1 mx-1"></i>Del
                                                </a>
                                                <a href="edit-user.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm action-btn">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                                <form method="POST">
                                                    <input type="hidden" name="reset_log_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="reset_log" class="btn btn-success action-btn">
                                                        <i class="fas fa-minus-square me-1 mx-1"></i>Log Reset
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='5'>No users found.</td></tr>";
                                }
                                $conn->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Mobile View (Card Layout) -->
                <div class="d-block d-lg-none mt-4">
                    <?php
                    if ($result->num_rows > 0) {
                        mysqli_data_seek($result, 0); // Reset result pointer for mobile view
                        while ($row = $result->fetch_assoc()) {
                    ?>
                            <div class="mobile-user-card mb-4 p-3 shadow-sm rounded bg-white border border-dark-subtle">
                                <div class="mb-2">
                                    <h6 class="mb-1 text-dark fw-semibold" style="font-size: 1rem;">ID: <?php echo $row['id']; ?></h6>
                                    <h6 class="mb-1 text-dark fw-semibold" style="font-size: 1rem;"><?php echo htmlspecialchars($row['username']); ?></h6>
                                    <p class="mb-0 text-secondary" style="font-size: 0.875rem;"><?php echo htmlspecialchars($row['email']); ?></p>
                                    <p class="mb-0" style="font-size: 0.875rem;"><?php 
                                                                                    $end_date = $row['end_date'] ?? null;
                                                                                    $today = date('Y-m-d');
                                                                                    if ($end_date) {
                                                                                        if ($end_date <= $today) {
                                                                                            echo '<span class="text-danger">Subscription ended</span>';
                                                                                        } else {
                                                                                            $days_left = (strtotime($end_date) - strtotime($today)) / (60 * 60 * 24);
                                                                                            echo '<span class="text-success">Ends in ' . intval($days_left) . ' day(s)</span>';
                                                                                        }
                                                                                    } else {
                                                                                        echo '<span class="text-muted">No end date set</span>';
                                                                                    }
                                                                                    ?></p>
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="view_videos.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm w-100 rounded-2">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <button class="btn btn-warning btn-sm w-100 rounded-2 snd-msg mt-2" id='<?php echo $row['id']; ?>'>
                                        <i class="fas fa-paper-plane me-1"></i> Message
                                    </button>
                                    <a href="delete_user.php?id=<?php echo $row['id']; ?>" class="btn btn-danger mt-4 btn-sm w-100 rounded-2">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </a>
                                    <a href="edit-user.php?id=<?php echo $row['id']; ?>" class="btn mt-2 btn-success btn-sm w-100 rounded-2">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo '<div class="text-center text-muted">No users found.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Send Message Modal -->
    <div class="modal fade" id="sendMessageModal" tabindex="-1" role="dialog" aria-labelledby="sendMessageLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-0 rounded-3 shadow-lg">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title text-white" id="sendMessageLabel">Send Message</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <textarea id="messageText" class="form-control" placeholder="Type your message here..." rows="4"></textarea>
                    <!-- Alert box (hidden initially) -->
                    <div id="messageAlert" class="alert alert-dark mt-3 d-none" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="sendMessageBtn" class="btn btn-primary">Send Message</button>
                </div>
            </div>
        </div>
    </div>
    <!-- js -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    <script>
        $(document).ready(function() {
            let selectedUserId;
            // Open modal on .snd-msg click
            $(document).on('click', '.snd-msg', function(e) {
                e.preventDefault();
                selectedUserId = $(this).attr('id');
                console.log("User ID:", selectedUserId);
                $('#messageText').val('');
                $('#messageAlert').addClass('d-none').text('');
                $('#sendMessageModal').modal('show');
            });
            // Handle Send Message
            $('#sendMessageBtn').on('click', function() {
                var new_message = $('#messageText').val().trim();
                if (new_message === '') {
                    alert("Message cannot be empty.");
                    return;
                }
                $(this).prop('disabled', true).text("Sending...");
                $.ajax({
                    url: 'snd_message.php',
                    method: 'POST',
                    data: {
                        user_id: selectedUserId,
                        message: new_message
                    },
                    success: function(response) {
                        $('#messageAlert')
                            .removeClass('d-none')
                            .text("Message sent successfully!")
                            .fadeIn();
                        // Optionally delay close
                        setTimeout(function() {
                            $('#sendMessageModal').modal('hide');
                            $('#sendMessageBtn').prop('disabled', false).text("Send Message");
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error:", error);
                        $('#messageAlert')
                            .removeClass('d-none alert-dark')
                            .addClass('alert-danger')
                            .text("Error sending message. Please try again.");
                        $('#sendMessageBtn').prop('disabled', false).text("Send Message");
                    }
                });
            });
        });
    </script>
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
</body>
</html>