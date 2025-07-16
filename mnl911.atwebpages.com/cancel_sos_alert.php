    <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alert_id = $_POST['alert_id'] ?? null;

    if ($alert_id) {
        $stmt = pg_prepare($conn, "UPDATE sosalert SET a_status = 'cancelled' WHERE alert_id = $1");
        $result = pg_execute($conn, "UPDATE sosalert SET a_status = 'cancelled' WHERE alert_id = $1", array($alert_id));
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to cancel alert']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing alert_id']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
pg_close($conn);
?>