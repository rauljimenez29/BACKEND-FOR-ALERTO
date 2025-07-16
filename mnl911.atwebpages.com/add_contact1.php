<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');


$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuser_id = $_POST['nuser_id'];
    $contact_name = $_POST['contact_name'];
    $contact_number = $_POST['contact_number'];
    $relationship = $_POST['relationship'];

    $stmt = pg_prepare($conn, "INSERT INTO usercontacts (nuser_id, contact_name, contact_number, relationship) VALUES ($1, $2, $3, $4)");
    $result = pg_execute($conn, "INSERT INTO usercontacts (nuser_id, contact_name, contact_number, relationship) VALUES ($1, $2, $3, $4)", array($nuser_id, $contact_name, $contact_number, $relationship));

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add contact']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'POST request required']);
}
pg_close($conn);
?>