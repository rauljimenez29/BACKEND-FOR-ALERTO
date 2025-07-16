<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate'); // Prevent caching
header('Pragma: no-cache'); // Prevent caching
header('Expires: 0'); // Prevent caching

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $police_id = $_GET['police_id'] ?? null;
    $police_lat = $_GET['latitude'] ?? null;
    $police_lng = $_GET['longitude'] ?? null;
    
    if (!$police_id) {
        echo json_encode(['success' => false, 'error' => 'Police ID is required']);
        exit();
    }

    // --- NEW: Check if police is on shift ---
    $check_shift_stmt = pg_prepare($conn, "check_shift", "SELECT is_on_shift FROM policeusers WHERE police_id = $1");
    $check_shift_params = [$police_id];
    $check_shift_result = pg_execute($conn, "check_shift", $check_shift_params);
    $is_on_shift = pg_fetch_result($check_shift_result, 0, 0);
    pg_free_result($check_shift_result);
    pg_close_stmt($check_shift_stmt);

    if ($is_on_shift != 1) {
        echo json_encode([
            'success' => true,
            'notifications' => [],
            'timestamp' => time(),
            'count' => 0
        ]);
        pg_close($conn);
        exit();
    }

    // Get pending alerts with optimized query
    $query = "SELECT a.*, nu.f_name, nu.l_name, nu.m_number 
              FROM sosalert a 
              JOIN normalusers nu ON a.nuser_id = nu.nuser_id 
              WHERE a.a_status = 'pending' 
              ORDER BY a.a_created DESC 
              LIMIT 20"; // Reduced from 50 to 20 for faster response
    
    $result = pg_query($conn, $query);
    $notifications = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // If police location is provided, check if within 10km radius (increased for better coverage)
            if ($police_lat && $police_lng) {
                $alert_lat = floatval($row['a_latitude']);
                $alert_lng = floatval($row['a_longitude']);
                
                // Calculate distance using Haversine formula
                $distance = calculateDistance(
                    floatval($police_lat), 
                    floatval($police_lng),
                    $alert_lat,
                    $alert_lng
                );
                
                // Include alerts within 10km radius (increased for better coverage)
                if ($distance <= 10) {
                    $row['distance'] = round($distance, 2);
                    $notifications[] = $row;
                }
            } else {
                // If no police location, include all alerts
                $notifications[] = $row;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'timestamp' => time(), // Add timestamp for debugging
        'count' => count($notifications) // Add count for debugging
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Earth's radius in kilometers
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

pg_close($conn);
?>