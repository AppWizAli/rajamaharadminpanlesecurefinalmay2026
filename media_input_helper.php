<?php

include_once __DIR__ . DIRECTORY_SEPARATOR . 'chunk_upload_helper.php';

function media_join_path($base, $child) {
    return rtrim($base, "/\\") . DIRECTORY_SEPARATOR . ltrim($child, "/\\");
}

function media_ensure_directory($dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function media_public_path($relativePath) {
    return str_replace(DIRECTORY_SEPARATOR, '/', ltrim($relativePath, "/\\"));
}

function media_public_base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? "localhost";
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . "://" . $host . ($dir !== '' ? $dir : '');
}

function media_to_client_url($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (media_is_url($value)) {
        return $value;
    }

    $relative = ltrim(str_replace('\\', '/', $value), '/');
    return rtrim(media_public_base_url(), '/') . '/' . $relative;
}

function media_guess_extension($filename, $fallback = '') {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $ext !== '' ? $ext : $fallback;
}

function media_is_url($value) {
    return is_string($value) && preg_match('#^https?://#i', trim($value));
}

function media_extension_matches($value, array $allowedExtensions) {
    $path = parse_url($value, PHP_URL_PATH);
    if (!$path) {
        return false;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, $allowedExtensions, true);
}

function media_validate_remote_url($value, array $allowedExtensions, $label) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        throw new RuntimeException("Invalid {$label} URL.");
    }

    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    if ($scheme !== 'http' && $scheme !== 'https') {
        throw new RuntimeException("Only HTTP or HTTPS {$label} URLs are allowed.");
    }

    if (!media_extension_matches($value, $allowedExtensions)) {
        throw new RuntimeException("Unsupported {$label} URL format.");
    }

    return $value;
}

function media_validate_relative_path($value, array $allowedExtensions, $label) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (strpos($value, '..') !== false) {
        throw new RuntimeException("Invalid {$label} path.");
    }

    if (!media_extension_matches($value, $allowedExtensions)) {
        throw new RuntimeException("Unsupported {$label} path format.");
    }

    return ltrim(str_replace('\\', '/', $value), '/');
}

function media_store_uploaded_file(array $file, $relativeDirectory, array $allowedExtensions, $prefix, $label) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $error = $file['error'] ?? UPLOAD_ERR_OK;
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The {$label} file is larger than the server upload limit. Increase upload_max_filesize and post_max_size, then try again.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The {$label} upload was interrupted. Please retry the upload.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "The server is missing a temporary upload folder.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "The server could not write the uploaded {$label} file.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "The {$label} upload was blocked by a server extension.";
                break;
            default:
                $message = "Failed to upload {$label}.";
        }
        throw new RuntimeException($message);
    }

    $originalName = $file['name'] ?? '';
    $ext = media_guess_extension($originalName);
    if (!in_array($ext, $allowedExtensions, true)) {
        throw new RuntimeException("Unsupported {$label} file type.");
    }

    $absoluteDirectory = media_join_path(__DIR__, $relativeDirectory);
    media_ensure_directory($absoluteDirectory);

    $generatedName = sprintf(
        '%s_%s_%s.%s',
        $prefix,
        date('Ymd_His'),
        bin2hex(random_bytes(6)),
        $ext
    );

    $absolutePath = media_join_path($absoluteDirectory, $generatedName);
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException("The {$label} upload was not received correctly. Please retry the upload.");
    }

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException("Could not save uploaded {$label}. Please check server disk space and folder permissions.");
    }

    return media_to_client_url(
        media_public_path(trim($relativeDirectory, "/\\") . '/' . $generatedName)
    );
}

function media_store_staged_upload($uploadToken, $stagedPurpose, $relativeDirectory, array $allowedExtensions, $prefix, $label) {
    $uploadToken = trim((string) $uploadToken);
    if ($uploadToken === '') {
        return '';
    }

    $meta = chunk_upload_read_meta($uploadToken);
    if (($meta['purpose'] ?? '') !== $stagedPurpose) {
        throw new RuntimeException("Unsupported {$label} upload session.");
    }

    $originalName = chunk_upload_safe_name($meta['original_name'] ?? 'upload.bin');
    $ext = media_guess_extension($originalName);
    if (!in_array($ext, $allowedExtensions, true)) {
        chunk_upload_cleanup($uploadToken);
        throw new RuntimeException("Unsupported {$label} file type.");
    }

    $absoluteDirectory = media_join_path(__DIR__, $relativeDirectory);
    media_ensure_directory($absoluteDirectory);

    $generatedName = sprintf(
        '%s_%s_%s.%s',
        $prefix,
        date('Ymd_His'),
        bin2hex(random_bytes(6)),
        $ext
    );

    $absolutePath = media_join_path($absoluteDirectory, $generatedName);
    $claimed = chunk_upload_claim_file($uploadToken, $stagedPurpose, $absolutePath);
    if (empty($claimed['absolute_path']) || !is_file($claimed['absolute_path'])) {
        throw new RuntimeException("Could not save uploaded {$label}. Please check server disk space and folder permissions.");
    }

    return media_to_client_url(
        media_public_path(trim($relativeDirectory, "/\\") . '/' . $generatedName)
    );
}

function resolve_media_value(array $file, $textValue, array $options) {
    $label = $options['label'];
    $relativeDirectory = $options['relativeDirectory'];
    $allowedExtensions = $options['allowedExtensions'];
    $prefix = $options['prefix'];
    $stagedUploadToken = trim((string) ($options['stagedUploadToken'] ?? ''));
    $stagedUploadPurpose = trim((string) ($options['stagedUploadPurpose'] ?? ''));
    $allowRemote = $options['allowRemote'] ?? true;
    $allowRelative = $options['allowRelative'] ?? true;
    $required = $options['required'] ?? false;
    $existingValue = trim((string) ($options['existingValue'] ?? ''));

    $uploadedValue = media_store_uploaded_file($file, $relativeDirectory, $allowedExtensions, $prefix, $label);
    if ($uploadedValue !== '') {
        return $uploadedValue;
    }

    if ($stagedUploadToken !== '' && $stagedUploadPurpose !== '') {
        $stagedValue = media_store_staged_upload(
            $stagedUploadToken,
            $stagedUploadPurpose,
            $relativeDirectory,
            $allowedExtensions,
            $prefix,
            $label
        );
        if ($stagedValue !== '') {
            return $stagedValue;
        }
    }

    $textValue = trim((string) $textValue);
    if ($textValue !== '') {
        if ($allowRemote && media_is_url($textValue)) {
            return media_validate_remote_url($textValue, $allowedExtensions, $label);
        }

        if ($allowRelative) {
            return media_validate_relative_path($textValue, $allowedExtensions, $label);
        }

        throw new RuntimeException("Unsupported {$label} value.");
    }

    if ($existingValue !== '') {
        return $existingValue;
    }

    if ($required) {
        throw new RuntimeException("Please provide {$label} by upload or direct link.");
    }

    return '';
}

function media_is_local_managed_video($value) {
    if (!is_string($value)) {
        return false;
    }

    $normalized = ltrim(str_replace('\\', '/', trim($value)), '/');
    return strpos($normalized, 'uploads/videos/') === 0;
}

function enforce_secure_video_policy($videoPath, $privacy, $downloadAccess) {
    $isRemote = media_is_url($videoPath);

    if ($isRemote) {
        $scheme = strtolower((string) parse_url($videoPath, PHP_URL_SCHEME));
        if ($scheme !== 'https') {
            throw new RuntimeException("Only HTTPS external video URLs are allowed.");
        }
    }
}

function media_resolve_local_public_file($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (media_is_url($value)) {
        $path = parse_url($value, PHP_URL_PATH);
        if (!$path) {
            return null;
        }
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if (($pos = strpos($normalized, 'uploads/')) !== false) {
            $normalized = substr($normalized, $pos);
        }
    } else {
        $normalized = ltrim(str_replace('\\', '/', $value), '/');
    }

    if (strpos($normalized, 'uploads/') !== 0) {
        return null;
    }

    $full = media_join_path(__DIR__, $normalized);
    $realBase = realpath(media_join_path(__DIR__, 'uploads'));
    $realFile = realpath($full);
    if ($realBase === false || $realFile === false) {
        return null;
    }
    if (strpos($realFile, $realBase) !== 0) {
        return null;
    }

    return $realFile;
}
?>
