<?php
session_start();
include "config.php";
include "apk_schema.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

ensure_apk_table($conn);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function public_apk_url($path) {
    $path = ltrim(str_replace('\\', '/', (string)$path), '/');
    return $path === '' ? '#' : $path;
}

$latest = $conn->query("SELECT * FROM apk_files WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
$latestApk = $latest && $latest->num_rows > 0 ? $latest->fetch_assoc() : null;
$history = $conn->query("SELECT * FROM apk_files ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Panel - APK Updates</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="vendors/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="vendors/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="vendors/images/favicon-16x16.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <link rel="stylesheet" href="vendors/styles/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="vendors/styles/mycss/Darams.css">
    <style>
        .apk-summary { border: 1px solid #edf0f5; border-radius: 8px; padding: 16px; background: #fff; }
        .meta { font-size: 12px; color: #667085; line-height: 1.6; }
        .badge-current { display:inline-block; padding:6px 10px; border-radius:999px; background:#e7f6ec; color:#027a48; font-weight:700; font-size:12px; }
        .upload-note { color:#667085; font-size:13px; }
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
                        <h4 class="mb-0">APK Updates</h4>
                        <small>Upload a higher app version. Splash screen will offer only the latest active APK.</small>
                    </div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent mb-0 p-0">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">APK Updates</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo h($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo h($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4 mb-30">
                <div class="apk-summary h-100">
                    <h5>Current Latest APK</h5>
                    <?php if ($latestApk): ?>
                        <div class="mt-3">
                            <span class="badge-current">Active</span>
                            <h4 class="mt-2 mb-1">Version <?php echo h($latestApk['version_name'] ?: $latestApk['string']); ?></h4>
                            <div class="meta">
                                Version code: <?php echo h($latestApk['version_code']); ?><br>
                                Uploaded: <?php echo h($latestApk['created_at']); ?><br>
                                File: <?php echo h($latestApk['original_name'] ?: basename($latestApk['apk_url'])); ?><br>
                                Size: <?php echo number_format((intval($latestApk['file_size']) / 1024 / 1024), 2); ?> MB
                            </div>
                            <a class="btn btn-sm btn-outline-primary mt-3" href="<?php echo h(public_apk_url($latestApk['apk_url'])); ?>">Download Current</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mt-3">No APK uploaded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-8 mb-30">
                <div class="pd-20 card-box">
                    <div class="section-header-custom">
                        <h4 class="section-title-custom">Upload New APK</h4>
                        <p class="section-subtitle-custom">Enter a version greater than the current APK, then choose the APK file.</p>
                    </div>

                    <form action="apk.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group row">
                            <label class="col-sm-12 col-md-3 col-form-label">Version Name</label>
                            <div class="col-sm-12 col-md-9">
                                <input class="form-control" name="version_name" type="text" placeholder="Example: 18.2" required>
                                <div class="upload-note">This is the version displayed and compared by older app builds.</div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-12 col-md-3 col-form-label">Version Code</label>
                            <div class="col-sm-12 col-md-9">
                                <input class="form-control" name="version_code" type="number" min="1" placeholder="Example: 19">
                                <div class="upload-note">Recommended. New app builds compare this number first.</div>
                            </div>
                        </div>
                        <div class="form-group row custom-file-upload-group">
                            <label class="col-sm-12 col-md-3 col-form-label">APK File</label>
                            <div class="col-sm-12 col-md-9">
                                <input type="file" class="form-control" id="apk-upload" name="apk_file" accept=".apk,application/vnd.android.package-archive" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-12 col-md-3"></div>
                            <div class="col-sm-12 col-md-9">
                                <button type="submit" name="upload_apk" class="btn btn-custom-red">Upload And Set Latest</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="pd-20 card-box mb-30">
            <h5>Previous APK Versions</h5>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-borderless">
                    <thead class="bg-dark text-white">
                    <tr>
                        <th>Status</th>
                        <th>Version</th>
                        <th>Version Code</th>
                        <th>File</th>
                        <th>Uploaded</th>
                        <th>Download</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($history && $history->num_rows > 0): ?>
                        <?php while ($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo intval($row['is_active']) === 1 ? '<span class="badge-current">Active</span>' : '<span class="text-muted">Old</span>'; ?></td>
                                <td><?php echo h($row['version_name'] ?: $row['string']); ?></td>
                                <td><?php echo h($row['version_code']); ?></td>
                                <td>
                                    <?php echo h($row['original_name'] ?: basename($row['apk_url'])); ?>
                                    <div class="meta"><?php echo number_format((intval($row['file_size']) / 1024 / 1024), 2); ?> MB</div>
                                </td>
                                <td><?php echo h($row['created_at']); ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="<?php echo h(public_apk_url($row['apk_url'])); ?>">Download</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">No APK history yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
</body>
</html>
<?php $conn->close(); ?>
