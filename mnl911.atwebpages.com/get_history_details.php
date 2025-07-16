<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$response = ['success' => false];

if (isset($_GET['history_id'])) {
    $history_id = $_GET['history_id'];

    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        $response['error'] = "Connection Failed: " . $conn->connect_error;
        echo json_encode($response);
        exit();
    }
    
    // This query now correctly selects `a_audio` from the `sosalert` table
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
            WHERE ph.history_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $history_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['success'] = true;
            $response['details'] = $result->fetch_assoc();
        } else {
             $response['error'] = "No details found for history ID " . $history_id;
        }
    } else {
        $response['error'] = "Query execution failed: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
} else {
    $response['error'] = "Required parameter 'history_id' is missing.";
}

echo json_encode($response);
?>
