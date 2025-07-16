<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

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
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . $conn->connect_error]);
    exit();
}

// --- Prepare and execute the UPDATE query ---
// This updates the police user's row with their Expo push token
$sql = "UPDATE policeusers SET expoPushToken = ? WHERE police_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $token, $police_id); // "s" for string token, "i" for integer police_id

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Token stored successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Database update failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>