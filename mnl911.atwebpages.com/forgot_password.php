<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
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

    $stmt = pg_prepare($conn, "SELECT security_question, 'regular' as user_type FROM normalusers WHERE email = $1
                            UNION
                            SELECT security_question, 'police' as user_type FROM policeusers WHERE email = $1");
    $result = pg_execute($conn, "SELECT security_question, 'regular' as user_type FROM normalusers WHERE email = $1
                            UNION
                            SELECT security_question, 'police' as user_type FROM policeusers WHERE email = $1", array($email));
    $user = pg_fetch_assoc($result);

    if ($user) {
        echo json_encode([
            "success" => true,
            "question" => $user['security_question'],
            "user_type" => $user['user_type']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Email not found"]);
    }

    pg_free_result($result);
    pg_close($conn);
    exit();
}

if ($step == '2') {
    $answer = $_POST['answer'] ?? '';

    if (!$email || !$answer || !$user_type) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit();
    }

    if ($user_type === 'regular') {
        $stmt = pg_prepare($conn, "SELECT security_answer FROM normalusers WHERE email = $1");
    } elseif ($user_type === 'police') {
        $stmt = pg_prepare($conn, "SELECT security_answer FROM policeusers WHERE email = $1");
    } else {
        echo json_encode(["success" => false, "message" => "Invalid user type"]);
        exit();
    }

    $result = pg_execute($conn, $stmt, array($email));
    $stored_hash = pg_fetch_result($result, 0, 0);

    if ($stored_hash) {
        if (password_verify($answer, $stored_hash)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect answer"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }

    pg_free_result($result);
    pg_close($conn);
    exit();
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
pg_close($conn);
?>
