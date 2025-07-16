<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');
  ini_set('display_errors', 1);
  error_reporting(E_ALL);

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";
$conn = new mysqli($host, $user, $password, $dbname);

$sql = "SELECT * FROM sosalert WHERE a_status = 'pending' ORDER BY a_created DESC";
$result = $conn->query($sql);

$alerts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
}
echo json_encode(['success' => true, 'alerts' => $alerts]);
$conn->close();
?>