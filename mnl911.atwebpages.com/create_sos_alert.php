<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";
$conn = new mysqli($host, $user, $password, $dbname);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept both form data and JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $nuser_id = $_POST['nuser_id'] ?? $input['nuser_id'] ?? null;
    $a_latitude = $_POST['a_latitude'] ?? $input['a_latitude'] ?? null;
    $a_longitude = $_POST['a_longitude'] ?? $input['a_longitude'] ?? null;
    $a_address = $_POST['a_address'] ?? $input['a_address'] ?? null;
    $a_audio = $_POST['a_audio'] ?? $input['a_audio'] ?? '';
    $a_created = date('Y-m-d H:i:s');

    if ($nuser_id && $a_latitude && $a_longitude) {
        $stmt = $conn->prepare("INSERT INTO sosalert (nuser_id, a_created, a_latitude, a_longitude, a_address, a_status, a_audio) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param('isddss', $nuser_id, $a_created, $a_latitude, $a_longitude, $a_address, $a_audio);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'alert_id' => $conn->insert_id,
                'debug_received_lat' => $a_latitude,
                'debug_received_lng' => $a_longitude
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
$conn->close();
?>