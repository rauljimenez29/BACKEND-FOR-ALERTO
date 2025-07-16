<?php
// Setup headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]));
}

$sql = "
    SELECT
        sa.alert_id AS alertId,
        CONCAT(n.f_name, ' ', n.l_name) AS name,
        sa.a_address AS address,
        sa.a_created AS date,
        cr.crime_type AS type,
        ct.severity,
        IFNULL(CONCAT(p.f_name, ' ', p.l_name), 'Unassigned') AS respondedBy
    FROM sosalert sa
    LEFT JOIN normalusers n ON sa.nuser_id = n.nuser_id
    LEFT JOIN crimereports cr ON sa.alert_id = cr.alert_id
    LEFT JOIN crimetypes ct ON cr.type_id = ct.type_id
    LEFT JOIN sosofficerassignments soa ON sa.alert_id = soa.alert_id
    LEFT JOIN policeusers p ON soa.police_id = p.police_id
    ORDER BY sa.alert_id DESC
";

$result = pg_query($conn, $sql);
if (!$result) {
    // ✅ Debug full query + error
    die(json_encode([
        "success" => false,
        "message" => "SQL query failed: " . pg_last_error(),
        "query" => $sql
    ]));
}

$records = [];
while ($row = pg_fetch_assoc($result)) {
    $records[] = $row;
}

if (empty($records)) {
    error_log("⚠️ No records returned from fetch_crime_data query.");
}

echo json_encode(["success" => true, "records" => $records]);

pg_close($conn);
?>
