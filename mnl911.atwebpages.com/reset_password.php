<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Validate fields
$email = $_POST['email'] ?? '';
$rawPassword = $_POST['password'] ?? '';
$user_type = $_POST['user_type'] ?? '';

if (!$email || !$rawPassword || !$user_type) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    $conn->close();
    exit();
}

$hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

// Decide table
$table = $user_type === 'police' ? 'policeusers' : 'normalusers';

$stmt = $conn->prepare("UPDATE `$table` SET `password` = ? WHERE `email` = ?");
$stmt->bind_param("ss", $hashedPassword, $email);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Password updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update password"]);
}

$stmt->close();
$conn->close();
?>
