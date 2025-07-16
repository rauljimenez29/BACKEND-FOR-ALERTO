<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

// Database credentials
$dsn = "host=aws-0-ap-southeast-1.pooler.supabase.com port=5432 dbname=postgres user=postgres.uyqspojnegjmxnedbtph password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "❌ Connection Failed: " . pg_last_error($conn)]);
    exit();
}

// Required fields
$required = ['f_name', 'l_name', 'm_number', 'email', 'password', 'security_question', 'security_answer'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(["success" => false, "message" => "$field is required"]);
        exit();
    }
}

$f_name = $_POST['f_name'];
$l_name = $_POST['l_name'];
$m_number = $_POST['m_number'];
$email = $_POST['email'];
$password_input = $_POST['password'];
$security_question = $_POST['security_question'];
$security_answer_input = $_POST['security_answer'];

// --- VALIDATION ---
if (!preg_match('/^[0-9]{11}$/', $m_number)) {
    echo json_encode(["success" => false, "message" => "Phone number must be exactly 11 digits."]);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit();
}

// Hash sensitive fields
$hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
$hashed_answer = password_hash($security_answer_input, PASSWORD_DEFAULT);

// Check if email already exists
$check = pg_prepare($conn, "check_email", "SELECT nuser_id FROM normalusers WHERE email = $1");
if (!$check) {
    echo json_encode(["success" => false, "message" => "Database error: " . pg_last_error($conn)]);
    pg_close($conn);
    exit();
}
$check_result = pg_execute($conn, "check_email", [$email]);
if (!$check_result) {
    echo json_encode(["success" => false, "message" => "Database error: " . pg_last_error($conn)]);
    pg_close($conn);
    exit();
}
if (pg_num_rows($check_result) > 0) {
    echo json_encode(["success" => false, "message" => "Email already registered"]);
    pg_free_result($check_result);
    pg_close($conn);
    exit();
}
pg_free_result($check_result);

// Insert user and return the new ID
$sql = "INSERT INTO normalusers (f_name, l_name, m_number, email, password, security_question, security_answer)
        VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING nuser_id";
$stmt = pg_prepare($conn, "insert_user", $sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error: " . pg_last_error($conn)]);
    pg_close($conn);
    exit();
}
$stmt_result = pg_execute($conn, "insert_user", [$f_name, $l_name, $m_number, $email, $hashed_password, $security_question, $hashed_answer]);

if ($stmt_result && pg_num_rows($stmt_result) > 0) {
    $nuser_id = pg_fetch_result($stmt_result, 0, 0);
    echo json_encode([
        "success" => true,
        "nuser_id" => $nuser_id,
        "user_type" => "regular",
        "first_name" => $f_name,
        "last_name" => $l_name,
        "email" => $email,
        "phone" => $m_number,
        "message" => "Signup successful"
    ]);
    pg_free_result($stmt_result);
} else {
    echo json_encode(["success" => false, "message" => "Signup failed: " . pg_last_error($conn)]);
}

pg_close($conn);
?>