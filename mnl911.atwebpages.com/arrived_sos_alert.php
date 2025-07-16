<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alert_id = $_POST['alert_id'] ?? null;
    if (!$alert_id) {
        echo json_encode(['success' => false, 'error' => 'Missing alert_id']);
        exit();
    }

    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE sosalert SET a_status = 'arrived' WHERE alert_id = ?");
    $stmt->bind_param("i", $alert_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Alert status updated to arrived']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update alert status']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => 'POST request required']);
}
?>
