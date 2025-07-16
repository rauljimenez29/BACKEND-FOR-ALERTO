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

$police_id = $_GET['police_id'] ?? $_POST['police_id'] ?? null;
$latitude = $_GET['latitude'] ?? $_POST['latitude'] ?? null;
$longitude = $_GET['longitude'] ?? $_POST['longitude'] ?? null;

if ($police_id) {
    $stmt = pg_prepare($conn, "SELECT * FROM notifications WHERE police_id = $1 ORDER BY id DESC");
    $result = pg_execute($conn, $stmt, [$police_id]);
    $notifications = [];
    while ($row = pg_fetch_assoc($result)) {
        // Only filter if both latitude and longitude are provided
        if ($latitude && $longitude && isset($row['a_latitude']) && isset($row['a_longitude'])) {
            $distance = haversineGreatCircleDistance(
                floatval($latitude),
                floatval($longitude),
                floatval($row['a_latitude']),
                floatval($row['a_longitude'])
            );
            if ($distance <= 3) { // 3km radius
                $row['distance'] = round($distance, 2);
                $notifications[] = $row;
            }
        } else {
            $notifications[] = $row;
        }
    }
    echo json_encode(['success' => true, 'notifications' => $notifications]);
    pg_free_result($result);
    pg_close($conn);
} else {
    echo json_encode(['success' => false, 'error' => 'Missing police_id']);
}

// Haversine formula
function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371)
{
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $earthRadius * $angle;
}
?>