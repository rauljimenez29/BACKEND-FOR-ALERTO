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

$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
    exit();
}

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
            $stmt = pg_prepare($conn, "UPDATE normaluser SET photo_url = $1 WHERE nuser_id = $2", array($photo_url, $nuser_id));
            $result = pg_execute($conn, "UPDATE normaluser SET photo_url = $1 WHERE nuser_id = $2", array($photo_url, $nuser_id));
            if ($result) {
                $response['success'] = true;
                $response['photo_url'] = $photo_url;
            } else {
                $response['message'] = "Failed to update photo_url in DB.";
                file_put_contents('debug_upload.txt', "PG update failed: " . pg_last_error() . "\n", FILE_APPEND);
            }
            pg_free_result($result);
            pg_close($stmt);
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
pg_close($conn);
?>
