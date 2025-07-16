<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Count emergencies responded (assignments with non-null resolved_at)
$sql1 = "SELECT COUNT(*) as emergency_responded FROM sosofficerassignments WHERE police_id = $1 AND resolved_at IS NOT NULL";
$stmt1 = pg_prepare($conn, "count_emergencies", $sql1);
$params1 = array($police_id);
pg_execute($conn, "count_emergencies", $params1);
$res1 = pg_get_result($conn);
$row1 = pg_fetch_assoc($res1);
$emergency_responded = $row1['emergency_responded'] ?? 0;

// Calculate average response time in minutes
$sql2 = "SELECT AVG(TIMESTAMPDIFF(MINUTE, assigned_at, resolved_at)) as avg_time_response FROM sosofficerassignments WHERE police_id = $1 AND resolved_at IS NOT NULL";
$stmt2 = pg_prepare($conn, "avg_response_time", $sql2);
$params2 = array($police_id);
pg_execute($conn, "avg_response_time", $params2);
$res2 = pg_get_result($conn);
$row2 = pg_fetch_assoc($res2);
$avg_time_response = $row2['avg_time_response'] !== null ? round($row2['avg_time_response'], 2) : 0;

echo json_encode([
    'success' => true,
    'emergency_responded' => intval($emergency_responded),
    'avg_time_response' => $avg_time_response
]);

pg_free_result($res1);
pg_free_result($res2);
pg_close($conn);
?>