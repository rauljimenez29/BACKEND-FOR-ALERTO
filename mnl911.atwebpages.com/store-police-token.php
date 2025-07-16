<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

// --- Get data from the app ---
$police_id = $_POST['police_id'] ?? null;
$token = $_POST['token'] ?? null;

if (!$police_id || !$token) {
    echo json_encode(["success" => false, "message" => "Police ID and token are required."]);
    exit();
}

// --- Connect to the database ---
$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
    exit();
}

// --- Prepare and execute the UPDATE query ---
// This updates the police user's row with their Expo push token
$sql = "UPDATE policeusers SET expoPushToken = ? WHERE police_id = ?";
$stmt = pg_prepare($conn, "update_token", $sql);
$params = [$token, $police_id];
$result = pg_execute($conn, "update_token", $params);

if ($result) {
    echo json_encode(["success" => true, "message" => "Token stored successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Database update failed: " . pg_last_error()]);
}

pg_close($conn);
?>