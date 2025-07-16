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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_id = $_POST['contact_id'];

    $stmt = pg_prepare($conn, "DELETE FROM usercontacts WHERE contact_id = $1", array($contact_id));
    $result = pg_execute($conn, "DELETE FROM usercontacts WHERE contact_id = $1", array($contact_id));

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete contact']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'POST request required']);
}
pg_close($conn);
?>