<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');
  ini_set('display_errors', 1);
  error_reporting(E_ALL);

$dsn = 'postgresql://postgres.uyqspojnegjmxnedbtph:09123433140aa@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo "❌ Connection Failed: " . pg_last_error($conn);
    exit();
}
$sql = "SELECT * FROM sosalert WHERE a_status = 'pending' ORDER BY a_created DESC";
$result = pg_query($conn, $sql);

$alerts = [];
if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $alerts[] = $row;
    }
}
echo json_encode(['success' => true, 'alerts' => $alerts]);
pg_close($conn);
?>