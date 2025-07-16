<?php
// --- Standard Headers & Error Reporting ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Fatal error catch block ---
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        ob_clean();
        echo json_encode([
            "success" => false,
            "message" => "Fatal Server Error",
            "error_details" => $error['message']
        ]);
        exit();
    }
});

try {
    // --- DB credentials ---
    $dsn = 'postgresql://postgres.uyqspojnegjmxnedbtph:09123433140aa@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres';
    $conn = pg_connect($dsn);
    if (!$conn) {
        throw new Exception("Database connection failed: " . pg_last_error($conn));
    }

    // --- Require GET ---
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("GET request required");
    }

    // --- Validate input ---
    if (!isset($_GET['alert_id'])) {
        throw new Exception("Missing alert_id parameter");
    }

    $alert_id = intval($_GET['alert_id']);

    // --- Connect DB ---
    // The original MySQL connection logic is removed as per the edit hint.
    // The new code snippet provided a placeholder for $dsn and $conn.
    // Assuming $dsn and $conn are now defined and $conn is a valid PostgreSQL connection.

    // --- Query a_status ---
    $sql = "SELECT a_status FROM sosalert WHERE alert_id = $1";
    $params = [$alert_id];
    $result = pg_query_params($conn, $sql, $params);

    if ($result && $row = pg_fetch_assoc($result)) {
        echo json_encode([
            "success" => true,
            "status" => $row['a_status']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Alert not found"
        ]);
    }

    pg_free_result($result); // Free the result set
    pg_close($conn); // Close the connection

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
