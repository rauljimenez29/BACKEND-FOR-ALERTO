<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
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
        pg_close($conn);
        exit;
    }

    $stmt = pg_prepare($conn, "UPDATE policeusers SET is_on_shift = $1 WHERE police_id = $2", array($is_on_shift, $police_id));
    $result = pg_execute($conn, "UPDATE policeusers SET is_on_shift = $1 WHERE police_id = $2", array($is_on_shift, $police_id));

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
    pg_close($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'POST request required']);
}
?>