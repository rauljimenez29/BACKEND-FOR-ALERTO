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
    $host = "fdb1028.awardspace.net";
    $user = "4642576_crimemap";
    $password = "@CrimeMap_911";
    $dbname = "4642576_crimemap";

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

    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // --- MODIFIED: The INSERT statement now includes the new a_address column --
    $sql_insert = "INSERT INTO sosalert (nuser_id, a_created, a_latitude, a_longitude, a_address, a_status) VALUES (?, NOW(), ?, ?, ?, 'pending')";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("idds", $nuser_id, $latitude, $longitude, $location_address);

    if (!$stmt_insert->execute() || $stmt_insert->affected_rows === 0) {
        throw new Exception("Database INSERT failed. Check if nuser_id exists.");
    }
    $alert_id = $conn->insert_id;
    $stmt_insert->close();

    // --- The rest of the script for fetching names and sending notifications remains the same ---
    // Fetch user's name
    $user_name = "User " . $nuser_id;
    $sql_user = "SELECT f_name FROM normalusers WHERE nuser_id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $nuser_id);
    if ($stmt_user->execute()) {
        $result_user = $stmt_user->get_result();
        if ($row_user = $result_user->fetch_assoc()) {
            $user_name = $row_user['f_name'];
        }
    }
    $stmt_user->close();
    // Get police tokens
    $sql_tokens = "SELECT expoPushToken FROM policeusers WHERE expoPushToken IS NOT NULL AND expoPushToken != ''";
    $result_tokens = $conn->query($sql_tokens);
    $tokens = [];
    while($row = $result_tokens->fetch_assoc()) { $tokens[] = $row["expoPushToken"]; }
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
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
