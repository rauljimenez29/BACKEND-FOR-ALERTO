<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// --- Connect to the database ---
$dsn = 'host=aws-0-ap-southeast-1.pooler.supabase.com port=5432 dbname=postgres user=postgres.uyqspojnegjmxnedbtph password=09123433140aa sslmode=require';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error($conn)]);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? $_POST['email'] ?? '';
$password = $input['password'] ?? $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    pg_close($conn);
    exit();
}

// Check normal user
$sql_normal = "SELECT nuser_id, password, failed_attempts, lockout_until, account_status, termination_reason FROM normalusers WHERE email = $1";
$stmt_normal = pg_prepare($conn, "select_normal_user", $sql_normal);
if (!$stmt_normal) {
    echo json_encode(["success" => false, "message" => "Failed to prepare statement: " . pg_last_error($conn)]);
    pg_close($conn);
    exit();
}
$result_normal = pg_execute($conn, "select_normal_user", [$email]);
if (!$result_normal) {
    echo json_encode(["success" => false, "message" => "Query failed: " . pg_last_error($conn)]);
    pg_close($conn);
    exit();
}
if (pg_num_rows($result_normal) === 1) {
    $row = pg_fetch_assoc($result_normal);
    $hashed_password = $row['password'];
    if (password_verify($password, $hashed_password)) {
        echo json_encode(["success" => true, "user_type" => "normal", "nuser_id" => $row['nuser_id']]);
        pg_close($conn);
        exit();
    } else {
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        pg_close($conn);
        exit();
    }
}

// Check police user
$sql_police = "SELECT police_id, password, failed_attempts, lockout_until, account_status FROM policeusers WHERE email = $1";
$stmt_police = pg_prepare($conn, "select_police_user", $sql_police);
if (!$stmt_police) {
    echo json_encode(["success" => false, "message" => "Failed to prepare statement: " . pg_last_error($conn)]);
    pg_close($conn);
    exit();
}
$result_police = pg_execute($conn, "select_police_user", [$email]);
if (!$result_police) {
    echo json_encode(["success" => false, "message" => "Query failed: " . pg_last_error($conn)]);
    pg_close($conn);
    exit();
}
if (pg_num_rows($result_police) === 1) {
    $row = pg_fetch_assoc($result_police);
    $hashed_password = $row['password'];
    if (password_verify($password, $hashed_password)) {
        echo json_encode(["success" => true, "user_type" => "police", "police_id" => $row['police_id']]);
        pg_close($conn);
        exit();
    } else {
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        pg_close($conn);
        exit();
    }
}

echo json_encode(["success" => false, "message" => "Invalid credentials"]);
pg_close($conn);
exit();
?>