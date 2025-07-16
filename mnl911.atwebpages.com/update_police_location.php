<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);


// --- Database Credentials ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";
$conn = new mysqli($host, $user, $password, $dbname);

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
$stmt = $conn->prepare("INSERT INTO police_locations (police_id, alert_id, latitude, longitude, updated_at)
    VALUES (?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), updated_at = NOW()");
$stmt->bind_param('iidd', $police_id, $alert_id, $lat, $lng);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
