<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

// --- Response Object ---
$response = ['success' => false];

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['error'] = "GET request required.";
    echo json_encode($response);
    exit();
}

// --- Get data from the URL query ---
$alert_id = $_GET['alert_id'] ?? null;

if (!$alert_id) {
    $response['error'] = "Alert ID is required.";
    echo json_encode($response);
    exit();
}

// --- Connect to the database ---
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    $response['error'] = "Connection Failed: " . $conn->connect_error;
    echo json_encode($response);
    exit();
}

// --- SQL Query to fetch alert and user details ---
$sql = "SELECT 
            sa.a_address, 
            sa.a_created,
            sa.a_latitude,
            sa.a_longitude,
            nu.f_name, 
            nu.l_name, 
            nu.m_number 
        FROM sosalert sa
        JOIN normalusers nu ON sa.nuser_id = nu.nuser_id
        WHERE sa.alert_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alert_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['success'] = true;
        $response['data'] = $result->fetch_assoc();
    } else {
        $response['error'] = "Alert with ID $alert_id not found.";
    }
} else {
    $response['error'] = "Query execution failed: " . $stmt->error;
}

// --- Close connections and send response ---
$stmt->close();
$conn->close();

echo json_encode($response);
?>