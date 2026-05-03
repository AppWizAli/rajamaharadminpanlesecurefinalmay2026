<?php
session_start();
include "config.php";
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo "Unauthorized access";
    exit;
}
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($group_id == 0) {
    echo "Invalid group ID";
    exit;
}
// Build the search query
if (!empty($search)) {
    $query = "SELECT u.id, u.username, u.email, gm.comment, gm.start_date, gm.end_date, gm.updated_at
              FROM users u
              JOIN group_members gm ON u.id = gm.user_id
              WHERE gm.group_id = ? AND (u.id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR gm.comment LIKE ?)
              ORDER BY CASE WHEN gm.end_date IS NULL THEN 1 ELSE 0 END, gm.end_date ASC";
    $search_param = "%" . $search . "%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $group_id, $search_param, $search_param, $search_param, $search_param);
} else {
    $query = "SELECT u.id, u.username, u.email, gm.comment, gm.start_date, gm.end_date, gm.updated_at
              FROM users u
              JOIN group_members gm ON u.id = gm.user_id
              WHERE gm.group_id = ?
              ORDER BY CASE WHEN gm.end_date IS NULL THEN 1 ELSE 0 END, gm.end_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $group_id);
}
$stmt->execute();
$result = $stmt->get_result();
// Generate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
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
    echo '<tr><td colspan="6" class="text-center">No users found.</td></tr>';
}
$stmt->close();
$conn->close();
?>