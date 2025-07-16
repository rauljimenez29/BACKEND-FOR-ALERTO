<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept both JSON and form-data
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $police_id = isset($input['police_id']) ? intval($input['police_id']) : null;
        $is_on_shift = isset($input['is_on_shift']) ? intval($input['is_on_shift']) : null;
    } else {
        $police_id = isset($_POST['police_id']) ? intval($_POST['police_id']) : null;
        $is_on_shift = isset($_POST['is_on_shift']) ? intval($_POST['is_on_shift']) : null;
    }

    if ($police_id === null || $is_on_shift === null) {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("UPDATE policeusers SET is_on_shift = ? WHERE police_id = ?");
    $stmt->bind_param("ii", $is_on_shift, $police_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'POST request required']);
}
$conn->close();
?>