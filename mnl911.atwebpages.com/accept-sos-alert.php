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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

// --- Get data from the app ---
$alert_id = $_POST['alert_id'] ?? null;
$police_id = $_POST['police_id'] ?? null;

if (!$alert_id || !$police_id) {
    echo json_encode(["success" => false, "message" => "Alert ID and Police ID are required."]);
    exit();
}

// --- Connect to the database ---
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . $conn->connect_error]);
    exit();
}


// --- Step 1: Insert into the assignment table ---
// The status 'assigned' is from your table schema
$sql_assign = "INSERT INTO sosofficerassignments (alert_id, police_id, assigned_at, status) VALUES (?, ?, NOW(), 'assigned')";
$stmt_assign = $conn->prepare($sql_assign);
$stmt_assign->bind_param("ii", $alert_id, $police_id); // "i" for integer

if ($stmt_assign->execute()) {
    // --- Step 2: If assignment is successful, update the main alert's status ---
    $sql_update = "UPDATE sosalert SET a_status = 'active' WHERE alert_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $alert_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    echo json_encode(["success" => true, "message" => "Alert assigned successfully."]);
} else {
    // Check for a duplicate key error (MySQL error code 1062)
    // This happens if the officer tries to accept an alert they've already accepted
    if ($conn->errno == 1062) {
        echo json_encode(["success" => false, "message" => "This alert has already been assigned to you or another officer."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error assigning alert: " . $stmt_assign->error]);
    }
}

$stmt_assign->close();
$conn->close();
?>