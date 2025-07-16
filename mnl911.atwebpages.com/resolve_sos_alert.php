<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

// --- Connect to the database ---
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Connection Failed: " . $conn->connect_error]);
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
$conn->begin_transaction();

try {
    // Step 1: Get latitude and longitude from the original SOS alert
    $lat = null;
    $lng = null;
    $stmt_coords = $conn->prepare("SELECT a_latitude, a_longitude FROM sosalert WHERE alert_id = ?");
    $stmt_coords->bind_param("i", $alert_id);
    $stmt_coords->execute();
    $stmt_coords->bind_result($lat, $lng);
    $stmt_coords->fetch();
    $stmt_coords->close();

    if ($lat === null || $lng === null) {
        throw new Exception("Could not find coordinates for the original alert.");
    }

    // Step 2: Get the type_id from the crimetypes table based on the severity string
    $type_id = null;
    $stmt_type = $conn->prepare("SELECT type_id FROM crimetypes WHERE severity = ?");
    $stmt_type->bind_param("s", $severity);
    $stmt_type->execute();
    $stmt_type->bind_result($fetched_type_id);
    if ($stmt_type->fetch()) {
        $type_id = $fetched_type_id;
    }
    $stmt_type->close();

    if ($type_id === null) {
        throw new Exception("Invalid severity level provided. Make sure 'Low', 'Medium', 'High' exist in the crimetypes table.");
    }

    // Step 3: Insert the crime report details using a default zone_id
    $default_zone_id = 1; // This provides the required zone_id
    // Note: 'longtitude' is a typo in your database schema, so we match it here.
    $stmt_report = $conn->prepare("INSERT INTO crimereports (alert_id, zone_id, type_id, description, latitude, longtitude, report_time, crime_type) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt_report->bind_param("iiisdds", $alert_id, $default_zone_id, $type_id, $description, $lat, $lng, $incident_type);
    $stmt_report->execute();
    $stmt_report->close();
    
    // Step 4: Update the main alert status in `sosalert`
    $stmt_alert = $conn->prepare("UPDATE sosalert SET a_status = 'resolved' WHERE alert_id = ?");
    $stmt_alert->bind_param("i", $alert_id);
    $stmt_alert->execute();
    $stmt_alert->close();

    // Step 5: Create the police history record
    $stmt_history = $conn->prepare("INSERT INTO policehistory (alert_id, police_id, response_time, p_audio) VALUES (?, ?, NOW(), ?)");
    $empty_audio = ""; // Assuming no audio for now
    $stmt_history->bind_param("iis", $alert_id, $police_id, $empty_audio);
    $stmt_history->execute();
    $stmt_history->close();

    // If all queries were successful, commit the changes
    $conn->commit();
    echo json_encode(["success" => true, "message" => "Incident resolved and report filed successfully."]);

} catch (Exception $exception) {
    // If any query fails, roll back all changes
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Database error: " . $exception->getMessage()]);
}

$conn->close();
?>
