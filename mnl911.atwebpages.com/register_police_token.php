<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dsn = 'postgresql://postgres.uyqspojnegjmxnedbtph:09123433140aa@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo "❌ Connection Failed: " . pg_last_error($conn);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $police_id = $_POST['police_id'] ?? null;
    $expo_push_token = $_POST['expo_push_token'] ?? null;
    
    if (!$police_id || !$expo_push_token) {
        echo json_encode(['success' => false, 'error' => 'Police ID and push token are required']);
        exit();
    }
    
    // Update the police user's push token
    $sql = "UPDATE policeusers SET expoPushToken = $1 WHERE police_id = $2";
    $params = array($expo_push_token, $police_id);
    
    $result = pg_query_params($conn, $sql, $params);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Push token registered successfully',
            'police_id' => $police_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to register push token']);
    }
    
    pg_close($conn);
} else {
    echo json_encode(['success' => false, 'error' => 'POST request required']);
}
?>