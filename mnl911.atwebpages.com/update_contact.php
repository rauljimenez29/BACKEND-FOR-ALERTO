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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_id = $_POST['contact_id'];
    $contact_name = $_POST['contact_name'];
    $contact_number = $_POST['contact_number'];
    $relationship = $_POST['relationship'];

    $stmt = $conn->prepare("UPDATE usercontacts SET contact_name = ?, contact_number = ?, relationship = ? WHERE contact_id = ?");
    $stmt->bind_param("sssi", $contact_name, $contact_number, $relationship, $contact_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update contact']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'POST request required']);
}
$conn->close();
?>