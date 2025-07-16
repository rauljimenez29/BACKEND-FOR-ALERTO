<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug log
file_put_contents('debug_upload.txt', "FILES: " . print_r($_FILES, true) . "\nPOST: " . print_r($_POST, true) . "\n", FILE_APPEND);

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";
$conn = new mysqli($host, $user, $password, $dbname);

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['photo']) && isset($_POST['nuser_id'])) {
        $nuser_id = $_POST['nuser_id'];
        $file = $_FILES['photo'];

        // Check file extension
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $response['message'] = "Invalid file type.";
            echo json_encode($response);
            exit;
        }

        // Define upload path using full file system path
        $uploadFolder = $_SERVER['DOCUMENT_ROOT'] . "/uploads/";
        if (!file_exists($uploadFolder)) {
            mkdir($uploadFolder, 0755, true);
        }

        // Filename and destination
        $filename = "user_" . $nuser_id . "_" . time() . "." . $ext;
        $destination = $uploadFolder . $filename;

        // Move the file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Public URL (AwardSpace uses http, not https on free tier)
            $photo_url = "http://mnl911.atwebpages.com/uploads/" . $filename;

            // Update DB
            $stmt = $conn->prepare("UPDATE normaluser SET photo_url = ? WHERE nuser_id = ?");
            $stmt->bind_param("si", $photo_url, $nuser_id);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['photo_url'] = $photo_url;
        } else {
            $response['message'] = "Failed to move uploaded file.";
            file_put_contents('debug_upload.txt', "Move failed: " . print_r(error_get_last(), true) . "\n", FILE_APPEND);
        }
    } else {
        $response['message'] = "Missing file or user ID.";
    }
} else {
    $response['message'] = "POST request required.";
}

echo json_encode($response);
$conn->close();
?>
