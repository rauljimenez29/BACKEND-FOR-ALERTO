<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

$response = ['success' => false];

if (isset($_GET['history_id'])) {
    $history_id = $_GET['history_id'];

    $sql = "SELECT
                ph.history_id,
                ph.response_time AS resolved_at,
                sa.a_audio AS voice_record_url, -- <-- CORRECTED: Fetches from sosalert table
                sa.alert_id,
                sa.a_address AS location,
                sa.a_created AS triggered_at,
                nu.f_name AS victim_fname,
                nu.l_name AS victim_lname,
                nu.m_number AS victim_number,
                nu.email AS victim_email,
                pu.f_name AS officer_fname,
                pu.l_name AS officer_lname,
                cr.crime_type,
                cr.description AS crime_description,
                ct.severity
            FROM policehistory ph
            JOIN sosalert sa ON ph.alert_id = sa.alert_id
            JOIN normalusers nu ON sa.nuser_id = nu.nuser_id
            JOIN policeusers pu ON ph.police_id = pu.police_id
            LEFT JOIN crimereports cr ON sa.alert_id = cr.alert_id
            LEFT JOIN crimetypes ct ON cr.type_id = ct.type_id
            WHERE ph.history_id = $1";

    $stmt = pg_prepare($conn, "get_history_details", $sql);
    $result = pg_execute($conn, "get_history_details", array($history_id));
    
    if ($result) {
        if (pg_num_rows($result) > 0) {
            $response['success'] = true;
            $response['details'] = pg_fetch_assoc($result);
        } else {
             $response['error'] = "No details found for history ID " . $history_id;
        }
    } else {
        $response['error'] = "Query execution failed: " . pg_last_error();
    }
    pg_free_result($result);
    pg_close($conn);
} else {
    $response['error'] = "Required parameter 'history_id' is missing.";
}

echo json_encode($response);
?>
