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

$data = json_decode(file_get_contents('php://input'), true);
file_put_contents('php://stderr', print_r($data, true));
if (!isset($data['police_id'], $data['alert_id'], $data['latitude'], $data['longitude'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}
$police_id = intval($data['police_id']);
$alert_id = intval($data['alert_id']);
$lat = floatval($data['latitude']);
$lng = floatval($data['longitude']);

// Upsert location (insert or update if exists for this police_id + alert_id)
$stmt = pg_prepare($conn, "upsert_location", "INSERT INTO police_locations (police_id, alert_id, latitude, longitude, updated_at)
    VALUES ($1, $2, $3, $4, NOW())
    ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), updated_at = NOW()");
$result = pg_execute($conn, "upsert_location", [$police_id, $alert_id, $lat, $lng]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => pg_last_error($conn)]);
}
pg_close($conn);
