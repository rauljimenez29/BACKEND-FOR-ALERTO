<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
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
$conn->begin_transaction();

try {
    // Step 1: Update the main alert status
    $stmt1 = $conn->prepare("UPDATE sosalert SET a_status = 'resolved' WHERE alert_id = ?");
    $stmt1->bind_param("i", $alert_id);
    $stmt1->execute();
    $stmt1->close();

    // Step 2: Create the police history record
    $stmt2 = $conn->prepare("INSERT INTO policehistory (alert_id, police_id, response_time, p_audio) VALUES (?, ?, NOW(), ?)");
    $empty_audio = "";
    $stmt2->bind_param("iis", $alert_id, $police_id, $empty_audio);
    $stmt2->execute();
    $stmt2->close();

    // Step 3: Insert the crime report details
    // NOTE: This assumes your `crimereports` table has 'alert_id' and 'severity' columns.
    // You may need to add them using an SQL tool like phpMyAdmin.
    $stmt3 = $conn->prepare("INSERT INTO crimereports (alert_id, crime_type, severity, description, report_time) VALUES (?, ?, ?, ?, NOW())");
    $stmt3->bind_param("isss", $alert_id, $incident_type, $severity, $description);
    $stmt3->execute();
    $stmt3->close();
    
    // If all queries were successful, commit the changes
    $conn->commit();
    echo json_encode(["success" => true, "message" => "Incident resolved and report filed successfully."]);

} catch (mysqli_sql_exception $exception) {
    // If any query fails, roll back all changes
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Database error: " . $exception->getMessage()]);
}

$conn->close();
?>
