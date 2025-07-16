<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

// --- Database Connection ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// --- Action Handler ---

// Get the request body and decode it
$request_body = file_get_contents('php://input');
$data = json_decode($request_body);

// Get the action from the decoded JSON, or set to null if not present
$action = isset($data->action) ? $data->action : null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action specified.']);
    $conn->close();
    exit();
}

$conn->begin_transaction();
$success = false;
$message = '';

try {
    switch ($action) {
        case 'set_sos_resolved':
            $stmt = $conn->prepare("UPDATE sosalert SET a_status = 'resolved' WHERE a_status != 'resolved'");
            $stmt->execute();
            $message = 'All SOS alerts have been set to resolved.';
            $success = true;
            break;
            
        case 'delete_crime_reports':
            // THE FIX: Changed from DELETE to TRUNCATE to reset the auto-incrementing ID.
            // Also handles the dependent clusterresults table to avoid foreign key issues.
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            $conn->query("TRUNCATE TABLE clusterresults");
            $conn->query("TRUNCATE TABLE crimereports");
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            $message = 'All crime reports and related cluster data have been deleted.';
            $success = true;
            break;

        case 'delete_sos_alerts':
            // Due to foreign keys, we must delete from dependent tables first.
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            $conn->query("TRUNCATE TABLE sosofficerassignments");
            $conn->query("TRUNCATE TABLE policehistory");
            $conn->query("TRUNCATE TABLE userhistory");
            $conn->query("TRUNCATE TABLE clusterresults");
            $conn->query("TRUNCATE TABLE sosalert");
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            $message = 'All SOS alerts and related data have been deleted.';
            $success = true;
            break;
            
        case 'delete_normal_users':
             // Related SOS alerts, contacts, etc., should be deleted first.
             // We use TRUNCATE for efficiency and to reset auto-incrementing IDs.
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            $conn->query("TRUNCATE TABLE usercontacts");
            $conn->query("TRUNCATE TABLE normalusers");
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            $message = 'All normal users and their contacts have been deleted.';
            $success = true;
            break;

        case 'delete_police_users':
            // Related assignments, history, etc., must be deleted first.
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            $conn->query("TRUNCATE TABLE notifications");
            $conn->query("TRUNCATE TABLE police_locations");
            $conn->query("TRUNCATE TABLE policeusers");
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            $message = 'All police users and their related data have been deleted.';
            $success = true;
            break;

        default:
            $message = 'Invalid action specified.';
            $success = false;
            break;
    }
    
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $message = 'A database error occurred: ' . $e->getMessage();
    $success = false;
}

if(isset($stmt)) {
    $stmt->close();
}

$conn->close();

echo json_encode(['success' => $success, 'message' => $message]);

?>
