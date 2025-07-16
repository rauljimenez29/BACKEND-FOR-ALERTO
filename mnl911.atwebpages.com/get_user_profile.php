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
    $stmt = $conn->prepare("SELECT f_name, l_name, email, m_number, photo_url, profile_failed_attempts, profile_lockout_until FROM normalusers WHERE nuser_id = ?");
    $stmt->bind_param("i", $nuser_id);
    $stmt->execute();
    $stmt->bind_result($f_name, $l_name, $email, $m_number, $photo_url, $profile_failed_attempts, $profile_lockout_until);
    if ($stmt->fetch()) {
        echo json_encode([
            "success" => true,
            "firstName" => $f_name,
            "lastName" => $l_name,
            "email" => $email,
            "phone" => $m_number,
            "photo_url" => $photo_url,
            "profile_failed_attempts" => $profile_failed_attempts,
            "profile_lockout_until" => $profile_lockout_until
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "nuser_id required"]);
}
$conn->close();
?>