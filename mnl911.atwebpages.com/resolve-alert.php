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
    echo json_encode(["success" => false, "error" => "Connection Failed: " . pg_last_error()]);
    exit();
}

// --- Get data from the app ---
$alert_id = $_POST['alert_id'] ?? null;
$police_id = $_POST['police_id'] ?? null;
$incident_type = $_POST['incident_type'] ?? null;
$severity = $_POST['severity'] ?? null;
$description = $_POST['description'] ?? null;

if (!$alert_id || !$police_id || !$incident_type || !$severity || !$description) {
    echo json_encode(["success" => false, "error" => "All fields are required."]);
    exit();
}

// --- Use a Transaction for data safety ---
pg_query($conn, "BEGIN");

try {
    // Step 1: Update the main alert status
    $stmt1 = pg_prepare($conn, "update_alert", "UPDATE sosalert SET a_status = 'resolved' WHERE alert_id = $1");
    $stmt1_params = [$alert_id];
    pg_execute($conn, "update_alert", $stmt1_params);
    pg_free_result($stmt1);

    // Step 2: Create the police history record
    $stmt2 = pg_prepare($conn, "insert_history", "INSERT INTO policehistory (alert_id, police_id, response_time, p_audio) VALUES ($1, $2, NOW(), $3)");
    $stmt2_params = [$alert_id, $police_id, "''"]; // Assuming empty audio is ''
    pg_execute($conn, "insert_history", $stmt2_params);
    pg_free_result($stmt2);

    // Step 3: Insert the crime report details
    // NOTE: This assumes your `crimereports` table has 'alert_id' and 'severity' columns.
    // You may need to add them using an SQL tool like phpMyAdmin.
    $stmt3 = pg_prepare($conn, "insert_crime_report", "INSERT INTO crimereports (alert_id, crime_type, severity, description, report_time) VALUES ($1, $2, $3, $4, NOW())");
    $stmt3_params = [$alert_id, $incident_type, $severity, $description];
    pg_execute($conn, "insert_crime_report", $stmt3_params);
    pg_free_result($stmt3);
    
    // If all queries were successful, commit the changes
    pg_query($conn, "COMMIT");
    echo json_encode(["success" => true, "message" => "Incident resolved and report filed successfully."]);

} catch (Exception $exception) {
    // If any query fails, roll back all changes
    pg_query($conn, "ROLLBACK");
    echo json_encode(["success" => false, "error" => "Database error: " . $exception->getMessage()]);
}

pg_close($conn);
?>
