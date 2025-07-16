<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

$dsn = "host=aws-0-ap-southeast-1.pooler.supabase.com port=5432 dbname=postgres user=postgres.uyqspojnegjmxnedbtph password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please contact support.']);
    exit();
}
if (isset($_GET['nuser_id'])) {
    $nuser_id = $_GET['nuser_id'];
    $stmt = pg_prepare($conn, "SELECT_USER", "SELECT f_name, l_name, email, m_number, photo_url, profile_failed_attempts, profile_lockout_until FROM normalusers WHERE nuser_id = $1");
    $result = pg_execute($conn, "SELECT_USER", array($nuser_id));
    $row = pg_fetch_assoc($result);
    if ($row) {
        echo json_encode([
            "success" => true,
            "firstName" => $row['f_name'],
            "lastName" => $row['l_name'],
            "email" => $row['email'],
            "phone" => $row['m_number'],
            "photo_url" => $row['photo_url'],
            "profile_failed_attempts" => $row['profile_failed_attempts'],
            "profile_lockout_until" => $row['profile_lockout_until']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
    pg_free_result($result);
    pg_close($conn);
} else {
    echo json_encode(["success" => false, "message" => "nuser_id required"]);
}
?>