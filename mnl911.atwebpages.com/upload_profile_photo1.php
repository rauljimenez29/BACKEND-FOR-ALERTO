<?php
file_put_contents(__DIR__ . '/photo_upload_debug.log', date('c') . "\n" . print_r($_SERVER, true) . print_r($_POST, true) . print_r($_FILES, true), FILE_APPEND);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$conn = new mysqli($host, $user, $password, $dbname);

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

    $stmt = $conn->prepare("UPDATE normalusers SET photo_url = ? WHERE nuser_id = ?");
    $stmt->bind_param("si", $photo_url, $nuser_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "photo_url" => $photo_url]);
    } else {
        echo json_encode(["success" => false, "message" => "DB update failed"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "File upload failed"]);
}

$conn->close();
?>