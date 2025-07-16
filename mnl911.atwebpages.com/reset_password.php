<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Validate fields
$email = $_POST['email'] ?? '';
$rawPassword = $_POST['password'] ?? '';
$user_type = $_POST['user_type'] ?? '';

if (!$email || !$rawPassword || !$user_type) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    pg_close($conn);
    exit();
}

$hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

// Decide table
$table = $user_type === 'police' ? 'policeusers' : 'normalusers';

$stmt = pg_prepare($conn, "update_password", "UPDATE `$table` SET `password` = $1 WHERE `email` = $2");
$result = pg_execute($conn, "update_password", [$hashedPassword, $email]);

if ($result && pg_affected_rows($result) > 0) {
    echo json_encode(["success" => true, "message" => "Password updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update password"]);
}

pg_close($conn);
?>
