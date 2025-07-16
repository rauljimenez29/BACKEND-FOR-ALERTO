<?php
file_put_contents(__DIR__ . '/photo_upload_debug.log', date('c') . "\n" . print_r($_SERVER, true) . print_r($_POST, true) . print_r($_FILES, true), FILE_APPEND);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

$dsn = 'postgresql://postgres.uyqspojnegjmxnedbtph:09123433140aa@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo "❌ Connection Failed: " . pg_last_error($conn);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

$nuser_id = $_POST['nuser_id'] ?? null;

if (!$nuser_id || !isset($_FILES['photo'])) {
    echo json_encode(["success" => false, "message" => "nuser_id and photo required"]);
    exit();
}

$target_dir = "profile_photos/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
$filename = "user_" . $nuser_id . "_" . time() . "." . $ext;
$target_file = $target_dir . $filename;

if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
    // Save the URL (adjust this to your actual domain/path)
    $photo_url = "http://mnl911.atwebpages.com/" . $target_file;

    $stmt = pg_prepare($conn, "UPDATE normalusers SET photo_url = $1 WHERE nuser_id = $2", array($photo_url, $nuser_id));
    $result = pg_execute($conn, "UPDATE normalusers SET photo_url = $1 WHERE nuser_id = $2", array($photo_url, $nuser_id));
    if ($result) {
        echo json_encode(["success" => true, "photo_url" => $photo_url]);
    } else {
        echo json_encode(["success" => false, "message" => "DB update failed"]);
    }
    pg_free_result($result);
} else {
    echo json_encode(["success" => false, "message" => "File upload failed"]);
}

pg_close($conn);
?>