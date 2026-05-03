<?php
include "config.php";
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
// Initialize search variable
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
// Fetch all groups, including comments
if ($search) {
    $sql = "SELECT * FROM `groups` WHERE group_name LIKE ?";
    $search_param = "%" . $search . "%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_param);
} else {
    $sql = "SELECT * FROM `groups`";
    $stmt = $conn->prepare($sql);
}
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
    <link rel="stylesheet" href="vendors/styles/css/custom.css">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="vendors/styles/mycss/show-group.css">
    <!-- Font Awesome CDN (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
    <style>
        .search-container {
            display: flex;
            align-items: center;
            background-color: #f0f0f0;
            border-radius: 5px;
            padding: 5px;
            margin-top: 30px;
            width: 100%;
        }
        .search-input-wrapper {
            flex-grow: 1;
            position: relative;
            display: flex;
            align-items: center;
            background-color: white;
            border-radius: 5px 0 0 5px;
        }
        .search-icon {
            padding: 10px;
            color: #777;
        }
        .search-input {
            border: none;
            padding: 10px;
            padding-left: 35px;
            font-size: 16px;
            border-radius: 5px 0 0 5px;
            outline: none;
            flex-grow: 1;
            background-color: transparent;
        }
        .search-button {
            background-color: #e0e0e0;
            color: #333;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            outline: none;
        }
        @media (max-width: 600px) {
            .search-container {
                max-width: 95%;
                margin: 10px auto;
            }
            .search-input,
            .search-button,
            .search-icon {
                padding: 8px;
                font-size: 14px;
            }
            .search-input {
                padding-left: 30px;
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
<div class="main-container">
    <div class="pd-ltr-20 xs-pd-20-10">
        <div class="min-height-200px">
            <div class="page-header">
                <div class="bg-white p-4 rounded-3 border mb-4">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-md-8">
                            <h4 class="main-heading mb-1">Manage <span class="highlight-text">Groups</span></h4>
                            <p class="sub-text mb-2">View, create, or manage your user groups efficiently.</p>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb bg-transparent p-0 m-0">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">All Groups</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="create_groups.php" class="btn btn-outline-green">
                                <i class="fas fa-users me-2 small"></i>
                                Add Group
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Search Form -->
            <div class="search-container">
                <form method="post" action="" class="d-flex w-100">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Search groups by name" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button class="search-btn search-button">Search</button>
                </form>
            </div>
            <!-- Product Wrap -->
            <div class="product-wrap">
                <div class="product-list">
                    <ul class="row">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $group_id = $row['id'];
                                $count_query = "SELECT COUNT(*) AS user_count FROM group_members WHERE group_id = $group_id";
                                $count_result = $conn->query($count_query);
                                $user_count = 0;
                                if ($count_result && $count_result->num_rows > 0) {
                                    $count_row = $count_result->fetch_assoc();
                                    $user_count = $count_row['user_count'];
                                }
                                echo '<li class="col-lg-12 col-md-6 col-sm-12">';
                                echo '<div class="product-box">';
                                echo '<div class="product-caption">';
                                echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
                                echo '<h4 class="product-heading">' . htmlspecialchars($row["group_name"]) . '</h4>';
                                echo '<div class="action-icons">';
                                echo '<a href="edit_group.php?id=' . $row["id"] . '" class="action-icon edit-icon" title="Edit"><i class="fas fa-pencil-alt"></i></a>';
                                echo '<a href="#" class="action-icon delete-icon" title="Delete" data-group-id="' . $row["id"] . '"><i class="fas fa-trash-alt"></i></a>';
                                echo '<a href="#" class="action-icon message-icon snd-msg" title="Message" data-group-id="' . $row["id"] . '"><i class="fas fa-comment-dots"></i></a>';
                                echo '</div>';
                                echo '</div>';
                                echo '<p><strong>Comment:</strong> ' . htmlspecialchars($row["group_comment"]) . '</p>';
                                echo '<div class="btn-group-wrap">';
                                echo '<a href="view_group_videos.php?group_id=' . $row["id"] . '" class="btn btn-blue"><i class="fas fa-video me-1"></i>Videos</a>';
                                echo '<a href="view_dgroups_users.php?group_id=' . $row["id"] . '" class="btn btn-blue1"><i class="fas fa-users me-1"></i>Users
                                    <span class="badge bg-light text-dark rounded-circle ms-1">' . $user_count . '</span>
                                    </a>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                                echo '</li>';
                            }
                        } else {
                            echo "<li class='col-lg-12 col-md-6 col-sm-12'>No groups found.</li>";
                        }
                        $stmt->close();
                        $conn->close();
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg rounded-3">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title text-white" id="sendMessageModalLabel">Send Message to Group</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea id="messageText" class="form-control" placeholder="Enter your message..." rows="4"></textarea>
                <div id="groupMessageAlert" class="alert d-none mt-3" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="sendMessageBtn" class="btn btn-primary">Send Message</button>
            </div>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg rounded-3">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white" id="deleteConfirmModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this group? This action cannot be undone.</p>
                <div id="deleteMessageAlert" class="alert d-none mt-3" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>
<div class="mobile-menu-overlay"></div>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    let selectedGroupId;

    // Handle send message button click
    $(document).on('click', '.snd-msg', function (e) {
        e.preventDefault();
        selectedGroupId = $(this).data('group-id');
        console.log("Group ID:", selectedGroupId);
        $('#messageText').val('');
        $('#groupMessageAlert').addClass('d-none').text('');
        $('#sendMessageModal').modal('show');
    });

    // Handle send message inside modal
    $('#sendMessageBtn').on('click', function () {
        const new_message = $('#messageText').val().trim();
        if (new_message === '') {
            $('#groupMessageAlert')
                .removeClass('d-none alert-success')
                .addClass('alert-danger')
                .text("Message cannot be empty.");
            return;
        }
        $('#sendMessageBtn').prop('disabled', true).text("Sending...");
        $.ajax({
            url: 'send-group-message.php',
            method: 'POST',
            data: {
                group_id: selectedGroupId,
                message: new_message
            },
            success: function (response) {
                console.log("Send message response:", response);
                $('#groupMessageAlert')
                    .removeClass('d-none alert-danger')
                    .addClass('alert-success')
                    .html('<strong>Success!</strong> Message sent to the group.');
                setTimeout(() => {
                    $('#sendMessageModal').modal('hide');
                    $('#sendMessageBtn').prop('disabled', false).text("Send Message");
                    location.reload();
                }, 2000);
            },
            error: function (xhr, status, error) {
                console.error("Error sending message:", error);
                $('#groupMessageAlert')
                    .removeClass('d-none alert-success')
                    .addClass('alert-danger')
                    .text("There was an error sending the message.");
                $('#sendMessageBtn').prop('disabled', false).text("Send Message");
            }
        });
    });

    // Handle delete icon click
    $(document).on('click', '.delete-icon', function (e) {
        e.preventDefault();
        selectedGroupId = $(this).data('group-id');
        console.log("Delete Group ID:", selectedGroupId);
        $('#deleteMessageAlert').addClass('d-none').text('');
        $('#deleteConfirmModal').modal('show');
    });

    // Handle confirm delete button click
    $('#confirmDeleteBtn').on('click', function () {
        $('#confirmDeleteBtn').prop('disabled', true).text("Deleting...");
        $.ajax({
            url: 'delete_group.php',
            method: 'POST',
            data: {
                id: selectedGroupId
            },
            success: function (response) {
                console.log("Delete response:", response);
                $('#deleteMessageAlert')
                    .removeClass('d-none alert-danger')
                    .addClass('alert-success')
                    .html('<strong>Success!</strong> Group deleted successfully.');
                setTimeout(() => {
                    $('#deleteConfirmModal').modal('hide');
                    $('#confirmDeleteBtn').prop('disabled', false).text("Delete");
                    location.reload();
                }, 2000);
            },
            error: function (xhr, status, error) {
                console.error("Error deleting group:", error);
                $('#deleteMessageAlert')
                    .removeClass('d-none alert-success')
                    .addClass('alert-danger')
                    .text("There was an error deleting the group.");
                $('#confirmDeleteBtn').prop('disabled', false).text("Delete");
            }
        });
    });
});
</script>
</body>
</html>