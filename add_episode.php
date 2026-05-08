<?php
session_start();
include "config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Add Episode</title>
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
        .episode-progress {
            margin-top: 16px;
        }

        .progress-container {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
        }

        .episode-progress-bar {
            width: 0;
            background-color: #4caf50;
            color: white;
            text-align: center;
            min-height: 20px;
        }

        .episode-status {
            margin-top: 8px;
            font-size: 13px;
        }

        .media-help {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
            display: block;
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
                            <h4 class="mb-0">Add Episode</h4>
                            <small>Upload media from your computer or paste direct links/paths</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pd-20 card-box mb-30">
                <div class="section-header-custom">
                    <h4 class="section-title-custom">ADD EPISODE</h4>
                    <p class="section-subtitle-custom">Supports direct MP4, M3U8, local uploads, and thumbnail uploads/links</p>
                </div>

                <form id="episodeForm" enctype="multipart/form-data">
                    <input type="hidden" name="season_id" value="<?php echo isset($_GET['season_id']) ? intval($_GET['season_id']) : 0; ?>" />

                    <div class="episode-container">
                        <div class="episode">
                            <div class="form-group row">
                                <label class="col-sm-12 col-md-2 col-form-label">Episode No:</label>
                                <div class="col-sm-12 col-md-10">
                                    <input class="form-control" type="number" name="episode_number[]" placeholder="Enter Episode Number" required />
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-12 col-md-2 col-form-label">Video Link/Path</label>
                                <div class="col-sm-12 col-md-10">
                                    <input type="text" class="form-control" name="video[]" placeholder="https://example.com/video.m3u8 or uploads/videos/file.mp4">
                                    <small class="media-help">Paste a direct video URL/path, or choose a file below. Upload wins if both are given.</small>
                                    <input type="file" class="form-control mt-2" name="video_file[]" accept=".mp4,.m3u8,.mkv,.webm,.mov,.ts,video/*">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-12 col-md-2 col-form-label">Description:</label>
                                <div class="col-sm-12 col-md-10">
                                    <textarea class="form-control" rows="4" name="description[]"></textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-12 col-md-2 col-form-label">Privacy:</label>
                                <div class="col-sm-12 col-md-10">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="privacyOptions[0]" value="public" />
                                        <label class="form-check-label">Public</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="privacyOptions[0]" value="private" checked />
                                        <label class="form-check-label">Private</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-12 col-md-2 col-form-label">Download Access:</label>
                                <div class="col-sm-12 col-md-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="downloadAccessOptions[0]" value="gallery" />
                                        <label class="form-check-label">Gallery</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="downloadAccessOptions[0]" value="appStorage" checked />
                                        <label class="form-check-label">App Storage</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="downloadAccessOptions[0]" value="both" />
                                        <label class="form-check-label">Both</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="downloadAccessOptions[0]" value="never" />
                                        <label class="form-check-label">Never</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-12 col-md-2 col-form-label">Thumbnail Link/Path</label>
                                <div class="col-sm-12 col-md-10">
                                    <input type="text" class="form-control" name="thumbnail[]" placeholder="https://example.com/thumb.jpg, http://example.com/thumb.jpg, or uploads/thumbnails/episode/file.jpg">
                                    <small class="media-help">Paste an image link/path, or choose a file below. Upload wins if both are given.</small>
                                    <input type="file" class="form-control mt-2" name="thumbnail_file[]" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                                </div>
                            </div>

                            <div class="episode-progress">
                                <div class="progress-container">
                                    <div class="episode-progress-bar">0%</div>
                                </div>
                                <div class="episode-status"></div>
                            </div>
                            <hr>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-12 col-md-2"></div>
                        <div class="col-sm-12 col-md-10">
                            <button type="button" class="btn btn-custom-red" id="addEpisodeBtn">
                                <i class="fas fa-plus-circle"></i> Add Epi.
                            </button>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-sm-12 col-md-2"></div>
                        <div class="col-sm-12 col-md-10">
                            <button type="submit" class="btn btn-custom-red" id="submitEpisodesBtn">
                                <i class="fas fa-paper-plane"></i> Submit
                            </button>
                        </div>
                    </div>
                </form>
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

        async function uploadEpisode(formData, onProgress) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'submit_episode.php', true);

                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        const percentComplete = Math.round((event.loaded / event.total) * 100);
                        onProgress(percentComplete);
                    }
                };

                xhr.onload = function() {
                    if (xhr.status !== 200) {
                        reject(new Error(`HTTP error: ${xhr.status}`));
                        return;
                    }

                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === 'success') {
                            resolve(response);
                            return;
                        }
                        reject(new Error(response.message || 'Upload failed.'));
                    } catch (error) {
                        reject(new Error('Invalid server response.'));
                    }
                };

                xhr.onerror = function() {
                    reject(new Error('Network error during upload.'));
                };

                xhr.send(formData);
            });
        }

        document.getElementById('submitEpisodesBtn').addEventListener('click', async function(event) {
            event.preventDefault();

            const seasonId = new URLSearchParams(window.location.search).get('season_id');
            const episodes = document.querySelectorAll('.episode');
            let errorsOccurred = false;
            const errorMessages = [];

            for (let i = 0; i < episodes.length; i++) {
                const episode = episodes[i];
                const statusElement = episode.querySelector('.episode-status');
                const progressBar = episode.querySelector('.episode-progress-bar');

                const formData = new FormData();
                formData.append('season_id', seasonId);
                formData.append('episode_number', episode.querySelector('input[name="episode_number[]"]').value);
                formData.append('description', episode.querySelector('textarea[name="description[]"]').value);
                formData.append('privacyOptions', episode.querySelector(`input[name="privacyOptions[${i}]"]:checked`)?.value || 'private');
                formData.append('downloadAccessOptions', episode.querySelector(`input[name="downloadAccessOptions[${i}]"]:checked`)?.value || 'appStorage');
                formData.append('video', episode.querySelector('input[name="video[]"]').value);
                formData.append('thumbnail', episode.querySelector('input[name="thumbnail[]"]').value);

                const videoFile = episode.querySelector('input[name="video_file[]"]').files[0];
                const thumbnailFile = episode.querySelector('input[name="thumbnail_file[]"]').files[0];
                if (videoFile) formData.append('video_file', videoFile);
                if (thumbnailFile) formData.append('thumbnail_file', thumbnailFile);

                statusElement.textContent = `Uploading episode ${i + 1}/${episodes.length}...`;
                statusElement.style.color = 'black';

                try {
                    await uploadEpisode(formData, (progress) => {
                        progressBar.style.width = `${progress}%`;
                        progressBar.textContent = `${progress}%`;
                    });

                    statusElement.textContent = `Episode ${i + 1}/${episodes.length} uploaded successfully.`;
                    statusElement.style.color = 'green';
                } catch (error) {
                    errorsOccurred = true;
                    statusElement.textContent = `Error: ${error.message}`;
                    statusElement.style.color = 'red';
                    errorMessages.push(`Episode ${i + 1}: ${error.message}`);
                }
            }

            if (errorsOccurred) {
                alert('Some episodes failed:\n' + errorMessages.join('\n'));
                return;
            }

            window.location.href = `view_episods.php?season_id=${seasonId}`;
        });

        document.getElementById('addEpisodeBtn').addEventListener('click', function() {
            const container = document.querySelector('.episode-container');
            const episodes = document.querySelectorAll('.episode');
            const sourceEpisode = episodes[episodes.length - 1];
            const template = sourceEpisode.cloneNode(true);
            const newIndex = episodes.length;

            template.querySelectorAll('input, textarea').forEach((field) => {
                if (field.type === 'radio') {
                    if (field.value === 'public' || field.value === 'private') {
                        field.name = `privacyOptions[${newIndex}]`;
                    } else {
                        field.name = `downloadAccessOptions[${newIndex}]`;
                    }
                    return;
                }

                if (field.type === 'file') {
                    field.value = '';
                    return;
                }
            });

            template.querySelector('.episode-progress-bar').style.width = '0%';
            template.querySelector('.episode-progress-bar').textContent = '0%';
            template.querySelector('.episode-status').textContent = '';
            container.appendChild(template);
        });
    </script>
</body>
</html>
