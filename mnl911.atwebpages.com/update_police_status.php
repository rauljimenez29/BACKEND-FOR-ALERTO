<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$police_id = isset($data['police_id']) ? $data['police_id'] : null;
$account_status = isset($data['account_status']) ? $data['account_status'] : null;
$suspension_end_date = isset($data['suspension_end_date']) ? $data['suspension_end_date'] : null;
$termination_reason = isset($data['termination_reason']) ? $data['termination_reason'] : null; // New field

if (!$police_id || !$account_status) {
    echo json_encode(["success" => false, "message" => "police_id and account_status are required"]);
    exit();
}

// Connect to database
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Prepare SQL
if ($account_status === 'P.Suspended' && $suspension_end_date) {
    $sql = "UPDATE policeusers SET account_status = ?, suspension_end_date = ?, termination_reason = NULL WHERE police_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $account_status, $suspension_end_date, $police_id);
} elseif ($account_status === 'P.Terminated') {
    $sql = "UPDATE policeusers SET account_status = ?, termination_reason = ?, suspension_end_date = NULL WHERE police_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $account_status, $termination_reason, $police_id);
} else {
    // For all other statuses (like P.Active), clear suspension and termination details
    $sql = "UPDATE policeusers SET account_status = ?, suspension_end_date = NULL, termination_reason = NULL WHERE police_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $account_status, $police_id);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Status updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update status"]);
}

$stmt->close();
$conn->close();
?>