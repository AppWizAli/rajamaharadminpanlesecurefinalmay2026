<?php
session_start();
header('Content-Type: application/json');

include "chunk_upload_helper.php";

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized upload session.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid upload request method.'
    ]);
    exit;
}

try {
    $result = chunk_upload_store_chunk(
        $_POST['purpose'] ?? '',
        $_POST['upload_id'] ?? '',
        $_POST['original_name'] ?? '',
        $_POST['chunk_index'] ?? -1,
        $_POST['total_chunks'] ?? 0,
        $_FILES['chunk'] ?? []
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Chunk uploaded successfully.',
        'upload_id' => $result['upload_id'],
        'chunk_index' => $result['chunk_index'],
        'received_chunks' => $result['received_chunks'],
        'total_chunks' => $result['total_chunks']
    ]);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
