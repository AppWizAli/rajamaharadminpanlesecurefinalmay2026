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

// Fetch users in the group with their comments
if (isset($_GET['group_id'])) {
    $group_id = intval($_GET['group_id']); // Sanitize the group_id

    // Query to fetch users for the given group_id
    $query = "SELECT u.id, u.username, u.email, gm.comment,gm.updated_at FROM users u
              JOIN group_members gm ON u.id = gm.user_id
              WHERE gm.group_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    echo "Group ID is missing.";
    exit;
}
// Fetch users who are not already in the selected group
$users_query = "SELECT id, username, email FROM users 
                WHERE id NOT IN (
                    SELECT user_id FROM group_members WHERE group_id = ?
                )";
$users_stmt = $conn->prepare($users_query);
$users_stmt->bind_param("i", $group_id);
$users_stmt->execute();
$users_result = $users_stmt->get_result();

// Search functionality for users in the group
$search_query = "SELECT u.id, u.username, u.email, gm.comment FROM users u
                 JOIN group_members gm ON u.id = gm.user_id
                 WHERE gm.group_id = ? AND (u.username LIKE ? OR u.email LIKE ?)";
$search_param = "%" . $search . "%";
$search_stmt = $conn->prepare($search_query);
$search_stmt->bind_param("iss", $group_id, $search_param, $search_param);
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
 <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
	    <!----css3---->
        <link rel="stylesheet" href="vendors/styles//css/custom.css">
			   <!--google material icon-->
			   <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
    <style>
            .search-container {
            display: flex;
            align-items: center;
            background-color: #f0f0f0; /* Light gray background similar to the image */
            border-radius: 5px; /* Slightly rounded corners */
            padding: 5px; /* Some padding around the elements */
       margin-top: 30px;
           
            width: 100%; /* Make it responsive within its container */
        }

        .search-input-wrapper {
            flex-grow: 1;
            position: relative; /* To position the icon */
            display: flex; /* For aligning icon and input */
            align-items: center;
            background-color: white;
            border-radius: 5px 0 0 5px;
        }

        .search-icon {
            padding: 10px;
            color: #777; /* Gray color for the icon */
        }

        .search-input {
            border: none;
            padding: 10px;
            padding-left: 35px; /* Add left padding to accommodate the icon */
            font-size: 16px;
            border-radius: 5px 0 0 5px; /* Rounded left corners */
            outline: none; /* Remove default focus outline */
            flex-grow: 1; /* Input takes remaining space */
            background-color: transparent; /* Make input background transparent */
        }

        .search-button {
            background-color: #e0e0e0; /* Light gray for the button */
            color: #333; /* Dark text color */
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 0 5px 5px 0; /* Rounded right corners */
            cursor: pointer;
            outline: none; /* Remove default focus outline */
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .search-container {
                max-width: 95%; /* Adjust for smaller screens */
                margin: 10px auto; /* Center on smaller screens */
            }

            .search-input,
            .search-button,
            .search-icon {
                padding: 8px;
                font-size: 14px;
            }

            .search-input {
                padding-left: 30px; /* Adjust icon padding on smaller screens */
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
       
        <!-- Users in Group -->
        <div class="pd-20 card-box mb-30">
            <div class="card mb-3">
            <!-- Search Form -->
<div class="search-container">
    <form method="post" action="" class="d-flex w-100">
        <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="search" class="search-input" placeholder="Search groups by name" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" class="search-btn search-button">Search</button>
    </form>
</div>

<div class="card-header bg-dark  mt-3">
    <h5 class="mb-0 text-white">Users in Group</h5>
</div>

                <div class="card-body p-0 mt-3">
                  <!-- Unified Layout: Table on Desktop, Cards on Mobile -->

<!-- Desktop View (Table) -->
<div class="d-none d-lg-block">
    <div class="table-responsive">
        <table id="group-users-table" class="table table-hover mb-0">
            <thead class="table-head-custom">
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Comment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $data_to_display = ($search !== '') ? $search_result : $result;

                if ($data_to_display->num_rows > 0) {
                    while ($row = $data_to_display->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row["id"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["username"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["email"]) . '</td>';

                        $updatedAt = isset($row["updated_at"]) ? htmlspecialchars($row["updated_at"]) : 'N/A';
                        echo '<td>' . htmlspecialchars($row["comment"]) . '<br><small>' . $updatedAt . '</small></td>';

                        echo '<td>';
                        echo '<button class="btn btn-sm btn-primary edit-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                        echo '<i class="fas fa-edit"></i> Edit</button> ';
                        
                        echo '<button class="btn btn-sm btn-danger remove-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
                        echo '<i class="fas fa-trash-alt mx-1"></i>Del</button> ';
                        
                        echo '<button class="btn btn-secondary btn-sm snd-msg" id="' . $row["id"] . '">';
                        echo '<i class="fas fa-paper-plane"></i> Send</button>';
                        
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center">No users found.</td></tr>';
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
        mysqli_data_seek($data_to_display, 0); // Reset result pointer
        while ($row = $data_to_display->fetch_assoc()) {
            echo '<div class="mobile-user-card mb-3 shadow-sm p-3 rounded">';
            echo '    <div class="user-header d-flex justify-content-between align-items-center mb-2">';
            echo '        <h5 class="user-name mb-0">' . htmlspecialchars($row['username']) . '</h5>';
            echo '        <small class="text-muted">' . htmlspecialchars($row['id']) . '</small>';
            echo '    </div>';
            echo '    <div class="user-email text-muted mb-2">' . htmlspecialchars($row['email']) . '</div>';
            echo '    <div class="user-comment mb-2">';
            echo '        <strong>Comment:</strong> ' . htmlspecialchars($row['comment']);
            echo '        <small class="text-muted d-block">Updated at: ' . (isset($row['updated_at']) ? htmlspecialchars($row['updated_at']) : 'N/A') . '</small>';
            echo '    </div>';
            echo '    <div class="user-actions d-flex justify-content-between">';
            echo '        <button class="btn btn-sm btn-primary edit-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
            echo '            <i class="fas fa-edit"></i> Edit</button>';
            echo '        <button class="btn btn-sm btn-danger remove-btn" data-user-id="' . $row["id"] . '" data-group-id="' . $group_id . '">';
            echo '            <i class="fas fa-trash-alt mx-1"></i> Del</button>';
            echo '        <button class="btn btn-secondary btn-sm snd-msg" id="' . $row["id"] . '">';
            echo '            <i class="fas fa-paper-plane"></i> Send</button>';
            echo '    </div>';
            echo '</div>';
        }
    } else {
        echo '<div class="text-center text-muted">No users found.</div>';
    }
    ?>
</div>

                </div>
                

            </div>

            <!-- Add User to Group -->
            <div class="card mb-3">
                <div class="card-header bg-dark ">
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
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="comment">Comment (optional):</label>
                            <textarea id="comment" name="comment" class="form-control"></textarea>
                        </div>
                        <div class="form-group row">
                          
                           
                                <button type="submit" name="add_user_to_group" class="btn btn-custom-red  ">
                                    <i class="fas fa-user-plus"></i> Add User 
                                </button>
                      
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Send Message Modal -->
<div class="modal fade" id="sendMsgModal" tabindex="-1" aria-labelledby="sendMsgModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-secondary text-white ">
          <h5 style="color: white;" class="modal-title " id="sendMsgModalLabel"><i class="fas fa-paper-plane me-2"></i>Send Message</h5>
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
<div id="editCommentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCommentModalLabel">Edit Comment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Textarea for editing the comment -->
                <div class="form-group">
                    <label for="commentText">Comment:</label>
                    <textarea id="commentText" class="form-control" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="saveCommentBtn" class="btn btn-primary">Save Comment</button>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
<script>
    $(document).ready(function() {
        // Initialize Select2 on the user dropdown
        $('#user_id').select2({
            placeholder: 'Select a user',
            allowClear: true
        });
    });
</script>
<script>
function loadGroupUsers(groupId) {
   
    var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?group_id=' + groupId;
    window.history.pushState({ path: newUrl }, '', newUrl);

    $.ajax({
        url: 'fetch_group_users.php',
        type: 'GET',
        data: { group_id: groupId },
        success: function(response) {
            $('#group-users-table tbody').html(response); 
        }
    });
}

</script>
<script>
    $(document).ready(function () {
    let selectedUserId;
    let selectedGroupId;

    // Handle edit button click
    $(document).on('click', '.edit-btn', function () {
        selectedUserId = $(this).data('user-id');
        selectedGroupId = $(this).data('group-id');
        console.log("Button clicked. User ID:", selectedUserId, "Group ID:", selectedGroupId);

        // Fetch the existing comment
        $.ajax({
            url: 'get_comment.php',
            method: 'POST',
            data: {
                user_id: selectedUserId,
                group_id: selectedGroupId
            },
            success: function (current_comment) {
                console.log("Fetched comment:", current_comment);

                // Set the current comment in the modal's textarea
                $('#commentText').val(current_comment);

                // Show the edit comment modal
                $('#editCommentModal').modal('show');
            }
        });
    });

    // Handle the save button click in the modal
    $('#saveCommentBtn').on('click', function () {
        var new_comment = $('#commentText').val().trim();

        if (new_comment === '') {
            alert("Comment cannot be empty.");
            return;
        }

        // Update the comment in the database
        $.ajax({
            url: 'update_comment.php',
            method: 'POST',
            data: {
                user_id: selectedUserId,
                group_id: selectedGroupId,
                comment: new_comment
            },
            success: function (response) {
                console.log("Update response:", response);
                alert(response);
                $('#editCommentModal').modal('hide'); // Hide the modal
                location.reload(); // Reload to reflect changes
            },
            error: function (xhr, status, error) {
                console.error("Error updating comment:", error);
                alert("There was an error updating the comment.");
            }
        });
    });

    // Handle remove user button click (same as previous)
    $(document).on('click', '.remove-btn', function () {
        var user_id = $(this).data('user-id');
        var group_id = $(this).data('group-id');
        if (confirm('Are you sure you want to remove this user from the group?')) {
            $.ajax({
                url: 'delete_user_from_group.php',
                method: 'POST',
                data: {
                    user_id: user_id,
                    group_id: group_id
                },
                success: function (response) {
                    alert(response);
                    location.reload();
                }
            });
        }
    });
});

</script>

<script>
    
    // AJAX for live search
    $('#search').on('keyup', function() {
        var search = $(this).val();
        $.ajax({
            url: 'search-group-users.php',
            type: 'GET',
            data: { group_id: <?php echo $group_id; ?>, search: search },
            success: function(response) {
                $('#group-users-table tbody').html(response);
            }
        });
    });
</script>

<script>
$(document).ready(function() {
    let selectedUserId;

    // Open modal when 'Send Message' button is clicked
    $(document).on('click', '.snd-msg', function() {
        selectedUserId = $(this).attr('id');
        $('#msgUserId').val(selectedUserId);
        $('#messageText').val('');
        $('#sendMsgModal').modal('show');
    });

    // Send message button in the modal
    $('#sendMsgBtn').on('click', function() {
        var message = $('#messageText').val().trim();

        if (message === '') {
            alert("Message cannot be empty.");
            return;
        }

        // Send the message via AJAX
        $.ajax({
            url: 'snd_message.php',
            method: 'POST',
            data: {
                user_id: selectedUserId,
                message: message
            },
            success: function(response) {
                $('#sendMsgModal').modal('hide');  // Hide modal after sending the message
                
                // Show the success notification
                showNotification("Success", "Your message has been sent.");
                
                // Optionally reload the page after 2 seconds
                setTimeout(function() {
                    location.reload();
                }, 2000); // Wait for 2 seconds before reloading
            },
            error: function(xhr, status, error) {
                $('#sendMsgModal').modal('hide');  // Hide modal if error occurs
                
                // Show the error notification
                showNotification("Error", "There was an error sending your message.");
            }
        });
    });

    // Hide the modal when the Cancel button is clicked
    $('#sendMsgModal').on('hidden.bs.modal', function () {
        // Clear the message field and reset the selected user ID
        $('#messageText').val('');
        selectedUserId = null;
    });

    // Function to display custom notification (success or error)
    function showNotification(type, message) {
        var notificationClass = type === "Success" ? "alert-success" : "alert-danger";
        
        // Show notification with the corresponding message
        $('#notification').removeClass('d-none');
        $('#notification .alert').removeClass('alert-success alert-danger').addClass(notificationClass);
        $('#notification .alert strong').text(type + "!");
        $('#notification .alert').text(message);
        
        // Auto-hide the notification after 5 seconds
        setTimeout(function() {
            $('#notification').addClass('d-none');
        }, 5000);
    }
});
</script>


<!-- Your other scripts -->
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>

</body>
</html>

<?php 
$stmt->close(); 
$users_stmt->close(); 
$search_stmt->close(); 
$conn->close(); 
?>
