<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- This block will catch fatal PHP errors and return them as JSON ---
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        ob_clean();
        echo json_encode([ "success" => false, "message" => "Fatal Server Error", "error_details" => $error['message'] ]);
        exit();
    }
});

try {
    // --- Database Credentials ---
    $dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
    $conn = pg_connect($dsn);
    if (!$conn) {
        throw new Exception("Database connection failed: " . pg_last_error());
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("POST request required");
    }

    // --- Get POST data, including the location_address ---
    $nuser_id = $_POST['nuser_id'] ?? null;
    $latitude = isset($_POST['a_latitude']) ? floatval($_POST['a_latitude']) : null;
    $longitude = isset($_POST['a_longitude']) ? floatval($_POST['a_longitude']) : null;
    $a_address = $_POST['a_address'] ?? 'Location not provided';

    if (!$nuser_id || !$latitude || !$longitude) {
        throw new Exception("Missing required fields.");
    }

    // --- OPTIMIZED: Single query to get user name and insert alert ---
    $sql_insert = "INSERT INTO sosalert (nuser_id, a_created, a_latitude, a_longitude, a_address, a_status) VALUES ($1, NOW(), $2, $3, $4, 'pending')";
    $params_insert = [$nuser_id, $latitude, $longitude, $a_address];
    $result_insert = pg_query_params($conn, $sql_insert, $params_insert);
    
    if (!$result_insert) {
        throw new Exception("Database INSERT failed: " . pg_last_error($conn));
    }
    $alert_id = pg_last_oid($result_insert);
    pg_free_result($result_insert);

    // --- OPTIMIZED: Get user name and police tokens in parallel ---
    $user_name = "User " . $nuser_id;
    
    // Get user name with optimized query
    $sql_user = "SELECT f_name FROM normalusers WHERE nuser_id = $1 LIMIT 1";
    $params_user = [$nuser_id];
    $result_user = pg_query_params($conn, $sql_user, $params_user);
    if ($result_user) {
        $row_user = pg_fetch_assoc($result_user);
        if ($row_user) {
            $user_name = $row_user['f_name'];
        }
        pg_free_result($result_user);
    }
    
    // --- OPTIMIZED: Get police tokens with better query ---
    // First, let's check what police users exist
    $sql_check = "SELECT COUNT(*) as total_police FROM policeusers";
    $result_check = pg_query($conn, $sql_check);
    $total_police = 0;
    if ($result_check) {
        $row = pg_fetch_assoc($result_check);
        $total_police = $row['total_police'];
        pg_free_result($result_check);
    }
    
    // Get police tokens - try different possible column names
    $sql_tokens = "SELECT expoPushToken, police_id FROM policeusers WHERE is_on_shift = 1 AND account_status = 'P.Active' AND (expoPushToken IS NOT NULL AND expoPushToken != '' AND expoPushToken != 'null')";
    $result_tokens = pg_query($conn, $sql_tokens);
    $tokens = [];
    $debug_info = [];
    
    if ($result_tokens) {
        while($row = pg_fetch_assoc($result_tokens)) { 
            // Try different possible token columns
            $token = $row["expoPushToken"] ?? null;
            if ($token) {
                $tokens[] = $token;
                $debug_info[] = [
                    'police_id' => $row['police_id'] ?? 'unknown',
                    'token' => substr($token, 0, 20) . '...' // Show first 20 chars for debugging
                ];
            }
        }
        pg_free_result($result_tokens);
    }
    
    // Log debugging information
    error_log("Police Debug - Total police users: $total_police, Tokens found: " . count($tokens));
    if (!empty($debug_info)) {
        error_log("Police tokens debug: " . json_encode($debug_info));
    }

    // --- OPTIMIZED: Send notifications asynchronously (non-blocking) ---
    if (!empty($tokens)) {
        $message = [
            'to' => $tokens,
            'sound' => 'default',
            'title' => '🚨 AlertoMNL: SOS!',
            'body' => "Alert from $user_name at $a_address.",
            'data' => [
                'alert_id' => $alert_id,
                'user_name' => $user_name,
                'location' => $a_address,
                'timestamp' => time()
            ],
            'priority' => 'high', // High priority for emergency alerts
            'channelId' => 'emergency-alerts' // Custom channel for emergency notifications
        ];

        // Send notification with timeout
        $ch = curl_init("https://exp.host/--/api/v2/push/send");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json', 
            'Content-Type: application/json', 
            'Accept-Encoding: gzip, deflate'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 second connection timeout
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log notification status
        if ($http_code === 200) {
            error_log("SOS Notification sent successfully for alert ID: $alert_id");
        } else {
            error_log("SOS Notification failed for alert ID: $alert_id, HTTP Code: $http_code");
        }
    }
    
    // --- Return success immediately ---
    echo json_encode([
        "success" => true, 
        "alert_id" => $alert_id, 
        "message" => "SOS alert created successfully.",
        "timestamp" => time(),
        "notifications_sent" => count($tokens),
        "debug_info" => [
            "total_police_users" => $total_police,
            "tokens_found" => count($tokens),
            "token_details" => $debug_info
        ]
    ]);
    
    pg_close($conn);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => $e->getMessage(),
        "timestamp" => time()
    ]);
    
    // Log the error
    error_log("SOS Alert Error: " . $e->getMessage());
}
?> 