<?php
// Setup headers before any output
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit();
}

// Fetch users with new status columns
$sql = "SELECT nuser_id AS id, f_name AS firstName, l_name AS lastName, m_number AS phone, email, password, security_question AS securityQuestion, security_answer AS securityAnswer, account_status, termination_reason FROM normalusers";
$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    // Mask password and answer
    $row['password'] = '••••••••';
    $row['securityAnswer'] = '••••••••';
    $users[] = $row;
}

// Send response once, with no trailing whitespace
echo json_encode(["success" => true, "users" => $users]);

$conn->close();
?>