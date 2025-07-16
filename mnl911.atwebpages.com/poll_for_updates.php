<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

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

// --- Connect to the database ---
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    $response['error'] = "Connection Failed: " . $conn->connect_error;
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
    $result_alerts = $conn->query($sql_alerts);
    while($row = $result_alerts->fetch_assoc()) {
        $response['data']['new_alerts'][] = $row;
    }

    // --- Query 2: Fetch unread notifications for the specific officer ---
    $sql_notifications = "SELECT id, title, description, created_at FROM notifications WHERE police_id = ? AND is_read = 0 ORDER BY created_at DESC";
    $stmt_notifications = $conn->prepare($sql_notifications);
    $stmt_notifications->bind_param("i", $police_id);
    $stmt_notifications->execute();
    $result_notifications = $stmt_notifications->get_result();
    while($row = $result_notifications->fetch_assoc()) {
        $response['data']['notifications'][] = $row;
    }
    $stmt_notifications->close();
    
    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = 'Database query failed: ' . $e->getMessage();
}

// --- Close connection and send response ---
$conn->close();
echo json_encode($response);

?>
