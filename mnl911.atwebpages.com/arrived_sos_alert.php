<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dsn = "host=aws-0-ap-southeast-1.pooler.supabase.com port=5432 dbname=postgres user=postgres.uyqspojnegjmxnedbtph password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please contact support.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alert_id = $_POST['alert_id'] ?? null;
    if (!$alert_id) {
        echo json_encode(['success' => false, 'error' => 'Missing alert_id']);
        exit();
    }

    $stmt = pg_prepare($conn, "UPDATE sosalert SET a_status = 'arrived' WHERE alert_id = $1");
    $result = pg_execute($conn, "UPDATE sosalert SET a_status = 'arrived' WHERE alert_id = $1", array($alert_id));

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Alert status updated to arrived']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update alert status']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'POST request required']);
}
?>
