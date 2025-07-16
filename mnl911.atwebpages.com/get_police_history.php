<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Set Timezone ---


// --- Database Credentials ---
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

$response = ['success' => false];

if (isset($_GET['police_id'])) {
    $police_id = $_GET['police_id'];

    // Set timezone for this specific database connection
   

    // --- THIS IS THE CORRECTED QUERY ---
    // It now JOINS three tables to get all the required information
    $sql = "SELECT
                ph.history_id,
                ph.alert_id,
                sa.a_address AS location, -- Gets location from the 'sosalert' table
                nu.f_name,               -- Gets first name from the 'normalusers' table
                nu.l_name,               -- Gets last name from the 'normalusers' table
                ph.response_time AS resolved_at -- Gets the timestamp from 'policehistory'
            FROM policehistory ph
            JOIN sosalert sa ON ph.alert_id = sa.alert_id
            JOIN normalusers nu ON sa.nuser_id = nu.nuser_id
            WHERE ph.police_id = $1
            ORDER BY ph.response_time DESC";

    $stmt = pg_prepare($conn, "get_police_history", $sql);
    $result = pg_execute($conn, "get_police_history", array($police_id));
    
    if ($result) {
        $history = [];
        while($row = pg_fetch_assoc($result)) {
            $history[] = $row;
        }
        $response['success'] = true;
        $response['history'] = $history;
    } else {
        $response['error'] = "Query execution failed: " . pg_last_error();
    }
    pg_free_result($result);
    pg_close($conn);
} else {
    $response['error'] = "Required parameter 'police_id' is missing.";
}

echo json_encode($response);
?>
