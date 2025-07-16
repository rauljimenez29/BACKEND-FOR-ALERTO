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

$police_id = $_GET['police_id'] ?? $_POST['police_id'] ?? null;
$latitude = $_GET['latitude'] ?? $_POST['latitude'] ?? null;
$longitude = $_GET['longitude'] ?? $_POST['longitude'] ?? null;

if ($police_id) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE police_id = ? ORDER BY id DESC");
    $stmt->bind_param('i', $police_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
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
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing police_id']);
}
$conn->close();

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