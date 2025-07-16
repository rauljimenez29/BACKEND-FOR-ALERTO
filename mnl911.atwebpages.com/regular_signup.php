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

// Database credentials
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

// Required fields
$required = ['f_name', 'l_name', 'm_number', 'email', 'password', 'security_question', 'security_answer'];
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
$security_question = $_POST['security_question'];
$security_answer_input = $_POST['security_answer'];

// --- VALIDATION ---
if (!preg_match('/^[0-9]{11}$/', $m_number)) {
    echo json_encode(["success" => false, "message" => "Phone number must be exactly 11 digits."]);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit();
}

// Hash sensitive fields
$hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
$hashed_answer = password_hash($security_answer_input, PASSWORD_DEFAULT);

// Connect to database
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT nuser_id FROM normalusers WHERE email = ?");
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

// Insert user
$sql = "INSERT INTO normalusers (f_name, l_name, m_number, email, password, security_question, security_answer)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssss", $f_name, $l_name, $m_number, $email, $hashed_password, $security_question, $hashed_answer);

if ($stmt->execute()) {
    $nuser_id = $stmt->insert_id;
    echo json_encode([
        "success" => true,
        "nuser_id" => $nuser_id,
        "user_type" => "regular",
        "first_name" => $f_name,
        "last_name" => $l_name,
        "email" => $email,
        "phone" => $m_number,
        "message" => "Signup successful"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Signup failed"]);
}

$stmt->close();
$conn->close();
?>
