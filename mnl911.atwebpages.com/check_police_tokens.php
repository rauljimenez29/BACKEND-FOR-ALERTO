<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check police users and their tokens
    $sql = "SELECT police_id, expoPushToken, f_name, l_name FROM policeusers LIMIT 50";
    $result = pg_query($conn, $sql);
    
    $police_users = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $police_users[] = [
                'police_id' => $row['police_id'],
                'name' => $row['f_name'] . ' ' . $row['l_name'],
                'expoPushToken' => $row['expoPushToken'],
                'has_token' => !empty($row['expoPushToken'])
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'total_police' => count($police_users),
        'police_with_tokens' => count(array_filter($police_users, function($p) { return $p['has_token']; })),
        'police_users' => $police_users
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'GET request required']);
}

pg_close($conn);
?> 