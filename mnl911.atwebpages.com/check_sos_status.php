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
    $host = "fdb1028.awardspace.net";
    $user = "4642576_crimemap";
    $password = "@CrimeMap_911";
    $dbname = "4642576_crimemap";

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
    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
   

    // --- Query a_status ---
    $sql = "SELECT a_status FROM sosalert WHERE alert_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $alert_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
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

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
