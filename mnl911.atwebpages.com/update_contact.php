<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_id = $_POST['contact_id'];
    $contact_name = $_POST['contact_name'];
    $contact_number = $_POST['contact_number'];
    $relationship = $_POST['relationship'];

    $stmt = pg_prepare($conn, "UPDATE usercontacts SET contact_name = $1, contact_number = $2, relationship = $3 WHERE contact_id = $4", array($contact_name, $contact_number, $relationship, $contact_id));
    $result = pg_execute($conn, "UPDATE usercontacts SET contact_name = $1, contact_number = $2, relationship = $3 WHERE contact_id = $4", array($contact_name, $contact_number, $relationship, $contact_id));

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update contact']);
    }
    pg_free_result($result);
} else {
    echo json_encode(['success' => false, 'message' => 'POST request required']);
}
pg_close($conn);
?>