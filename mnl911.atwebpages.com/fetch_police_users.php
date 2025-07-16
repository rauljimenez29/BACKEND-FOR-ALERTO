<?php
// Setup headers for API access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Connection (Stays the same) ---
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

// --- UPDATED SQL Query to fetch the new columns ---
$sql = "SELECT 
            police_id AS id, 
            f_name AS firstName, 
            l_name AS lastName, 
            station_name AS station,
            badge_number AS badge,
            m_number AS phone, 
            email, 
            password, 
            security_question AS securityQuestion, 
            security_answer AS securityAnswer, 
            account_status, 
            suspension_end_date 
        FROM 
            policeusers";

$result = pg_query($conn, $sql);

if (!$result) {
    echo json_encode(["success" => false, "message" => "SQL query failed: " . pg_last_error()]);
    exit();
}

$officers = [];
while ($row = pg_fetch_assoc($result)) {
    // Mask password and the real security answer for security
    $row['password'] = '••••••••';
    $row['securityAnswer'] = '••••••••'; // We still mask the answer, but now it's masking the real one
    
    $officers[] = $row;
}

// Send response using "officers" to match the React Native code
echo json_encode(["success" => true, "officers" => $officers]);

pg_close($conn);
?>