<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo 'Missing file parameter';
    exit;
}

$filename = basename($_GET['file']);
$filepath = __DIR__ . '/profile_photos/' . $filename;

if (file_exists($filepath)) {
    // Set the correct content type for JPEG/PNG/GIF
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
} else {
    http_response_code(404);
    echo 'File not found';
    exit;
}
