<?php
// --- Standard Headers ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// --- Database Credentials ---
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

// --- Define the directory to save audio files ---
// IMPORTANT: Make sure this 'audio_uploads' directory exists and is writable on your server.
$upload_dir = 'audio_uploads/';

// --- Get the alert_id and the uploaded file ---
$alert_id = isset($_POST['alert_id']) ? $_POST['alert_id'] : null;
$audio_file = isset($_FILES['audioFile']) ? $_FILES['audioFile'] : null;

if (!$alert_id || !$audio_file) {
    echo json_encode(["success" => false, "message" => "Missing required fields: alert_id and audioFile."]);
    exit();
}

// Check for upload errors
if ($audio_file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "File upload error: " . $audio_file['error']]);
    exit();
}

// --- Create a unique filename to prevent overwrites ---
$file_extension = pathinfo($audio_file['name'], PATHINFO_EXTENSION);
$new_filename = 'sos_audio_' . $alert_id . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// --- Move the temporary file to the permanent uploads directory ---
if (move_uploaded_file($audio_file['tmp_name'], $upload_path)) {

    // --- Connect to the database ---
    $dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
    $conn = pg_connect($dsn);
    if (!$conn) {
        echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
        exit();
    }

    // --- Update the sosalert record with the audio file path ---
    $sql_update = "UPDATE sosalert SET a_audio = $1 WHERE alert_id = $2";
    $params = array($upload_path, $alert_id);
    $result = pg_prepare($conn, "update_sosalert", $sql_update);
    if (!$result) {
        echo json_encode(["success" => false, "message" => "Failed to prepare update statement: " . pg_last_error()]);
        exit();
    }
    $result = pg_execute($conn, "update_sosalert", $params);
    if ($result) {
        echo json_encode(["success" => true, "message" => "Audio uploaded and linked successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database update failed: " . pg_last_error()]);
    }

    pg_close($conn);

} else {
    echo json_encode(["success" => false, "message" => "Failed to save uploaded file."]);
}
?>