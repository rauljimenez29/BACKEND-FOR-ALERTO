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

if (isset($_GET['nuser_id'])) {
    $nuser_id = $_GET['nuser_id'];
    
    // Modified query to fetch directly from sosalert table
    $sql = "SELECT 
                sa.alert_id,
                sa.alert_id as history_id,
                sa.a_address AS location,
                nu.f_name AS victim_fname,
                nu.l_name AS victim_lname,
                ph.response_time AS resolved_at,
                sa.a_created AS trigger_time
            FROM sosalert sa
            JOIN normalusers nu ON sa.nuser_id = nu.nuser_id
            LEFT JOIN policehistory ph ON sa.alert_id = ph.alert_id
            WHERE sa.nuser_id = $1
            ORDER BY sa.alert_id DESC";

    $stmt = pg_prepare($conn, "get_user_history", $sql);
    $result = pg_execute($conn, "get_user_history", array($nuser_id));
    
    $history = array();
    while ($row = pg_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "history" => $history
    ]);
    
    pg_free_result($result);
    pg_close($conn);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "nuser_id required"
    ]);
}
?>