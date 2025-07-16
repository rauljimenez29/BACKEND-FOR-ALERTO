<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
    exit();
}

// --- Main Response Object ---
$response = [
    'success' => false,
    'data' => [
        'new_alerts' => [],
        'notifications' => []
    ]
];

// Get police_id from the request
$input = json_decode(file_get_contents('php://input'), true);
$police_id = $input['police_id'] ?? null;

if (!$police_id) {
    $response['error'] = 'Police ID is required.';
    echo json_encode($response);
    exit();
}

try {
    // --- Query 1: Fetch unassigned SOS alerts ---
    // This query now joins with normalusers to get the victim's name
    $sql_alerts = "SELECT 
                    sa.alert_id, 
                    sa.a_address, 
                    sa.a_created, 
                    CONCAT(nu.f_name, ' ', nu.l_name) AS user_full_name
                   FROM sosalert sa
                   JOIN normalusers nu ON sa.nuser_id = nu.nuser_id
                   WHERE sa.a_status = 'pending' 
                   ORDER BY sa.a_created DESC";
    $result_alerts = pg_query($conn, $sql_alerts);
    while($row = pg_fetch_assoc($result_alerts)) {
        $response['data']['new_alerts'][] = $row;
    }

    // --- Query 2: Fetch unread notifications for the specific officer ---
    $sql_notifications = "SELECT id, title, description, created_at FROM notifications WHERE police_id = $1 AND is_read = 0 ORDER BY created_at DESC";
    $result_notifications = pg_query_params($conn, $sql_notifications, [$police_id]);
    while($row = pg_fetch_assoc($result_notifications)) {
        $response['data']['notifications'][] = $row;
    }
    
    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = 'Database query failed: ' . $e->getMessage();
}

// --- Close connection and send response ---
pg_close($conn);
echo json_encode($response);

?>
