<?php
$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type']; // "video" or "thumbnail"
$name = basename($data['name']);

$dir = ($type === 'video') ? 'videos/' : 'thumbnails/';
$path = $dir . $name;

echo json_encode(['exists' => file_exists($path)]);
?>