<?php
session_start();
include "config.php";
include "video_security.php";
include "media_input_helper.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Handle AJAX update requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    header('Content-Type: application/json');
    
    $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
    $season_id = isset($_POST['season_id']) ? intval($_POST['season_id']) : 0;
    $video_path = isset($_POST['video_path']) ? trim($_POST['video_path']) : '';
    $thumbnail = isset($_POST['thumbnail']) ? trim($_POST['thumbnail']) : '';
    
    if ($episode_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid episode ID']);
        exit;
    }
    
    $currentMedia = ['video_path' => '', 'thumbnail' => ''];
    $currentStmt = $conn->prepare("SELECT video_path, thumbnail FROM episode WHERE id = ?");
    if ($currentStmt) {
        $currentStmt->bind_param("i", $episode_id);
        $currentStmt->execute();
        $currentResult = $currentStmt->get_result();
        $currentMedia = $currentResult->fetch_assoc() ?: $currentMedia;
        $currentStmt->close();
    }

    try {
        $resolvedThumbnail = resolve_media_value(
            $_FILES['thumbnail_file'] ?? [],
            $thumbnail,
            [
                'label' => 'thumbnail',
                'relativeDirectory' => 'uploads/thumbnails/episode',
                'allowedExtensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'prefix' => 'episode_thumb',
                'required' => true,
                'existingValue' => $currentMedia['thumbnail'] ?? ''
            ]
        );

        $resolvedVideoPath = resolve_media_value(
            $_FILES['video_file'] ?? [],
            $video_path,
            [
                'label' => 'video',
                'relativeDirectory' => 'uploads/videos',
                'allowedExtensions' => ['mp4', 'm3u8', 'mkv', 'webm', 'mov', 'ts'],
                'prefix' => 'episode_video',
                'required' => true,
                'existingValue' => decrypt_video_path_if_needed($currentMedia['video_path'] ?? '', $VIDEO_URL_ENCRYPTION_KEY)
            ]
        );
        $privacyStmt = $conn->prepare("SELECT privacy, download_access FROM episode WHERE id = ?");
        $episodePrivacy = 'private';
        $episodeDownloadAccess = 'never';
        if ($privacyStmt) {
            $privacyStmt->bind_param("i", $episode_id);
            $privacyStmt->execute();
            $privacyStmt->bind_result($episodePrivacy, $episodeDownloadAccess);
            $privacyStmt->fetch();
            $privacyStmt->close();
        }
        enforce_secure_video_policy($resolvedVideoPath, $episodePrivacy, $episodeDownloadAccess);
    } catch (RuntimeException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }

    $storedVideoPath = encrypt_video_path_for_storage($resolvedVideoPath, $VIDEO_URL_ENCRYPTION_KEY);
    
    // Update query
    $sql = "UPDATE episode SET video_path = ?, thumbnail = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("ssi", $storedVideoPath, $resolvedThumbnail, $episode_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Episode updated successfully',
            'episode_id' => $episode_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update episode: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// Ensure season_id is provided and is a valid integer
$season_id = isset($_GET['season_id']) ? intval($_GET['season_id']) : 0;
if ($season_id <= 0) {
    echo "Invalid season ID.";
    exit;
}

// Query to fetch drama_id and season_number from seasons table
$drama_query = "SELECT drama_id, season_number FROM season WHERE id = ?";
$drama_stmt = $conn->prepare($drama_query);
$drama_stmt->bind_param("i", $season_id);
$drama_stmt->execute();
$drama_result = $drama_stmt->get_result();

if ($drama_result->num_rows > 0) {
    $drama_row = $drama_result->fetch_assoc();
    $drama_id = $drama_row['drama_id'];
    $season_number = $drama_row['season_number'];
    
    // Query to fetch the name of the drama using drama_id
    $drama_name_query = "SELECT name FROM drama WHERE id = ?";
    $drama_name_stmt = $conn->prepare($drama_name_query);
    $drama_name_stmt->bind_param("i", $drama_id);
    $drama_name_stmt->execute();
    $drama_name_result = $drama_name_stmt->get_result();
    
    if ($drama_name_result->num_rows > 0) {
        $drama_name_row = $drama_name_result->fetch_assoc();
        $drama_name = htmlspecialchars($drama_name_row['name']);
    } else {
        echo "Drama not found.";
        exit;
    }
} else {
    echo "Season not found.";
    exit;
}

// Query to fetch episodes for the given season_id
$sql = "SELECT * FROM episode WHERE season_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $season_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if there are any episodes
if ($result->num_rows > 0) {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $drama_name; ?> - Episodes</title>
    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
    <link rel="stylesheet" href="vendors/styles/mycss/show-group.css">
    <link rel="stylesheet" href="vendors/styles/css/custom.css">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
        }
        .da-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .da-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.15);
        }
        .da-card-photo {
            position: relative;
            background: #000;
            overflow: hidden;
        }
        .da-card-photo img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: opacity 0.3s ease;
        }
        .da-card-photo img:hover {
            opacity: 0.9;
        }
        .da-card-caption {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .da-card-caption p {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            margin: 0;
            line-height: 1.4;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        .btn-danger:hover {
            background-color: #a71d2a;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        .btn-success:hover {
            background-color: #1e7e34;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #333;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: 500;
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            outline: none;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            align-items: center;
        }
        .video-container video {
            border-radius: 0;
            object-fit: cover;
            width: 100%;
            height: 200px;
        }
        .gallery-wrap .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .gallery-wrap .col-lg-3 {
            flex: 0 0 calc(25% - 15px);
            max-width: calc(25% - 15px);
        }
        @media (max-width: 992px) {
            .gallery-wrap .col-lg-3 {
                flex: 0 0 calc(50% - 10px);
                max-width: calc(50% - 10px);
            }
        }
        @media (max-width: 576px) {
            .gallery-wrap .col-lg-3 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        .page-header {
            margin-bottom: 30px;
        }
        .dashboard-header {
            border-radius: 12px;
            padding: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        .page-title h4 {
            font-size: 24px;
            font-weight: 700;
            color: #222;
        }
        .page-title small {
            color: #666;
            font-size: 14px;
        }
        .update-status {
            margin-top: 10px;
            padding: 8px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: none;
        }
        .update-status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .update-status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .update-status.processing {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .encryption-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        .encryption-badge.encrypted {
            background-color: #28a745;
            color: white;
        }
        .encryption-badge.not-encrypted {
            background-color: #dc3545;
            color: white;
        }
        .bulk-update-section {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .bulk-status {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        .bulk-status.show {
            display: block;
        }
        .bulk-status.processing {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .bulk-status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .bulk-status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    <div class="mobile-menu-overlay"></div>
    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <div class="min-height-200px">
                <div class="page-header">
                    <div class="dashboard-header mb-4 rounded shadow-sm bg-white">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <div class="page-title">
                                <h4 class="mb-0"><?php echo $drama_name; ?> - Episodes</h4>
                                <small>All episodes listed for this drama</small>
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb bg-transparent mb-0 p-0">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Episodes</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Bulk Update Section -->
                <div class="bulk-update-section">
                    <h5 style="margin-bottom: 15px; color: #333;">
                        <i class="fas fa-shield-alt"></i> Bulk Encryption Update
                    </h5>
                    <p style="color: #666; margin-bottom: 15px; font-size: 14px;">
                        Click the button below to encrypt all unencrypted video URLs in this season.
                    </p>
                    <button type="button" class="btn btn-warning" id="updateAllBtn">
                        <i class="fas fa-lock"></i> Encrypt All Video URLs
                    </button>
                    <div class="bulk-status" id="bulkStatus"></div>
                </div>

                <div class="gallery-wrap">
                    <ul class="row">
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <li class="col-lg-3 col-md-6 col-sm-12">
                                <div class="da-card box-shadow">
                                    <div class="da-card-photo">
                                        <?php 
                                        $thumbnail_path = htmlspecialchars($row['thumbnail']);
                                        // Check if thumbnail exists and is not empty
                                        if (!empty($thumbnail_path)) {
                                            echo '<img src="' . $thumbnail_path . '" alt="Episode Thumbnail" width="100%" height="200" style="object-fit: cover;" onerror="this.src=\'vendors/images/default-thumbnail.jpg\'; this.onerror=null;">';
                                        } else {
                                            echo '<img src="vendors/images/default-thumbnail.jpg" alt="No Thumbnail" width="100%" height="200" style="object-fit: cover;">';
                                        }
                                        ?>
                                    </div>
                                    <div class="da-card-caption">
                                        <p>
                                            <?php echo $drama_name; ?> -
                                            Season <?php echo htmlspecialchars($season_number); ?>,
                                            Ep <?php echo isset($row['episode_number']) ? htmlspecialchars($row['episode_number']) : 'N/A'; ?>
                                            <span class="encryption-badge" id="badge_<?php echo $row['id']; ?>">Checking...</span>
                                        </p>
                                        <div class="action-buttons">
                                            <a href="edit_episode.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete_episode.php?id=<?php echo htmlspecialchars($row['id']); ?>&season_id=<?php echo htmlspecialchars($row['season_id']); ?>" class="btn btn-danger">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                        <form class="episode-update-form mt-3" data-episode-id="<?php echo htmlspecialchars($row['id']); ?>">
                                            <input type="hidden" name="episode_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                            <input type="hidden" name="season_id" value="<?php echo htmlspecialchars($season_id); ?>">
                                            <div class="form-group">
                                                <label>Video Path:</label>
                                                <?php $decodedVideoPath = decrypt_video_path_if_needed($row['video_path'], $VIDEO_URL_ENCRYPTION_KEY); ?>
                                                <input name="video_path" value="<?php echo htmlspecialchars($decodedVideoPath); ?>" class="form-control video-path-input" data-original="<?php echo htmlspecialchars($decodedVideoPath); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Image Path (Thumbnail):</label>
                                                <input name="thumbnail" value="<?php echo htmlspecialchars($row['thumbnail']); ?>" class="form-control">
                                            </div>
                                            <button type="button" class="btn btn-success update-single-btn" data-episode-id="<?php echo htmlspecialchars($row['id']); ?>">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                            <div class="update-status" id="status_<?php echo htmlspecialchars($row['id']); ?>"></div>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoInputs = document.querySelectorAll('.video-path-input');
            
            videoInputs.forEach(input => {
                const episodeId = input.closest('.episode-update-form').dataset.episodeId;
                const badge = document.getElementById('badge_' + episodeId);
                badge.textContent = 'Server Protected';
                badge.className = 'encryption-badge encrypted';
            });
        });
        
        // Update single episode
        document.querySelectorAll('.update-single-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const episodeId = this.dataset.episodeId;
                const form = this.closest('.episode-update-form');
                const statusDiv = document.getElementById('status_' + episodeId);
                const badge = document.getElementById('badge_' + episodeId);
                
                const videoPathInput = form.querySelector('input[name="video_path"]');
                const thumbnailInput = form.querySelector('input[name="thumbnail"]');
                const videoPath = videoPathInput.value;
                const thumbnail = thumbnailInput.value;
                
                // Show processing status
                statusDiv.textContent = 'Processing...';
                statusDiv.className = 'update-status processing';
                statusDiv.style.display = 'block';
                
                try {
                    // Send update request
                    const formData = new FormData();
                    formData.append('ajax_update', '1');
                    formData.append('episode_id', episodeId);
                    formData.append('season_id', form.querySelector('input[name="season_id"]').value);
                    formData.append('video_path', videoPath);
                    formData.append('thumbnail', thumbnail);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        statusDiv.textContent = 'Updated successfully!';
                        statusDiv.className = 'update-status success';

                        badge.textContent = 'Server Protected';
                        badge.className = 'encryption-badge encrypted';
                        
                        setTimeout(() => {
                            statusDiv.style.display = 'none';
                        }, 3000);
                    } else {
                        throw new Error(result.message || 'Update failed');
                    }
                } catch (error) {
                    statusDiv.textContent = 'Error: ' + error.message;
                    statusDiv.className = 'update-status error';
                    console.error('Update error:', error);
                }
            });
        });
        
        // Update all episodes
        document.getElementById('updateAllBtn').addEventListener('click', async function() {
            const bulkStatus = document.getElementById('bulkStatus');
            const forms = document.querySelectorAll('.episode-update-form');
            let processed = 0;
            let encrypted = 0;
            let skipped = 0;
            let errors = 0;
            
            bulkStatus.textContent = 'Processing all episodes...';
            bulkStatus.className = 'bulk-status processing show';
            this.disabled = true;
            
            for (const form of forms) {
                const episodeId = form.dataset.episodeId;
                const videoPathInput = form.querySelector('input[name="video_path"]');
                const thumbnailInput = form.querySelector('input[name="thumbnail"]');
                const badge = document.getElementById('badge_' + episodeId);
                
                // Get current values from inputs (these are what's displayed on the page)
                const videoPath = videoPathInput.value;
                const thumbnail = thumbnailInput.value;
                
                try {
                    const formData = new FormData();
                    formData.append('ajax_update', '1');
                    formData.append('episode_id', episodeId);
                    formData.append('season_id', form.querySelector('input[name="season_id"]').value);
                    formData.append('video_path', videoPath);
                    formData.append('thumbnail', thumbnail);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        encrypted++;

                        badge.textContent = 'Server Protected';
                        badge.className = 'encryption-badge encrypted';
                    } else {
                        errors++;
                        console.error('Failed to update episode ' + episodeId + ':', result.message);
                    }
                    
                    processed++;
                    bulkStatus.textContent = `Processing... ${processed}/${forms.length} episodes checked, ${encrypted} encrypted, ${skipped} already encrypted`;
                    
                } catch (error) {
                    errors++;
                    processed++;
                    console.error('Error processing episode ' + episodeId + ':', error);
                }
            }
            
            // Show final result
            if (errors > 0) {
                bulkStatus.textContent = `Completed with errors. Total: ${processed}, Updated: ${encrypted}, Errors: ${errors}`;
                bulkStatus.className = 'bulk-status error show';
            } else {
                bulkStatus.textContent = `Successfully updated ${encrypted} episodes.`;
                bulkStatus.className = 'bulk-status success show';
            }
            
            this.disabled = false;
            
            setTimeout(() => {
                bulkStatus.className = 'bulk-status';
            }, 5000);
        });
    </script>
</body>
</html>
<?php
} else {
    echo "No episodes found for season ID: " . $season_id;
}
// Close the database connection and statement
$stmt->close();
$drama_stmt->close();
$drama_name_stmt->close();
$conn->close();
?>

