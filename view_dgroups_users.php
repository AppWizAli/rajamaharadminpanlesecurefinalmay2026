<?php
session_start();
include "config.php"; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch groups
$groups_query = "SELECT id, group_name FROM `groups`";
$groups_result = $conn->query($groups_query);
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

// Initialize search variables
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

// Fetch users in the group with their comments, sorted by end_date
if (isset($_GET['group_id'])) {
    $group_id = intval($_GET['group_id']); // Sanitize the group_id
    
    // Query to fetch users for the given group_id, sorted by end_date
    $query = "SELECT u.id, u.username, u.email, gm.comment, gm.start_date, gm.end_date, gm.updated_at FROM users u
              JOIN group_members gm ON u.id = gm.user_id
              WHERE gm.group_id = ? ORDER BY CASE WHEN gm.end_date IS NULL THEN 1 ELSE 0 END, gm.end_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    echo "Group ID is missing.";
    exit;
}

// Fetch users who are not already in the selected group (newest to oldest)
$users_query = "SELECT id, username, email FROM users
                WHERE id NOT IN (
                    SELECT user_id FROM group_members WHERE group_id = ?
                )
                ORDER BY id DESC";
$users_stmt = $conn->prepare($users_query);
$users_stmt->bind_param("i", $group_id);
$users_stmt->execute();
$users_result = $users_stmt->get_result();

// Search functionality for users in the group, sorted by end_date
$search_query = "SELECT u.id, u.username, u.email, gm.comment, gm.start_date, gm.end_date, gm.updated_at
                 FROM users u
                 JOIN group_members gm ON u.id = gm.user_id
                 WHERE gm.group_id = ? AND (u.id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR gm.comment LIKE ?)
                 ORDER BY CASE WHEN gm.end_date IS NULL THEN 1 ELSE 0 END, gm.end_date ASC";
$search_param = "%" . $search . "%";
$search_stmt = $conn->prepare($search_query);
$search_stmt->bind_param("issss", $group_id, $search_param, $search_param, $search_param, $search_param);
$search_stmt->execute();
$search_result = $search_stmt->get_result();

$no_search_results = ($search_result->num_rows === 0 && $search !== '') ? true : false;
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
    <link rel="stylesheet" type="text/css" href="vendors/styles/mycss/Darams.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/css/custom.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    
    <style>
<?php if(isset($_GET['embedded']) && $_GET['embedded'] == '1'): ?>
/* Hide sidebar and adjust content when embedded */
#sidebar,
.left-side-bar,
.header,
.page-header,
.mobile-menu-overlay,
nav[aria-label="breadcrumb"],
.xp-menubar {
    display: none !important;
}

.main-container {
    margin-left: 0 !important;
    padding-left: 0 !important;
}

.xs-pd-20-10,
.pd-ltr-20 {
    padding-left: 20px !important;
    padding-right: 20px !important;
}

body {
    background: transparent !important;
}

.page-header {
    display: none !important;
}
<?php endif; ?>

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
            border-radius: 5px;
            outline: none;
            width: 100%;
            background-color: transparent;
        }
        .search-input-wrapper {
            position: relative;
            width: 100%;
        }
        .search-input-wrapper .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            z-index: 1;
        }
        
        /* Fix for group dropdown text color */
        #group_id, #group_id option {
            color: #000 !important;
            background-color: #fff;
        }
        #group_id:focus, #group_id option:focus {
            color: #000 !important;
        }
        
        /* Bulk Action Styles */
        .bulk-actions-bar {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }
        .bulk-actions-bar.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .selected-count {
            font-weight: 600;
            color: #495057;
        }
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        .user-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        #selectAll {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 600px) {
            .search-container {
                max-width: 95%;
                margin: 10px auto;
            }
            .search-input,
            .search-icon {
                font-size: 14px;
            }
            .search-input {
                padding-left: 30px;
            }
            .search-icon {
                left: 8px;
            }
            .bulk-actions-bar.show {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    <div class="main-container">
        <div class="xs-pd-20-10 pd-ltr-20">
            <div class="page-header">
                <div class="dashboard-header mb-4 p-3 rounded shadow-sm bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div class="page-title">
                            <h4 class="mb-0">Manage Group Users</h4>
                            <small>Manage all registered group members</small>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb bg-transparent mb-0 p-0">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Alert message for search results -->
            <?php if ($no_search_results): ?>
                <div class="alert alert-warning" role="alert">
                    No users found with the search criteria.
                </div>
            <?php endif; ?>

            <!-- Add User to Group -->
            <div class="card mb-3">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-white">Add User to Group</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="add_user_to_group.php">
                        <div class="form-group">
                            <label for="group_id">Select Group:</label>
                            <select id="group_id" name="group_id" class="form-control" required onchange="loadGroupUsers(this.value)">
                                <?php while ($group = $groups_result->fetch_assoc()) { ?>
                                    <option value="<?php echo $group['id']; ?>" <?php echo ($group_id == $group['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($group['group_name']); ?>
                                    </option>
                                <?php } ?>
                                <?php mysqli_data_seek($groups_result, 0); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="user_id">Select User:</label>
                            <select id="user_id" name="user_id" class="form-control select2" required>
                                <option value="">Select a User</option>
                                <?php while ($user = $users_result->fetch_assoc()) { ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo $user['id'] . ' - ' . htmlspecialchars($user['username']) . ' - ' . htmlspecialchars($user['email']); ?>
                                    </option>
                                <?php } ?>
                                <?php mysqli_data_seek($users_result, 0); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="comment">Comment (optional):</label>
                            <textarea id="comment" name="comment" class="form-control"></textarea>
                        </div>
                        <div class="from-group d-flex">
                            <div class="form-group mr-2">
                                <label for="start_date">Subscription Start Date:</label>
                                <input type="date" id="start_date" name="start_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="end_date">Subscription End Date:</label>
                                <input type="date" id="end_date" name="end_date" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row d-flex justify-content-center">
                            <button type="submit" name="add_user_to_group" class="btn btn-sm" style="background-color: #007bff; color: white; padding: 6px 12px; font-size: 14px;">
                                <i class="fas fa-user-plus"></i> Add User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users in Group -->
            <div class="pd-20 card-box mb-30">
                <div class="card mb-3">
                    <!-- Search Form -->
                    <div class="search-container">
                        <div class="search-input-wrapper w-100">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="search-input" placeholder="Search users by ID, name, email, or comment..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>

                    <!-- Bulk Actions Bar -->
                    <div class="bulk-actions-bar" id="bulkActionsBar">
                        <div>
                            <span class="selected-count" id="selectedCount">0 users selected</span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger" id="bulkDeleteBtn">
                                <i class="fas fa-trash-alt"></i> Delete Selected Users
                            </button>
                        </div>
                    </div>

                    <div class="card-header bg-dark mt-3">
                        <h5 class="mb-0 text-white">Users in Group</h5>
                    </div>
                    <div class="card-body p-0 mt-3">
                        <!-- Desktop View (Table) -->
                        <div class="d-none d-lg-block">
                            <div class="table-responsive">
                                <table id="group-users-table" class="table table-hover mb-0">
                                    <thead class="table-head-custom">
                                        <tr>
                                            <th class="checkbox-cell">
                                                <input type="checkbox" id="selectAll" title="Select All">
                                            </th>
                                            <th>User ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Comment</th>
                                            <th>Subscription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $data_to_display = ($search !== '') ? $search_result : $result;
                                        if ($data_to_display->num_rows > 0) {
                                            while ($row = $data_to_display->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td class="checkbox-cell">';
                                                echo '<input type="checkbox" class="user-checkbox" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                                echo '</td>';
                                                echo '<td>' . htmlspecialchars($row["id"]) . '</td>';
                                                echo '<td>' . htmlspecialchars($row["username"]) . '</td>';
                                                echo '<td>' . htmlspecialchars($row["email"]) . '</td>';
                                                
                                                $formatted_date = '';
                                                if (!empty($row['updated_at'])) {
                                                    $date = new DateTime($row['updated_at']);
                                                    $formatted_date = htmlspecialchars($date->format('d M Y - h:i A'));
                                                }
                                                $updatedAt = isset($row["updated_at"]) ? htmlspecialchars($formatted_date) : 'N/A';
                                                echo '<td>' . htmlspecialchars($row["comment"]) . '<br><small>' . $updatedAt . '</small></td>';
                                                
                                                echo '<td>';
                                                $start_date = $row['start_date'] ?? null;
                                                $end_date = $row['end_date'] ?? null;
                                                $today = date('Y-m-d');
                                                
                                                if ($start_date && $end_date) {
                                                    $start_ts = strtotime($start_date);
                                                    $end_ts = strtotime($end_date);
                                                    $today_ts = strtotime($today);
                                                    $total_days = ($end_ts - $start_ts) / (60 * 60 * 24);
                                                    
                                                    if ($today_ts < $start_ts) {
                                                        $days_until_start = ($start_ts - $today_ts) / (60 * 60 * 24);
                                                        echo '<span class="text-warning">Subscription will start in ' . intval($days_until_start) . ' day(s)</span><br>';
                                                        echo '<small class="text-muted">Total duration: ' . intval($total_days) . ' day(s)</small>';
                                                    } elseif ($today_ts > $end_ts) {
                                                        echo '<span class="text-danger">Subscription ended</span><br>';
                                                        echo '<small class="text-muted">Total duration was ' . intval($total_days) . ' day(s)</small>';
                                                    } else {
                                                        $days_left = ($end_ts - $today_ts) / (60 * 60 * 24);
                                                        echo '<span class="text-success">Ends in ' . intval($days_left) . ' day(s)</span><br>';
                                                        echo '<small class="text-muted">Total duration: ' . intval($total_days) . ' day(s)</small>';
                                                    }
                                                } elseif (!$start_date) {
                                                    echo '<span class="text-muted">No start date set</span>';
                                                } elseif (!$end_date) {
                                                    echo '<span class="text-muted">No end date set</span>';
                                                }
                                                echo '</td>';
                                                
                                                echo '<td>';
                                                echo '<button class="btn btn-sm btn-primary edit-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                                echo '<i class="fas fa-edit"></i> Edit</button> ';
                                                echo '<button class="btn btn-sm btn-primary edit-sub-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                                echo '<i class="fas fa-edit"></i> Edit Subscription </button> ';
                                                echo '<button class="btn btn-sm btn-danger remove-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                                echo '<i class="fas fa-trash-alt mx-1"></i>Del</button> ';
                                                echo '<button class="btn btn-secondary btn-sm snd-msg" id="' . $row["id"] . '">';
                                                echo '<i class="fas fa-paper-plane"></i> Send</button>';
                                                echo '<button class="btn btn-sm btn-warning increase-date-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                                echo '<i class="fas fa-calendar-plus"></i> Increase Date</button> ';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="7" class="text-center">No users found.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobile View (Cards) -->
                        <div class="d-block d-lg-none">
                            <?php
                            if ($data_to_display->num_rows > 0) {
                                mysqli_data_seek($data_to_display, 0);
                                while ($row = $data_to_display->fetch_assoc()) {
                                    echo '<div class="mobile-user-card mb-3 shadow-sm p-3 rounded">';
                                    echo '<div class="d-flex align-items-center mb-2">';
                                    echo '<input type="checkbox" class="user-checkbox me-2" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                    echo '<div class="flex-grow-1">';
                                    echo '<div class="user-header d-flex justify-content-between align-items-center">';
                                    echo '<h5 class="user-name mb-0">' . htmlspecialchars($row['username']) . '</h5>';
                                    echo '<small class="text-muted">' . htmlspecialchars($row['id']) . '</small>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '<div class="user-email text-muted mb-2">' . htmlspecialchars($row['email']) . '</div>';
                                    echo '<div class="user-comment mb-2">';
                                    echo '<strong>Comment:</strong> ' . htmlspecialchars($row['comment']);
                                    echo '</div>';
                                    echo '<div class="user-comment mb-2">';
                                    
                                    $start_date = $row['start_date'] ?? null;
                                    $end_date = $row['end_date'] ?? null;
                                    $today = date('Y-m-d');
                                    
                                    if ($start_date && $end_date) {
                                        $start_ts = strtotime($start_date);
                                        $end_ts = strtotime($end_date);
                                        $today_ts = strtotime($today);
                                        $total_days = ($end_ts - $start_ts) / (60 * 60 * 24);
                                        
                                        if ($today_ts < $start_ts) {
                                            $days_until_start = ($start_ts - $today_ts) / (60 * 60 * 24);
                                            echo '<span class="text-warning">Subscription will start in ' . intval($days_until_start) . ' day(s)</span><br>';
                                            echo '<small class="text-muted">Total duration: ' . intval($total_days) . ' day(s)</small>';
                                        } elseif ($today_ts > $end_ts) {
                                            echo '<span class="text-danger">Subscription ended</span><br>';
                                            echo '<small class="text-muted">Total duration was ' . intval($total_days) . ' day(s)</small>';
                                        } else {
                                            $days_left = ($end_ts - $today_ts) / (60 * 60 * 24);
                                            echo '<span class="text-success">Ends in ' . intval($days_left) . ' day(s)</span><br>';
                                            echo '<small class="text-muted">Total duration: ' . intval($total_days) . ' day(s)</small>';
                                        }
                                    } elseif (!$start_date) {
                                        echo '<span class="text-muted">No start date set</span>';
                                    } elseif (!$end_date) {
                                        echo '<span class="text-muted">No end date set</span>';
                                    }
                                    
                                    $formatted_date = '';
                                    if (!empty($row['updated_at'])) {
                                        $date = new DateTime($row['updated_at']);
                                        $formatted_date = htmlspecialchars($date->format('d M Y - h:i A'));
                                    }
                                    echo '<small class="text-muted d-block">Updated at: ' . (isset($row['updated_at']) ? $formatted_date : 'N/A') . '</small>';
                                    echo '</div>';
                                    echo '<div class="user-actions d-flex justify-content-between flex-wrap gap-2">';
                                    echo '<button class="btn btn-sm btn-primary edit-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                    echo '<i class="fas fa-edit"></i> Edit</button>';
                                    echo '<button class="btn btn-sm btn-primary edit-sub-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                    echo '<i class="fas fa-edit"></i> Edit Sub</button>';
                                    echo '<button class="btn btn-sm btn-danger remove-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                    echo '<i class="fas fa-trash-alt mx-1"></i> Del</button>';
                                    echo '<button class="btn btn-secondary btn-sm snd-msg" id="' . $row["id"] . '">';
                                    echo '<i class="fas fa-paper-plane"></i> Send</button>';
                                    echo '<button class="btn btn-sm btn-warning increase-date-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                                    echo '<i class="fas fa-calendar-plus"></i> +Date</button>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="text-center text-muted">No users found.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Message Modal -->
    <div class="modal fade" id="sendMsgModal" tabindex="-1" aria-labelledby="sendMsgModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="sendMsgModalLabel"><i class="fas fa-paper-plane me-2"></i>Send Message</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="sendMsgForm">
                        <input type="hidden" id="msgUserId">
                        <div class="mb-3">
                            <label for="messageText" class="form-label">Your Message</label>
                            <textarea class="form-control" id="messageText" rows="4" placeholder="Type your message here..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="sendMsgBtn" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i>Send</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Comment Modal -->
    <div id="editCommentModal" class="modal fade" tabindex="-1" aria-labelledby="editCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCommentModalLabel">Edit Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="commentText">Comment:</label>
                        <textarea id="commentText" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveCommentBtn" class="btn btn-primary">Save Comment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Subscription Modal -->
    <div id="editSubscriptionModal" class="modal fade" tabindex="-1" aria-labelledby="editSubscriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubscriptionModalLabel">Edit Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="subscriptionForm">
                        <input type="hidden" id="editUserId">
                        <input type="hidden" id="editGroupId">
                        <div class="form-group">
                            <label for="editStartDate">Start Date</label>
                            <input type="date" id="editStartDate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="editEndDate">End Date</label>
                            <input type="date" id="editEndDate" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveSubscriptionBtn" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkDeleteModalLabel">Confirm Bulk Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="bulkDeleteCount">0</strong> selected user(s) from this group?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmBulkDeleteBtn" class="btn btn-danger">Delete Users</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="confirmActionModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to perform this action?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmActionBtn" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="vendors/scripts/popper.min.js"></script>
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>

<script>
// Check if page is embedded
if(window.location.search.includes('embedded=1') || window !== window.parent) {
    // Hide sidebar and header elements
    const elementsToHide = [
        '#sidebar',
        '.left-side-bar',
        '.header',
        '.mobile-menu-overlay',
        'nav[aria-label="breadcrumb"]',
        '.xp-menubar',
        '.page-header'
    ];
    
    elementsToHide.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            if(el) el.style.display = 'none';
        });
    });
    
    // Adjust main container
    const mainContainer = document.querySelector('.main-container');
    if(mainContainer) {
        mainContainer.style.marginLeft = '0';
        mainContainer.style.paddingLeft = '0';
    }
    
    // Adjust body
    document.body.style.background = 'transparent';
}
</script>

    <script>
        $(document).ready(function() {
            $(".xp-menubar").on('click', function() {
                $("#sidebar").toggleClass('active');
                $("#content").toggleClass('active');
            });

            $('.xp-menubar,.body-overlay').on('click', function() {
                $("#sidebar,.body-overlay").toggleClass('show-nav');
            });

            $('#user_id').select2({
                placeholder: 'Select a user',
                allowClear: true
            });

            // Bulk Selection Functionality
            let selectedUsers = [];

            // Update selected count and show/hide bulk actions bar
            function updateBulkActionsBar() {
                const count = selectedUsers.length;
                $('#selectedCount').text(count + ' user' + (count !== 1 ? 's' : '') + ' selected');
                
                if (count > 0) {
                    $('#bulkActionsBar').addClass('show');
                } else {
                    $('#bulkActionsBar').removeClass('show');
                }
            }

            // Select All checkbox
            $('#selectAll').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.user-checkbox').prop('checked', isChecked);
                
                selectedUsers = [];
                if (isChecked) {
                    $('.user-checkbox').each(function() {
                        selectedUsers.push({
                            user_id: $(this).data('user-id'),
                            group_id: $(this).data('group-id')
                        });
                    });
                }
                updateBulkActionsBar();
            });

            // Individual checkbox
            $(document).on('change', '.user-checkbox', function() {
                const userId = $(this).data('user-id');
                const groupId = $(this).data('group-id');
                
                if ($(this).is(':checked')) {
                    selectedUsers.push({ user_id: userId, group_id: groupId });
                } else {
                    selectedUsers = selectedUsers.filter(u => u.user_id !== userId);
                    $('#selectAll').prop('checked', false);
                }
                
                // Check if all checkboxes are selected
                const totalCheckboxes = $('.user-checkbox').length;
                const checkedCheckboxes = $('.user-checkbox:checked').length;
                $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
                
                updateBulkActionsBar();
            });

            // Bulk Delete Button
            $('#bulkDeleteBtn').on('click', function() {
                if (selectedUsers.length === 0) {
                    alert('Please select at least one user to delete.');
                    return;
                }
                
                $('#bulkDeleteCount').text(selectedUsers.length);
                $('#bulkDeleteModal').modal('show');
            });

            // Confirm Bulk Delete
            $('#confirmBulkDeleteBtn').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
                
                $.ajax({
                    url: 'bulk_delete_users.php',
                    method: 'POST',
                    data: JSON.stringify({ users: selectedUsers }),
                    contentType: 'application/json',
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            alert(res.message);
                            $('#bulkDeleteModal').modal('hide');
                            location.reload();
                        } else {
                            alert('Error: ' + res.message);
                            button.prop('disabled', false).html('Delete Users');
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting users.');
                        button.prop('disabled', false).html('Delete Users');
                    }
                });
            });
        });
    </script>

    <script>
        $(document).on('click', '.edit-sub-btn', function() {
            const userId = $(this).data('user-id');
            const groupId = $(this).data('group-id');

            $('#editUserId').val(userId);
            $('#editGroupId').val(groupId);

            $.ajax({
                url: 'get_subscription_dates.php',
                type: 'POST',
                data: { user_id: userId, group_id: groupId },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        $('#editStartDate').val(res.start_date);
                        $('#editEndDate').val(res.end_date);
                    } else {
                        alert(res.message);
                        $('#editStartDate').val('');
                        $('#editEndDate').val('');
                    }
                },
                error: function() {
                    alert('Failed to fetch current subscription dates.');
                },
                complete: function() {
                    $('#editSubscriptionModal').modal('show');
                }
            });
        });

        $('#saveSubscriptionBtn').on('click', function() {
            const userId = $('#editUserId').val();
            const groupId = $('#editGroupId').val();
            const startDate = $('#editStartDate').val();
            const endDate = $('#editEndDate').val();

            $.ajax({
                url: 'update_subscription.php',
                type: 'POST',
                data: {
                    user_id: userId,
                    group_id: groupId,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        alert('Subscription updated successfully.');
                        $('#editSubscriptionModal').modal('hide');
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    alert('Something went wrong.');
                }
            });
        });
    </script>

    <script>
        function loadGroupUsers(groupId) {
            var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?group_id=' + groupId;
            window.history.pushState({ path: newUrl }, '', newUrl);
            
            $.ajax({
                url: 'fetch_dgroup_users.php',
                type: 'GET',
                data: { group_id: groupId },
                success: function(response) {
                    $('#group-users-table tbody').html(response);
                }
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            let selectedUserId;
            let selectedGroupId;

            $(document).on('click', '.edit-btn', function() {
                selectedUserId = $(this).data('user-id');
                selectedGroupId = $(this).data('group-id');
                
                $.ajax({
                    url: 'get_comment.php',
                    method: 'POST',
                    data: { user_id: selectedUserId, group_id: selectedGroupId },
                    success: function(current_comment) {
                        $('#commentText').val(current_comment);
                        $('#editCommentModal').modal('show');
                    }
                });
            });

            $('#saveCommentBtn').on('click', function() {
                var new_comment = $('#commentText').val().trim();
                if (new_comment === '') {
                    alert("Comment cannot be empty.");
                    return;
                }

                $.ajax({
                    url: 'update_comment.php',
                    method: 'POST',
                    data: {
                        user_id: selectedUserId,
                        group_id: selectedGroupId,
                        comment: new_comment
                    },
                    success: function(response) {
                        alert(response);
                        $('#editCommentModal').modal('hide');
                        location.reload();
                    },
                    error: function() {
                        alert("There was an error updating the comment.");
                    }
                });
            });

            $(document).on('click', '.remove-btn', function() {
                var user_id = $(this).data('user-id');
                var group_id = $(this).data('group-id');
                
                $('#confirmActionBtn').data('user-id', user_id);
                $('#confirmActionBtn').data('group-id', group_id);
                $('#confirmActionBtn').data('action', 'delete');
                $('#confirmActionModal').modal('show');
            });

            $(document).on('click', '.increase-date-btn', function() {
                const userId = $(this).data('user-id');
                const groupId = $(this).data('group-id');
                
                $('#confirmActionBtn').data('user-id', userId);
                $('#confirmActionBtn').data('group-id', groupId);
                $('#confirmActionBtn').data('action', 'increase-date');
                $('#confirmActionModal').modal('show');
            });

            $(document).on('click', '#confirmActionBtn', function() {
                const userId = $(this).data('user-id');
                const groupId = $(this).data('group-id');
                const action = $(this).data('action');

                if (action === 'delete') {
                    $.ajax({
                        url: 'delete_user_from_group.php',
                        method: 'POST',
                        data: { user_id: userId, group_id: groupId },
                        success: function(response) {
                            alert(response);
                            $('#confirmActionModal').modal('hide');
                            location.reload();
                        },
                        error: function() {
                            alert('An error occurred while removing the user.');
                            $('#confirmActionModal').modal('hide');
                        }
                    });
                } else if (action === 'increase-date') {
                    $.ajax({
                        url: 'increase_date.php',
                        method: 'POST',
                        contentType: 'application/x-www-form-urlencoded',
                        data: `user_id=${userId}&group_id=${groupId}`,
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                alert(data.message || 'End date increased by 31 days.');
                                $('#confirmActionModal').modal('hide');
                                location.reload();
                            } else {
                                alert('Error: ' + data.message);
                                $('#confirmActionModal').modal('hide');
                            }
                        },
                        error: function() {
                            alert('An error occurred.');
                            $('#confirmActionModal').modal('hide');
                        }
                    });
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            let searchTimeout;

            $('input[name="search"]').on('keyup', function() {
                var search = $(this).val().trim();
                var groupId = <?php echo $group_id; ?>;

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    performSearch(search, groupId);
                }, 300);
            });

            function performSearch(search, groupId) {
                $.ajax({
                    url: 'search-group-users.php',
                    type: 'GET',
                    data: { group_id: groupId, search: search },
                    success: function(response) {
                        $('#group-users-table tbody').html(response);
                        if ($('.d-block.d-lg-none').length > 0) {
                            updateMobileView(search, groupId);
                        }
                    }
                });
            }

            function updateMobileView(search, groupId) {
                $.ajax({
                    url: 'search-group-users-mobile.php',
                    type: 'GET',
                    data: { group_id: groupId, search: search },
                    success: function(response) {
                        $('.d-block.d-lg-none').html(response);
                    }
                });
            }

            $('input[name="search"]').on('input', function() {
                if ($(this).val() === '') {
                    location.reload();
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            let selectedUserId;

            $(document).on('click', '.snd-msg', function() {
                selectedUserId = $(this).attr('id');
                $('#msgUserId').val(selectedUserId);
                $('#messageText').val('');
                $('#sendMsgModal').modal('show');
            });

            $('#sendMsgBtn').on('click', function() {
                var message = $('#messageText').val().trim();
                if (message === '') {
                    alert("Message cannot be empty.");
                    return;
                }

                $.ajax({
                    url: 'snd_message.php',
                    method: 'POST',
                    data: { user_id: selectedUserId, message: message },
                    success: function(response) {
                        $('#sendMsgModal').modal('hide');
                        showNotification("Success", "Your message has been sent.");
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    },
                    error: function() {
                        $('#sendMsgModal').modal('hide');
                        showNotification("Error", "There was an error sending your message.");
                    }
                });
            });

            $('#sendMsgModal').on('hidden.bs.modal', function() {
                $('#messageText').val('');
                selectedUserId = null;
            });

            function showNotification(type, message) {
                var notificationClass = type === "Success" ? "alert-success" : "alert-danger";
                $('#notification').removeClass('d-none');
                $('#notification .alert').removeClass('alert-success alert-danger').addClass(notificationClass);
                $('#notification .alert strong').text(type + "!");
                $('#notification .alert').text(message);
                setTimeout(function() {
                    $('#notification').addClass('d-none');
                }, 5000);
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
</body>
</html>
<?php
$stmt->close();
$users_stmt->close();
$search_stmt->close();
$conn->close();
?>
