<?php
// Setup headers before any output
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
    exit();
}

// Fetch users with new status columns
$sql = "SELECT nuser_id AS id, f_name AS firstName, l_name AS lastName, m_number AS phone, email, password, security_question AS securityQuestion, security_answer AS securityAnswer, account_status, termination_reason FROM normalusers";
$result = pg_query($conn, $sql);

$users = [];
while ($row = pg_fetch_assoc($result)) {
    // Mask password and answer
    $row['password'] = '••••••••';
    $row['securityAnswer'] = '••••••••';
    $users[] = $row;
}

// Send response once, with no trailing whitespace
echo json_encode(["success" => true, "users" => $users]);

pg_close($conn);
?>