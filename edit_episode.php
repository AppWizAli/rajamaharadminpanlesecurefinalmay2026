<?php
session_start();
include "config.php";
include "video_security.php";
include "media_input_helper.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    header('Content-Type: application/json');

    $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
    $season_id = isset($_POST['season_id']) ? intval($_POST['season_id']) : 0;
    $episode_number = isset($_POST['episode_number']) ? trim($_POST['episode_number']) : '';
    $video_path = isset($_POST['video_path']) ? trim($_POST['video_path']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $privacy = isset($_POST['privacy']) ? trim($_POST['privacy']) : 'private';
    $download_access = isset($_POST['downloadAccessOptions']) ? trim($_POST['downloadAccessOptions']) : 'never';
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
                'storeRelativePath' => true,
                'required' => true,
                'existingValue' => decrypt_video_path_if_needed($currentMedia['video_path'] ?? '', $VIDEO_URL_ENCRYPTION_KEY)
            ]
        );
        enforce_secure_video_policy($resolvedVideoPath, $privacy, $download_access);
    } catch (RuntimeException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }

    $storedVideoPath = encrypt_video_path_for_storage($resolvedVideoPath, $VIDEO_URL_ENCRYPTION_KEY);

    $sql = "UPDATE episode SET episode_number = ?, video_path = ?, description = ?, privacy = ?, download_access = ?, thumbnail = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("ssssssi", $episode_number, $storedVideoPath, $description, $privacy, $download_access, $resolvedThumbnail, $episode_id);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Episode updated successfully',
            'episode_id' => $episode_id,
            'season_id' => $season_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update episode: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

$episode_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$episode = null;

if ($episode_id > 0) {
    $sql = "SELECT * FROM episode WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $episode_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $episode = $result->fetch_assoc();
    $stmt->close();
}

$episodeVideoPath = isset($episode['video_path'])
    ? decrypt_video_path_if_needed($episode['video_path'], $VIDEO_URL_ENCRYPTION_KEY)
    : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Episode</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="vendors/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="vendors/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="vendors/images/favicon-16x16.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="src/plugins/jvectormap/jquery-jvectormap-2.0.3.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
    <link rel="stylesheet" href="vendors/styles/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <style>
        #upload-overlay {
            display: none;
            position: fixed;
            width: 20%;
            height: 30%;
            top: 45%;
            left: 45%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            text-align: center;
        }

        #upload-overlay .loading-text {
            background: white;
            padding: 20px;
            border-radius: 10px;
            font-size: 20px;
            font-weight: bold;
        }

        .progress-container {
            margin-top: 20px;
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
        }

        .progress-bar {
            width: 0;
            height: 20px;
            background-color: #4caf50;
            border-radius: 5px;
            text-align: center;
        }

        .progress-percentage {
            font-size: 16px;
            margin-top: 10px;
        }

        .encryption-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 10px;
            background-color: #28a745;
            color: white;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
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
                            <h4 class="mb-0">Edit Episode</h4>
                            <small>Update episode media by link or direct upload</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pd-20 card-box mb-30">
                <div id="alertMessage" class="alert"></div>

                <form id="episodeForm" enctype="multipart/form-data">
                    <?php if ($episode_id > 0) { ?>
                        <input type="hidden" name="episode_id" value="<?php echo $episode_id; ?>" />
                    <?php } ?>
                    <input type="hidden" name="season_id" value="<?php echo isset($episode['season_id']) ? intval($episode['season_id']) : 0; ?>" />

                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Episode No:</label>
                        <div class="col-sm-12 col-md-10">
                            <input class="form-control" type="number" name="episode_number" value="<?php echo isset($episode['episode_number']) ? htmlspecialchars($episode['episode_number']) : ''; ?>" required />
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Video Source <span class="encryption-badge">Server Protected</span></label>
                        <div class="col-sm-12 col-md-10">
                            <input class="form-control" type="text" name="video_path" value="<?php echo htmlspecialchars($episodeVideoPath); ?>" placeholder="https://example.com/video.m3u8 or uploads/videos/file.mp4" required />
                            <small class="form-text text-muted">Paste MP4, M3U8, or server path. If you choose a file below, the upload will be used instead.</small>
                            <input class="form-control mt-2" type="file" name="video_file" accept=".mp4,.m3u8,.mkv,.webm,.mov,.ts,video/*">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Description:</label>
                        <div class="col-sm-12 col-md-10">
                            <textarea class="form-control" rows="4" name="description"><?php echo isset($episode['description']) ? htmlspecialchars($episode['description']) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Privacy:</label>
                        <div class="col-sm-12 col-md-10">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="privacy" value="public" <?php echo isset($episode['privacy']) && $episode['privacy'] == 'public' ? 'checked' : ''; ?> />
                                <label class="form-check-label">Public</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="privacy" value="private" <?php echo !isset($episode['privacy']) || $episode['privacy'] == 'private' ? 'checked' : ''; ?> />
                                <label class="form-check-label">Private</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Download Access:</label>
                        <div class="col-sm-12 col-md-10">
                            <?php
                            $downloadValue = $episode['download_access'] ?? 'appStorage';
                            $downloadOptions = ['gallery' => 'Gallery', 'appStorage' => 'App Storage', 'both' => 'Both', 'never' => 'Never'];
                            foreach ($downloadOptions as $value => $label) {
                            ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="downloadAccessOptions" value="<?php echo $value; ?>" <?php echo $downloadValue === $value ? 'checked' : ''; ?> />
                                    <label class="form-check-label"><?php echo $label; ?></label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-12 col-md-2 col-form-label">Thumbnail</label>
                        <div class="col-sm-12 col-md-10">
                            <input class="form-control" type="text" name="thumbnail" value="<?php echo isset($episode['thumbnail']) ? htmlspecialchars($episode['thumbnail']) : ''; ?>" placeholder="https://example.com/thumb.jpg or uploads/thumbnails/file.jpg">
                            <small class="form-text text-muted">Paste an image URL/path or choose an image file below.</small>
                            <input class="form-control mt-2" type="file" name="thumbnail_file" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-12 col-md-2"></div>
                        <div class="col-sm-12 col-md-10">
                            <button type="submit" id="submitEpisodesBtn" class="btn btn-custom-red">
                                <i class="fas fa-edit"></i> Update Episode
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="upload-overlay">
        <div class="loading-text">
            Processing...
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
            <div class="progress-percentage" id="progress-percentage">0%</div>
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

        document.getElementById('submitEpisodesBtn').addEventListener('click', async function(event) {
            event.preventDefault();

            const form = document.getElementById('episodeForm');
            const overlay = document.getElementById('upload-overlay');
            const progressBar = document.getElementById('progress-bar');
            const progressPercentage = document.getElementById('progress-percentage');
            const alertMessage = document.getElementById('alertMessage');

            overlay.style.display = 'block';
            progressBar.style.width = '30%';
            progressPercentage.textContent = '30%';

            try {
                const formData = new FormData(form);
                formData.append('ajax_update', '1');

                progressBar.style.width = '60%';
                progressPercentage.textContent = '60%';

                const response = await fetch(window.location.href.split('?')[0], {
                    method: 'POST',
                    body: formData
                });

                progressBar.style.width = '90%';
                progressPercentage.textContent = '90%';

                const result = await response.json();

                progressBar.style.width = '100%';
                progressPercentage.textContent = '100%';

                if (result.status === 'success') {
                    alertMessage.textContent = 'Episode updated successfully! Redirecting...';
                    alertMessage.className = 'alert alert-success show';

                    setTimeout(() => {
                        window.location.href = 'view_episods.php?season_id=' + result.season_id;
                    }, 1500);
                    return;
                }

                throw new Error(result.message || 'Update failed');
            } catch (error) {
                alertMessage.textContent = 'Error: ' + error.message;
                alertMessage.className = 'alert alert-danger show';
                overlay.style.display = 'none';
            }
        });
    </script>
</body>
</html>
