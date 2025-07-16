<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}
$result = pg_query($conn, "SELECT police_id, f_name AS first_name, l_name AS last_name FROM policeusers");
$officers = [];
while ($row = pg_fetch_assoc($result)) {
    $officers[] = $row;
}
echo json_encode(['success' => true, 'officers' => $officers]);
pg_close($conn);
?>