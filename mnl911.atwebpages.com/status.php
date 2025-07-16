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

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
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
        $stmt_assign = $conn->prepare($sql_assign);
        $stmt_assign->bind_param("ii", $alert_id, $police_id);
        if ($stmt_assign->execute()) {
            // Update alert status
            $sql_update = "UPDATE sosalert SET a_status = 'active' WHERE alert_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $alert_id);
            $stmt_update->execute();
            $stmt_update->close();
            echo json_encode(['success' => true, 'message' => 'Alert assigned successfully.']);
        } else {
            if ($conn->errno == 1062) {
                echo json_encode(['success' => false, 'error' => 'This alert has already been assigned.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt_assign->error]);
            }
        }
        $stmt_assign->close();
        break;

    case 'arrived':
        if (!$alert_id) {
            echo json_encode(['success' => false, 'error' => 'Missing alert_id']);
            break;
        }
        $stmt = $conn->prepare("UPDATE sosalert SET a_status = 'arrived' WHERE alert_id = ?");
        $stmt->bind_param("i", $alert_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Alert status updated to arrived']);
            } else {
                echo json_encode(['success' => false, 'error' => 'No rows updated. Check alert_id.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
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
            $stmt_coords = $conn->prepare("SELECT a_latitude, a_longitude FROM sosalert WHERE alert_id = ?");
            $stmt_coords->bind_param("i", $alert_id);
            $stmt_coords->execute();
            $stmt_coords->bind_result($lat, $lng);
            $stmt_coords->fetch();
            $stmt_coords->close();
            if ($lat === null || $lng === null) throw new Exception("No coordinates found.");

            // Get type_id
            $type_id = null;
            $stmt_type = $conn->prepare("SELECT type_id FROM crimetypes WHERE severity = ?");
            $stmt_type->bind_param("s", $severity);
            $stmt_type->execute();
            $stmt_type->bind_result($fetched_type_id);
            if ($stmt_type->fetch()) $type_id = $fetched_type_id;
            $stmt_type->close();
            if ($type_id === null) throw new Exception("Invalid severity.");

            // Insert crime report
            $default_zone_id = 1;
            $stmt_report = $conn->prepare("INSERT INTO crimereports (alert_id, zone_id, type_id, description, latitude, longtitude, report_time, crime_type) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt_report->bind_param("iiisdds", $alert_id, $default_zone_id, $type_id, $description, $lat, $lng, $incident_type);
            $stmt_report->execute();
            $stmt_report->close();

            // Update alert status
            $stmt_alert = $conn->prepare("UPDATE sosalert SET a_status = 'resolved' WHERE alert_id = ?");
            $stmt_alert->bind_param("i", $alert_id);
            $stmt_alert->execute();
            $stmt_alert->close();

            // Update resolved_at in sosofficerassignments
            $stmt_assignment = $conn->prepare("UPDATE sosofficerassignments SET resolved_at = NOW() WHERE alert_id = ? AND police_id = ?");
            $stmt_assignment->bind_param("ii", $alert_id, $police_id);
            $stmt_assignment->execute();
            $stmt_assignment->close();

            // Insert police history
            $stmt_history = $conn->prepare("INSERT INTO policehistory (alert_id, police_id, response_time, p_audio) VALUES (?, ?, NOW(), ?)");
            $empty_audio = "";
            $stmt_history->bind_param("iis", $alert_id, $police_id, $empty_audio);
            $stmt_history->execute();
            $stmt_history->close();

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

$conn->close();
?>