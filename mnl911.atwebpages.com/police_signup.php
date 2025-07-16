<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST request required"]);
    exit();
}

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$required = ['f_name', 'l_name', 'm_number', 'email', 'password', 'badge_number', 'station_name', 'security_question', 'security_answer'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(["success" => false, "message" => "$field is required"]);
        exit();
    }
}

$f_name = $_POST['f_name'];
$l_name = $_POST['l_name'];
$m_number = $_POST['m_number'];
$email = $_POST['email'];
$password_input = $_POST['password'];
$badge_number = $_POST['badge_number'];
$station_name = $_POST['station_name'];
$security_question = $_POST['security_question'];
$security_answer_input = $_POST['security_answer'];

// Validation
if (!preg_match('/^[0-9]{11}$/', $m_number)) {
    echo json_encode(["success" => false, "message" => "Phone number must be exactly 11 digits."]);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit();
}
if ($station_name === "Default") {
    echo json_encode(["success" => false, "message" => "Please select a valid station."]);
    exit();
}

$hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
$hashed_security_answer = password_hash($security_answer_input, PASSWORD_DEFAULT);

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

$check = $conn->prepare("SELECT police_id FROM policeusers WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already registered"]);
    $check->close();
    $conn->close();
    exit();
}
$check->close();

$sql = "INSERT INTO policeusers (f_name, l_name, m_number, email, password, badge_number, station_name, security_question, security_answer, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$default_status = 'P.Verification';
$stmt->bind_param("ssssssssss", $f_name, $l_name, $m_number, $email, $hashed_password, $badge_number, $station_name, $security_question, $hashed_security_answer, $default_status);

if ($stmt->execute()) {
    $police_id = $stmt->insert_id;
    echo json_encode([
        "success" => true,
        "police_id" => $police_id,
        "user_type" => "police",
        "message" => "Signup successful"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Signup failed"]);
}

$stmt->close();
$conn->close();
?>