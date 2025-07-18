<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$dsn = 'postgresql://postgres.uyqspojnegjmxnedbtph:09123433140aa@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo "❌ Connection Failed: " . pg_last_error($conn);
    exit();
}
// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$nuser_id = isset($data['nuser_id']) ? $data['nuser_id'] : null;
$account_status = isset($data['account_status']) ? $data['account_status'] : null;
$termination_reason = isset($data['termination_reason']) ? $data['termination_reason'] : null;

if (!$nuser_id || !$account_status) {
    echo json_encode(["success" => false, "message" => "nuser_id and account_status are required"]);
    exit();
}

// Prepare SQL
if ($account_status === 'Terminated') {
    $sql = "UPDATE normalusers SET account_status = ?, termination_reason = ? WHERE nuser_id = ?";
    $stmt = pg_prepare($conn, "update_status", $sql);
    $params = array($account_status, $termination_reason, $nuser_id);
} else {
    // For 'Active' status, clear the termination reason
    $sql = "UPDATE normalusers SET account_status = ?, termination_reason = NULL WHERE nuser_id = ?";
    $stmt = pg_prepare($conn, "update_status", $sql);
    $params = array($account_status, $nuser_id);
}

$result = pg_execute($conn, "update_status", $params);

if ($result) {
    echo json_encode(["success" => true, "message" => "Status updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update status"]);
}

pg_close($conn);
?>