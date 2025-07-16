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
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

$alert_id = isset($_GET['alert_id']) ? intval($_GET['alert_id']) : 0;
if (!$alert_id) {
    echo json_encode(['success' => false, 'error' => 'Missing alert_id']);
    exit;
}

// Get user location from sosalert
$userQuery = pg_prepare($conn, "get_user_location", "SELECT a_latitude, a_longitude, nuser_id FROM sosalert WHERE alert_id = $1");
pg_execute($conn, "get_user_location", [$alert_id]);
$userResult = pg_get_result($conn);
$userData = pg_fetch_assoc($userResult);

if (!$userData) {
    echo json_encode(['success' => false, 'error' => 'Alert not found']);
    exit;
}

// Get officer_id from sosofficerassignments
$officerQuery = pg_prepare($conn, "get_officer_assignment", "SELECT police_id FROM sosofficerassignments WHERE alert_id = $1 AND status = 'assigned' ORDER BY assigned_at DESC LIMIT 1");
pg_execute($conn, "get_officer_assignment", [$alert_id]);
$officerResult = pg_get_result($conn);
$officerData = pg_fetch_assoc($officerResult);

if (!$officerData) {
    echo json_encode(['success' => false, 'error' => 'No officer assigned']);
    exit;
}

// Get officer location from policeusers
$policeQuery = pg_prepare($conn, "get_police_location", "SELECT latitude, longitude FROM police_locations WHERE police_id = $1 ORDER BY updated_at DESC LIMIT 1");
pg_execute($conn, "get_police_location", [$officerData['police_id']]);
$policeResult = pg_get_result($conn);
$policeData = pg_fetch_assoc($policeResult);

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
pg_close($conn);
?>