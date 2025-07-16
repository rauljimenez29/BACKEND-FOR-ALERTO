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
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed"]));
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

$result = $conn->query($sql);

if (!$result) {
    die(json_encode(["success" => false, "message" => "SQL query failed: " . $conn->error]));
}

$officers = [];
while ($row = $result->fetch_assoc()) {
    // Mask password and the real security answer for security
    $row['password'] = '••••••••';
    $row['securityAnswer'] = '••••••••'; // We still mask the answer, but now it's masking the real one
    
    $officers[] = $row;
}

// Send response using "officers" to match the React Native code
echo json_encode(["success" => true, "officers" => $officers]);

$conn->close();
?>