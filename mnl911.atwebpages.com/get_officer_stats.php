<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

if (!isset($_GET['police_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing police_id']);
    exit;
}

$police_id = intval($_GET['police_id']);

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Count emergencies responded (assignments with non-null resolved_at)
$sql1 = "SELECT COUNT(*) as emergency_responded FROM sosofficerassignments WHERE police_id = ? AND resolved_at IS NOT NULL";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $police_id);
$stmt1->execute();
$res1 = $stmt1->get_result();
$row1 = $res1->fetch_assoc();
$emergency_responded = $row1['emergency_responded'] ?? 0;

// Calculate average response time in minutes
$sql2 = "SELECT AVG(TIMESTAMPDIFF(MINUTE, assigned_at, resolved_at)) as avg_time_response FROM sosofficerassignments WHERE police_id = ? AND resolved_at IS NOT NULL";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $police_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$row2 = $res2->fetch_assoc();
$avg_time_response = $row2['avg_time_response'] !== null ? round($row2['avg_time_response'], 2) : 0;

echo json_encode([
    'success' => true,
    'emergency_responded' => intval($emergency_responded),
    'avg_time_response' => $avg_time_response
]);

$stmt1->close();
$stmt2->close();
$conn->close();
?>