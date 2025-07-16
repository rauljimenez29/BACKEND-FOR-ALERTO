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

$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

$required = ['f_name', 'l_name', 'm_number', 'email', 'password', 'badge_number', 'station_name', 'security_question', 'security_answer'];
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
$badge_number = $_POST['badge_number'];
$station_name = $_POST['station_name'];
$security_question = $_POST['security_question'];
$security_answer_input = $_POST['security_answer'];

// Validation
if (!preg_match('/^[0-9]{11}$/', $m_number)) {
    echo json_encode(["success" => false, "message" => "Phone number must be exactly 11 digits."]);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit();
}
if ($station_name === "Default") {
    echo json_encode(["success" => false, "message" => "Please select a valid station."]);
    exit();
}

$hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
$hashed_security_answer = password_hash($security_answer_input, PASSWORD_DEFAULT);

$check_query = "SELECT police_id FROM policeusers WHERE email = $1";
$check_params = [$email];
$check_result = pg_query_params($conn, $check_query, $check_params);
if ($check_result && pg_num_rows($check_result) > 0) {
    echo json_encode(["success" => false, "message" => "Email already registered"]);
    pg_free_result($check_result);
    pg_close($conn);
    exit();
}
pg_free_result($check_result);

$sql = "INSERT INTO policeusers (f_name, l_name, m_number, email, password, badge_number, station_name, security_question, security_answer, account_status) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)";
$stmt = pg_prepare($conn, "signup_stmt", $sql);
$default_status = 'P.Verification';
$params = [$f_name, $l_name, $m_number, $email, $hashed_password, $badge_number, $station_name, $security_question, $hashed_security_answer, $default_status];
$result = pg_execute($conn, "signup_stmt", $params);

if ($result) {
    $police_id = pg_fetch_result($result, 0, "police_id");
    echo json_encode([
        "success" => true,
        "police_id" => $police_id,
        "user_type" => "police",
        "message" => "Signup successful"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Signup failed"]);
}

pg_free_result($result);
pg_close($conn);
?>