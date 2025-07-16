<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);


// --- Database Credentials ---
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

$police_id = isset($_GET['police_id']) ? intval($_GET['police_id']) : 0;
$alert_id = isset($_GET['alert_id']) ? intval($_GET['alert_id']) : 0;

if (!$police_id || !$alert_id) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
    exit;
}

$stmt = pg_prepare($conn, "SELECT_LOCATION", "SELECT latitude, longitude, updated_at FROM police_locations WHERE police_id = $1 AND alert_id = $2 ORDER BY updated_at DESC LIMIT 1");
$result = pg_execute($conn, "SELECT_LOCATION", [$police_id, $alert_id]);

if ($row = pg_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'location' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'Location not found.']);
}
pg_free_result($result);
pg_close($conn);
