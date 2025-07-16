<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 0); // Do not display errors to the client
ini_set('log_errors', 1);     // Log errors instead

ini_set('display_errors', 1);

// --- Database Credentials ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

// Create the connection
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$alert_id = isset($_GET['alert_id']) ? intval($_GET['alert_id']) : 0;
if (!$alert_id) {
    echo json_encode(['success' => false, 'error' => 'Missing alert_id']);
    exit;
}

// Get user location from sosalert
$userQuery = $conn->prepare("SELECT a_latitude, a_longitude, nuser_id FROM sosalert WHERE alert_id = ?");
$userQuery->bind_param("i", $alert_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();

if (!$userData) {
    echo json_encode(['success' => false, 'error' => 'Alert not found']);
    exit;
}

// Get officer_id from sosofficerassignments
$officerQuery = $conn->prepare("SELECT police_id FROM sosofficerassignments WHERE alert_id = ? AND status = 'assigned' ORDER BY assigned_at DESC LIMIT 1");
$officerQuery->bind_param("i", $alert_id);
$officerQuery->execute();
$officerResult = $officerQuery->get_result();
$officerData = $officerResult->fetch_assoc();

if (!$officerData) {
    echo json_encode(['success' => false, 'error' => 'No officer assigned']);
    exit;
}

// Get officer location from policeusers
$policeQuery = $conn->prepare("SELECT latitude, longitude FROM police_locations WHERE police_id = ? ORDER BY updated_at DESC LIMIT 1");
$policeQuery->bind_param("i", $police_id);
$policeQuery->execute();
$policeResult = $policeQuery->get_result();
$policeData = $policeResult->fetch_assoc();

if (!$policeData) {
    echo json_encode(['success' => false, 'error' => 'Officer location not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => [
        'lat' => floatval($userData['a_latitude']),
        'lng' => floatval($userData['a_longitude'])
    ],
    'officer' => [
        'lat' => floatval($policeData['latitude']),
        'lng' => floatval($policeData['longitude'])
    ]
]);
$conn->close();
?>