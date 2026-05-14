<?php

function chunk_upload_root_dir() {
    return __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'chunks';
}

function chunk_upload_safe_id($uploadId) {
    $safe = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $uploadId);
    if ($safe === '') {
        throw new RuntimeException('Invalid upload session.');
    }
    return $safe;
}

function chunk_upload_safe_name($name) {
    $base = basename((string) $name);
    $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
    return $safe !== '' ? $safe : 'upload.bin';
}

function chunk_upload_purpose_config($purpose) {
    $map = [
        'apk' => [
            'label' => 'APK',
            'allowedExtensions' => ['apk']
        ],
        'episode_video' => [
            'label' => 'episode video',
            'allowedExtensions' => ['mp4', 'm3u8', 'mkv', 'webm', 'mov', 'ts']
        ]
    ];

    if (!isset($map[$purpose])) {
        throw new RuntimeException('Unsupported upload purpose.');
    }

    return $map[$purpose];
}

function chunk_upload_meta_path($uploadId) {
    return chunk_upload_session_dir($uploadId) . DIRECTORY_SEPARATOR . 'meta.json';
}

function chunk_upload_session_dir($uploadId) {
    return chunk_upload_root_dir() . DIRECTORY_SEPARATOR . chunk_upload_safe_id($uploadId);
}

function chunk_upload_cleanup_expired($maxAgeSeconds = 86400) {
    $root = chunk_upload_root_dir();
    if (!is_dir($root)) {
        return;
    }

    foreach (glob($root . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
        if (!is_dir($path)) {
            continue;
        }
        $updatedAt = filemtime($path);
        if ($updatedAt !== false && (time() - $updatedAt) < $maxAgeSeconds) {
            continue;
        }
        chunk_upload_delete_tree($path);
    }
}

function chunk_upload_delete_tree($path) {
    if (!file_exists($path)) {
        return;
    }

    if (is_file($path)) {
        @unlink($path);
        return;
    }

    foreach (scandir($path) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        chunk_upload_delete_tree($path . DIRECTORY_SEPARATOR . $item);
    }
    @rmdir($path);
}

function chunk_upload_write_meta($uploadId, array $meta) {
    $dir = chunk_upload_session_dir($uploadId);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(chunk_upload_meta_path($uploadId), json_encode($meta, JSON_PRETTY_PRINT));
}

function chunk_upload_read_meta($uploadId) {
    $path = chunk_upload_meta_path($uploadId);
    if (!is_file($path)) {
        throw new RuntimeException('Upload session data is missing.');
    }

    $meta = json_decode((string) file_get_contents($path), true);
    if (!is_array($meta)) {
        throw new RuntimeException('Upload session data is invalid.');
    }

    return $meta;
}

function chunk_upload_store_chunk($purpose, $uploadId, $originalName, $chunkIndex, $totalChunks, array $chunkFile) {
    chunk_upload_cleanup_expired();

    $config = chunk_upload_purpose_config($purpose);
    $uploadId = chunk_upload_safe_id($uploadId);
    $originalName = chunk_upload_safe_name($originalName);
    $chunkIndex = (int) $chunkIndex;
    $totalChunks = (int) $totalChunks;

    if ($originalName === '') {
        throw new RuntimeException("Missing {$config['label']} filename.");
    }
    if ($totalChunks <= 0 || $chunkIndex < 0 || $chunkIndex >= $totalChunks) {
        throw new RuntimeException('Invalid upload chunk information.');
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $config['allowedExtensions'], true)) {
        throw new RuntimeException("Unsupported {$config['label']} file type.");
    }

    if (($chunkFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($chunkFile['tmp_name'])) {
        throw new RuntimeException("Failed to receive {$config['label']} chunk.");
    }

    $dir = chunk_upload_session_dir($uploadId);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $meta = [
        'purpose' => $purpose,
        'original_name' => $originalName,
        'total_chunks' => $totalChunks,
        'updated_at' => time()
    ];

    if (is_file(chunk_upload_meta_path($uploadId))) {
        $existingMeta = chunk_upload_read_meta($uploadId);
        if (
            ($existingMeta['purpose'] ?? '') !== $purpose ||
            ($existingMeta['original_name'] ?? '') !== $originalName ||
            (int) ($existingMeta['total_chunks'] ?? 0) !== $totalChunks
        ) {
            chunk_upload_delete_tree($dir);
            mkdir($dir, 0755, true);
        }
    }

    chunk_upload_write_meta($uploadId, $meta);

    $chunkPath = $dir . DIRECTORY_SEPARATOR . sprintf('chunk_%05d.part', $chunkIndex);
    if (!move_uploaded_file($chunkFile['tmp_name'], $chunkPath)) {
        throw new RuntimeException("Could not save {$config['label']} chunk on the server.");
    }

    $receivedChunks = 0;
    foreach (glob($dir . DIRECTORY_SEPARATOR . 'chunk_*.part') ?: [] as $file) {
        if (is_file($file)) {
            $receivedChunks++;
        }
    }

    return [
        'upload_id' => $uploadId,
        'chunk_index' => $chunkIndex,
        'received_chunks' => $receivedChunks,
        'total_chunks' => $totalChunks
    ];
}

function chunk_upload_materialize($uploadId, $expectedPurpose) {
    chunk_upload_cleanup_expired();

    $uploadId = chunk_upload_safe_id($uploadId);
    $meta = chunk_upload_read_meta($uploadId);
    if (($meta['purpose'] ?? '') !== $expectedPurpose) {
        throw new RuntimeException('Upload purpose does not match the requested file.');
    }

    $dir = chunk_upload_session_dir($uploadId);
    $totalChunks = (int) ($meta['total_chunks'] ?? 0);
    if ($totalChunks <= 0) {
        throw new RuntimeException('Upload session is incomplete.');
    }

    $originalName = chunk_upload_safe_name($meta['original_name'] ?? 'upload.bin');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $assembledPath = $dir . DIRECTORY_SEPARATOR . 'assembled_' . $uploadId . ($extension !== '' ? '.' . $extension : '');

    if (is_file($assembledPath)) {
        @unlink($assembledPath);
    }

    $output = fopen($assembledPath, 'wb');
    if ($output === false) {
        throw new RuntimeException('Could not create the assembled upload file.');
    }

    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkPath = $dir . DIRECTORY_SEPARATOR . sprintf('chunk_%05d.part', $i);
        if (!is_file($chunkPath)) {
            fclose($output);
            @unlink($assembledPath);
            throw new RuntimeException('Upload is incomplete. Some chunks are missing.');
        }

        $input = fopen($chunkPath, 'rb');
        if ($input === false) {
            fclose($output);
            @unlink($assembledPath);
            throw new RuntimeException('Could not read an uploaded chunk.');
        }

        while (!feof($input)) {
            $buffer = fread($input, 1024 * 1024);
            if ($buffer === false) {
                fclose($input);
                fclose($output);
                @unlink($assembledPath);
                throw new RuntimeException('Could not assemble the uploaded file.');
            }
            fwrite($output, $buffer);
        }
        fclose($input);
    }

    fclose($output);

    return [
        'tmp_path' => $assembledPath,
        'original_name' => $originalName,
        'upload_id' => $uploadId
    ];
}

function chunk_upload_claim_file($uploadId, $expectedPurpose, $destinationPath) {
    $materialized = chunk_upload_materialize($uploadId, $expectedPurpose);
    $tmpPath = $materialized['tmp_path'];

    $parent = dirname($destinationPath);
    if (!is_dir($parent)) {
        mkdir($parent, 0755, true);
    }

    $moved = @rename($tmpPath, $destinationPath);
    if (!$moved) {
        $moved = @copy($tmpPath, $destinationPath);
        if ($moved) {
            @unlink($tmpPath);
        }
    }

    if (!$moved) {
        @unlink($tmpPath);
        throw new RuntimeException('Could not move the uploaded file into storage.');
    }

    chunk_upload_cleanup($uploadId);

    return [
        'original_name' => $materialized['original_name'],
        'file_size' => @filesize($destinationPath) ?: 0,
        'absolute_path' => $destinationPath
    ];
}

function chunk_upload_cleanup($uploadId) {
    $dir = chunk_upload_session_dir($uploadId);
    chunk_upload_delete_tree($dir);
}
?>
