<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$dsn = "host=aws-0-ap-southeast-1.pooler.supabase.com port=5432 dbname=postgres user=postgres.uyqspojnegjmxnedbtph password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please contact support.']);
    exit();
}

// --- Response Object ---
$response = ['success' => false];

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['error'] = "GET request required.";
    echo json_encode($response);
    exit();
}

// --- Get data from the URL query ---
$alert_id = $_GET['alert_id'] ?? null;

if (!$alert_id) {
    $response['error'] = "Alert ID is required.";
    echo json_encode($response);
    exit();
}

// --- Connect to the database ---
$dsn = 'postgresql://postgres.uyqspojnegjmxnedbtph:09123433140aa@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    $response['error'] = "Connection Failed: " . pg_last_error($conn);
    echo json_encode($response);
    exit();
}

// --- SQL Query to fetch alert and user details ---
$sql = "SELECT 
            sa.a_address, 
            sa.a_created,
            sa.a_latitude,
            sa.a_longitude,
            nu.f_name, 
            nu.l_name, 
            nu.m_number 
        FROM sosalert sa
        JOIN normalusers nu ON sa.nuser_id = nu.nuser_id
        WHERE sa.alert_id = $1";

$stmt = pg_prepare($conn, "fetch_alert", $sql);
$result = pg_execute($conn, "fetch_alert", array($alert_id));

if ($result) {
    if (pg_num_rows($result) > 0) {
        $response['success'] = true;
        $response['details'] = pg_fetch_assoc($result);
    } else {
        $response['error'] = "No details found for alert ID " . $alert_id;
    }
} else {
    $response['error'] = "Query execution failed: " . pg_last_error($conn);
}
pg_free_result($result);
pg_close($conn);
echo json_encode($response);
?>