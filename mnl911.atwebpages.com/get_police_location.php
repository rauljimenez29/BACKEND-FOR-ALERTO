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

$police_id = isset($_GET['police_id']) ? intval($_GET['police_id']) : 0;
$alert_id = isset($_GET['alert_id']) ? intval($_GET['alert_id']) : 0;

if (!$police_id || !$alert_id) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
    exit;
}

$stmt = $conn->prepare("SELECT latitude, longitude, updated_at FROM police_locations WHERE police_id = ? AND alert_id = ? ORDER BY updated_at DESC LIMIT 1");
$stmt->bind_param('ii', $police_id, $alert_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'location' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'Location not found.']);
}
$stmt->close();
$conn->close();
