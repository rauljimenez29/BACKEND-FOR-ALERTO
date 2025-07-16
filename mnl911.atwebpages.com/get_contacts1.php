<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $nuser_id = $_GET['nuser_id'];
    $stmt = pg_prepare($conn, "SELECT contact_id, contact_name, contact_number, relationship FROM usercontacts WHERE nuser_id = $1", [$nuser_id]);
    $result = pg_execute($conn, "SELECT contact_id, contact_name, contact_number, relationship FROM usercontacts WHERE nuser_id = $1", [$nuser_id]);
    $contacts = [];
    while ($row = pg_fetch_assoc($result)) {
        $contacts[] = $row;
    }
    echo json_encode(['success' => true, 'contacts' => $contacts]);
    pg_free_result($result);
    pg_close($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'GET request required']);
    pg_close($conn);
}
?>