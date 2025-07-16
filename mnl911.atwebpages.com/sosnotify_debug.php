<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $location_address = $_POST['location_address'] ?? 'Location not provided';

    // Debug: include received coordinates in the response if debug param is set
    if (isset($_POST['debug'])) {
        echo json_encode([
            "success" => true,
            "debug_received_lat" => $latitude,
            "debug_received_lng" => $longitude,
            "debug_nuser_id" => $nuser_id
        ]);
        exit();
    }

    if (!$nuser_id || !$latitude || !$longitude) {
        throw new Exception("Missing required fields.");
    }

    // --- MODIFIED: The INSERT statement now includes the new a_address column --
    $sql_insert = "INSERT INTO sosalert (nuser_id, a_created, a_latitude, a_longitude, a_address, a_status) VALUES ($1, NOW(), $2, $3, $4, 'pending')";
    $params_insert = [$nuser_id, $latitude, $longitude, $location_address];
    $result_insert = pg_query_params($conn, $sql_insert, $params_insert);

    if (!$result_insert) {
        throw new Exception("Database INSERT failed: " . pg_last_error($conn));
    }
    $alert_id = pg_last_oid($result_insert);

    // --- The rest of the script for fetching names and sending notifications remains the same ---
    // Fetch user's name
    $user_name = "User " . $nuser_id;
    $sql_user = "SELECT f_name FROM normalusers WHERE nuser_id = $1";
    $params_user = [$nuser_id];
    $result_user = pg_query_params($conn, $sql_user, $params_user);
    if ($row_user = pg_fetch_assoc($result_user)) {
        $user_name = $row_user['f_name'];
    }
    pg_free_result($result_user);

    // Get police tokens
    $sql_tokens = "SELECT expoPushToken FROM policeusers WHERE expoPushToken IS NOT NULL AND expoPushToken != ''";
    $result_tokens = pg_query($conn, $sql_tokens);
    $tokens = [];
    while($row = pg_fetch_assoc($result_tokens)) { $tokens[] = $row["expoPushToken"]; }
    pg_free_result($result_tokens);

    // Send notifications
    if (!empty($tokens)) {
        $message = [
            'to' => $tokens,
            'sound' => 'default',
            'title' => '🚨 AlertoMNL: SOS!',
            'body' => "Alert from $user_name at $location_address.",
            'data' => [
                'alert_id' => $alert_id,
                'user_name' => $user_name,
                'location' => $location_address
            ],
        ];
        $ch = curl_init("https://exp.host/--/api/v2/push/send");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json', 'Accept-Encoding: gzip, deflate']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    echo json_encode(["success" => true, "alert_id" => $alert_id, "message" => "SOS alert created and notifications sent."]);
    pg_close($conn);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
