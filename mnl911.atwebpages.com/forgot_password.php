<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$step = $_POST['step'] ?? '';
$email = $_POST['email'] ?? '';
$user_type = $_POST['user_type'] ?? '';

if ($step == '1') {
    if (empty($email)) {
        echo json_encode(["success" => false, "message" => "Email is required"]);
        exit();
    }

    $stmt = $conn->prepare("SELECT security_question, 'regular' as user_type FROM normalusers WHERE email = ?
                            UNION
                            SELECT security_question, 'police' as user_type FROM policeusers WHERE email = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        echo json_encode([
            "success" => true,
            "question" => $user['security_question'],
            "user_type" => $user['user_type']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Email not found"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

if ($step == '2') {
    $answer = $_POST['answer'] ?? '';

    if (!$email || !$answer || !$user_type) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit();
    }

    if ($user_type === 'regular') {
        $stmt = $conn->prepare("SELECT security_answer FROM normalusers WHERE email = ?");
    } elseif ($user_type === 'police') {
        $stmt = $conn->prepare("SELECT security_answer FROM policeusers WHERE email = ?");
    } else {
        echo json_encode(["success" => false, "message" => "Invalid user type"]);
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($stored_hash);
    if ($stmt->fetch()) {
        if (password_verify($answer, $stored_hash)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect answer"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
$conn->close();
?>
