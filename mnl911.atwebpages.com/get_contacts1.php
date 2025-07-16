<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";
$conn = new mysqli($host, $user, $password, $dbname);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $nuser_id = $_GET['nuser_id'];
    $stmt = $conn->prepare("SELECT contact_id, contact_name, contact_number, relationship FROM usercontacts WHERE nuser_id = ?");
    $stmt->bind_param("i", $nuser_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }
    echo json_encode(['success' => true, 'contacts' => $contacts]);
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'GET request required']);
}
$conn->close();
?>