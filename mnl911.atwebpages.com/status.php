<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$action = $_POST['action'] ?? null;
$alert_id = $_POST['alert_id'] ?? null;

if (!$action || !$alert_id) {
    echo json_encode(['success' => false, 'error' => 'Missing action or alert_id']);
    exit();
}

switch ($action) {
    case 'accept':
        $police_id = $_POST['police_id'] ?? null;
        if (!$police_id) {
            echo json_encode(['success' => false, 'error' => 'Missing police_id']);
            exit();
        }
        // Assign officer
        $sql_assign = "INSERT INTO sosofficerassignments (alert_id, police_id, assigned_at, status) VALUES (?, ?, NOW(), 'assigned')";
        $stmt_assign = pg_prepare($conn, "assign_alert", $sql_assign);
        $params = [$alert_id, $police_id];
        $result = pg_execute($conn, "assign_alert", $params);
        if ($result) {
            // Update alert status
            $sql_update = "UPDATE sosalert SET a_status = 'active' WHERE alert_id = ?";
            $stmt_update = pg_prepare($conn, "update_alert_status", $sql_update);
            $params = [$alert_id];
            $result = pg_execute($conn, "update_alert_status", $params);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Alert assigned successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . pg_last_error($conn)]);
            }
            pg_free_result($result);
        } else {
            if (pg_last_error($conn) == '23505') { // Duplicate key error for alert_id
                echo json_encode(['success' => false, 'error' => 'This alert has already been assigned.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . pg_last_error($conn)]);
            }
        }
        pg_free_result($result);
        break;

    case 'arrived':
        if (!$alert_id) {
            echo json_encode(['success' => false, 'error' => 'Missing alert_id']);
            break;
        }
        $stmt = pg_prepare($conn, "update_alert_arrived", "UPDATE sosalert SET a_status = 'arrived' WHERE alert_id = ?");
        $params = [$alert_id];
        $result = pg_execute($conn, "update_alert_arrived", $params);
        if ($result) {
            if (pg_affected_rows($result) > 0) {
                echo json_encode(['success' => true, 'message' => 'Alert status updated to arrived']);
            } else {
                echo json_encode(['success' => false, 'error' => 'No rows updated. Check alert_id.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => pg_last_error($conn)]);
        }
        pg_free_result($result);
        break;

    case 'resolve':
        $police_id = $_POST['police_id'] ?? null;
        $incident_type = $_POST['incident_type'] ?? null;
        $severity = $_POST['severity'] ?? null;
        $description = $_POST['description'] ?? null;
        if (!$police_id || !$incident_type || !$severity) {
            echo json_encode(['success' => false, 'error' => 'Required fields are missing.']);
            exit();
        }
        $conn->begin_transaction();
        try {
            // Get lat/lng
            $lat = null; $lng = null;
            $stmt_coords = pg_prepare($conn, "get_coords", "SELECT a_latitude, a_longitude FROM sosalert WHERE alert_id = $1");
            $params = [$alert_id];
            $result = pg_execute($conn, "get_coords", $params);
            if ($result) {
                $row = pg_fetch_assoc($result);
                if ($row) {
                    $lat = $row['a_latitude'];
                    $lng = $row['a_longitude'];
                }
            } else {
                throw new Exception("No coordinates found: " . pg_last_error($conn));
            }
            pg_free_result($result);
            if ($lat === null || $lng === null) throw new Exception("No coordinates found.");

            // Get type_id
            $type_id = null;
            $stmt_type = pg_prepare($conn, "get_type_id", "SELECT type_id FROM crimetypes WHERE severity = $1");
            $params = [$severity];
            $result = pg_execute($conn, "get_type_id", $params);
            if ($result) {
                $row = pg_fetch_assoc($result);
                if ($row) $type_id = $row['type_id'];
            } else {
                throw new Exception("Invalid severity: " . pg_last_error($conn));
            }
            pg_free_result($result);
            if ($type_id === null) throw new Exception("Invalid severity.");

            // Insert crime report
            $default_zone_id = 1;
            $stmt_report = pg_prepare($conn, "insert_crime_report", "INSERT INTO crimereports (alert_id, zone_id, type_id, description, latitude, longtitude, report_time, crime_type) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            $params = [$alert_id, $default_zone_id, $type_id, $description, $lat, $lng, $incident_type];
            $result = pg_execute($conn, "insert_crime_report", $params);
            if (!$result) {
                throw new Exception("Failed to insert crime report: " . pg_last_error($conn));
            }
            pg_free_result($result);

            // Update alert status
            $stmt_alert = pg_prepare($conn, "update_alert_resolved", "UPDATE sosalert SET a_status = 'resolved' WHERE alert_id = ?");
            $params = [$alert_id];
            $result = pg_execute($conn, "update_alert_resolved", $params);
            if (!$result) {
                throw new Exception("Failed to update alert status: " . pg_last_error($conn));
            }
            pg_free_result($result);

            // Update resolved_at in sosofficerassignments
            $stmt_assignment = pg_prepare($conn, "update_assignment_resolved", "UPDATE sosofficerassignments SET resolved_at = NOW() WHERE alert_id = ? AND police_id = ?");
            $params = [$alert_id, $police_id];
            $result = pg_execute($conn, "update_assignment_resolved", $params);
            if (!$result) {
                throw new Exception("Failed to update assignment resolved_at: " . pg_last_error($conn));
            }
            pg_free_result($result);

            // Insert police history
            $stmt_history = pg_prepare($conn, "insert_police_history", "INSERT INTO policehistory (alert_id, police_id, response_time, p_audio) VALUES ($1, $2, NOW(), $3)");
            $empty_audio = "";
            $params = [$alert_id, $police_id, $empty_audio];
            $result = pg_execute($conn, "insert_police_history", $params);
            if (!$result) {
                throw new Exception("Failed to insert police history: " . pg_last_error($conn));
            }
            pg_free_result($result);

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Incident resolved and report filed successfully.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

pg_close($conn);
?>