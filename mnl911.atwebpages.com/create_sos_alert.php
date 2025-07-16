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

$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

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
        $stmt = pg_prepare($conn, "INSERT INTO sosalert (nuser_id, a_created, a_latitude, a_longitude, a_address, a_status, a_audio) VALUES ($1, $2, $3, $4, $5, 'pending', $6)");
        $params = array($nuser_id, $a_created, $a_latitude, $a_longitude, $a_address, $a_audio);
        $result = pg_execute($conn, "INSERT INTO sosalert (nuser_id, a_created, a_latitude, a_longitude, a_address, a_status, a_audio) VALUES ($1, $2, $3, $4, $5, 'pending', $6)", $params);

        if ($result) {
            $row = pg_fetch_assoc($result);
            echo json_encode([
                'success' => true,
                'alert_id' => $row['id'],
                'debug_received_lat' => $a_latitude,
                'debug_received_lng' => $a_longitude
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => pg_last_error($conn)]);
        }
        pg_free_result($result);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
pg_close($conn);
?>