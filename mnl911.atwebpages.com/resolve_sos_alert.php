<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");
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

// --- Get data from the app's FormData ---
$alert_id = $_POST['alert_id'] ?? null;
$police_id = $_POST['police_id'] ?? null;
$incident_type = $_POST['incident_type'] ?? null;
$severity = $_POST['severity'] ?? null;
$description = $_POST['description'] ?? null;

if (!$alert_id || !$police_id || !$incident_type || !$severity) {
    echo json_encode(["success" => false, "error" => "Required fields are missing."]);
    exit();
}

// --- Use a Transaction for data safety ---
pg_query($conn, "BEGIN");

try {
    // Step 1: Get latitude and longitude from the original SOS alert
    $lat = null;
    $lng = null;
    $stmt_coords = pg_prepare($conn, "get_coords", "SELECT a_latitude, a_longitude FROM sosalert WHERE alert_id = $1");
    $result_coords = pg_execute($conn, "get_coords", array($alert_id));
    $row_coords = pg_fetch_array($result_coords, null, PGSQL_ASSOC);
    if ($row_coords) {
        $lat = $row_coords['a_latitude'];
        $lng = $row_coords['a_longitude'];
    }
    pg_free_result($result_coords);
    pg_close_statement($stmt_coords);

    if ($lat === null || $lng === null) {
        throw new Exception("Could not find coordinates for the original alert.");
    }

    // Step 2: Get the type_id from the crimetypes table based on the severity string
    $type_id = null;
    $stmt_type = pg_prepare($conn, "get_type_id", "SELECT type_id FROM crimetypes WHERE severity = $1");
    $result_type = pg_execute($conn, "get_type_id", array($severity));
    $row_type = pg_fetch_array($result_type, null, PGSQL_ASSOC);
    if ($row_type) {
        $type_id = $row_type['type_id'];
    }
    pg_free_result($result_type);
    pg_close_statement($stmt_type);

    if ($type_id === null) {
        throw new Exception("Invalid severity level provided. Make sure 'Low', 'Medium', 'High' exist in the crimetypes table.");
    }

    // Step 3: Insert the crime report details using a default zone_id
    $default_zone_id = 1; // This provides the required zone_id
    // Note: 'longtitude' is a typo in your database schema, so we match it here.
    $stmt_report = pg_prepare($conn, "insert_report", "INSERT INTO crimereports (alert_id, zone_id, type_id, description, latitude, longtitude, report_time, crime_type) VALUES ($1, $2, $3, $4, $5, $6, NOW(), $7)");
    $result_report = pg_execute($conn, "insert_report", array($alert_id, $default_zone_id, $type_id, $description, $lat, $lng, $incident_type));
    pg_free_result($result_report);
    pg_close_statement($stmt_report);
    
    // Step 4: Update the main alert status in `sosalert`
    $stmt_alert = pg_prepare($conn, "update_alert", "UPDATE sosalert SET a_status = 'resolved' WHERE alert_id = $1");
    $result_alert = pg_execute($conn, "update_alert", array($alert_id));
    pg_free_result($result_alert);
    pg_close_statement($stmt_alert);

    // Step 5: Create the police history record
    $stmt_history = pg_prepare($conn, "insert_history", "INSERT INTO policehistory (alert_id, police_id, response_time, p_audio) VALUES ($1, $2, NOW(), $3)");
    $empty_audio = ""; // Assuming no audio for now
    $result_history = pg_execute($conn, "insert_history", array($alert_id, $police_id, $empty_audio));
    pg_free_result($result_history);
    pg_close_statement($stmt_history);

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
