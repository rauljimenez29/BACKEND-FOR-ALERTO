<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Connect to the database ---
$dsn = "host=aws-0-ap-southeast-1.pooler.supabase.com port=5432 dbname=postgres user=postgres.uyqspojnegjmxnedbtph password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please contact support.']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

// --- Get data from the app ---
$alert_id = $_POST['alert_id'] ?? null;
$police_id = $_POST['police_id'] ?? null;

if (!$alert_id || !$police_id) {
    echo json_encode(["success" => false, "message" => "Alert ID and Police ID are required."]);
    exit();
}

// --- Step 1: Insert into the assignment table ---
$sql_assign = "INSERT INTO sosofficerassignments (alert_id, police_id, assigned_at, status) VALUES ($1, $2, NOW(), 'assigned')";
$result_assign = pg_query_params($conn, $sql_assign, [$alert_id, $police_id]);

if ($result_assign) {
    // --- Step 2: If assignment is successful, update the main alert's status ---
    $sql_update = "UPDATE sosalert SET a_status = 'active' WHERE alert_id = $1";
    pg_query_params($conn, $sql_update, [$alert_id]);
    echo json_encode(["success" => true, "message" => "Alert assigned successfully."]);
} else {
    $error = pg_last_error($conn);
    if (strpos($error, 'duplicate key') !== false) {
        echo json_encode(["success" => false, "message" => "This alert has already been assigned to you or another officer."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error assigning alert: " . $error]);
    }
}
pg_close($conn);
?>