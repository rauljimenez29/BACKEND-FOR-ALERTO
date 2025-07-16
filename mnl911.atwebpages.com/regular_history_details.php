<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    $response['error'] = "Connection Failed: " . pg_last_error();
    echo json_encode($response);
    exit();
}

if (isset($_GET['history_id'])) {
    $history_id = $_GET['history_id'];

    // Modified query to use alert_id directly from sosalert
   $sql = "SELECT
            sa.alert_id,
            sa.a_address AS location,
            sa.a_created AS triggered_at,
            ph.response_time AS resolved_at,
            nu.f_name AS victim_fname,
            nu.l_name AS victim_lname,
            nu.m_number AS victim_number,
            nu.email AS victim_email,
            pu.f_name AS officer_fname,
            pu.l_name AS officer_lname,
            pu.m_number AS officer_number,
            pu.email AS officer_email,
            pu.station_name AS officer_station,
            pu.badge_number AS officer_badge,
            cr.crime_type,
            ct.severity,
            cr.description AS crime_description,
            sa.a_audio AS voice_record_url
        FROM sosalert sa
        JOIN normalusers nu ON sa.nuser_id = nu.nuser_id
        LEFT JOIN policehistory ph ON sa.alert_id = ph.alert_id
        LEFT JOIN policeusers pu ON ph.police_id = pu.police_id
        LEFT JOIN crimereports cr ON sa.alert_id = cr.alert_id
        LEFT JOIN crimetypes ct ON cr.type_id = ct.type_id
        WHERE sa.alert_id = ?";

    $stmt = pg_prepare($conn, "get_history_details", $sql);
    $result = pg_execute($conn, "get_history_details", array($history_id));
    
    if ($result) {
        $details = pg_fetch_assoc($result);
        if ($details) {
            $response['success'] = true;
            $response['details'] = $details;
        } else {
            $response['error'] = "No details found for alert_id " . $history_id;
        }
    } else {
        $response['error'] = "Query execution failed: " . pg_last_error();
    }
    pg_free_result($result);
    pg_close_stmt($stmt);
} else {
    $response['error'] = "Required parameter 'history_id' is missing.";
}

pg_close($conn);
echo json_encode($response);
?>