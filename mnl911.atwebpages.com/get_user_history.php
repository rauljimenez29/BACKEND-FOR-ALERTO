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

if (isset($_GET['nuser_id'])) {
    $nuser_id = $_GET['nuser_id'];
    
    // Modified query to fetch directly from sosalert table
    $sql = "SELECT 
                sa.alert_id,
                sa.alert_id as history_id,
                sa.a_address AS location,
                nu.f_name AS victim_fname,
                nu.l_name AS victim_lname,
                ph.response_time AS resolved_at,
                sa.a_created AS trigger_time
            FROM sosalert sa
            JOIN normalusers nu ON sa.nuser_id = nu.nuser_id
            LEFT JOIN policehistory ph ON sa.alert_id = ph.alert_id
            WHERE sa.nuser_id = ?
            ORDER BY sa.alert_id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $nuser_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = array();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "history" => $history
    ]);
    
    $stmt->close();
} else {
    echo json_encode([
        "success" => false, 
        "message" => "nuser_id required"
    ]);
}
$conn->close();
?>