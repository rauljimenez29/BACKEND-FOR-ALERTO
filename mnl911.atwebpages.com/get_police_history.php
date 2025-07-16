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
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$response = ['success' => false];

if (isset($_GET['police_id'])) {
    $police_id = $_GET['police_id'];

    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        $response['error'] = "Connection Failed: " . $conn->connect_error;
        echo json_encode($response);
        exit();
    }
    
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
            WHERE ph.police_id = ?
            ORDER BY ph.response_time DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $police_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $history = [];
        while($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $response['success'] = true;
        $response['history'] = $history;
    } else {
        $response['error'] = "Query execution failed: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
} else {
    $response['error'] = "Required parameter 'police_id' is missing.";
}

echo json_encode($response);
?>
