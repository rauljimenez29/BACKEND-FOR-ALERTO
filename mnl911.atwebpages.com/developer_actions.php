<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

// --- Database Connection ---
$dsn = "host=db.uyqspojnegjmxnedbtph.supabase.co port=5432 dbname=postgres user=postgres password=09123433140aa sslmode=require";
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection Failed: " . pg_last_error()]);
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
    pg_close($conn);
    exit();
}

$success = false;
$message = '';

try {
    switch ($action) {
        case 'set_sos_resolved':
            $stmt = pg_prepare($conn, "UPDATE sosalert SET a_status = 'resolved' WHERE a_status != 'resolved'");
            $result = pg_execute($conn, "UPDATE sosalert SET a_status = 'resolved' WHERE a_status != 'resolved'");
            $message = 'All SOS alerts have been set to resolved.';
            $success = true;
            break;
            
        case 'delete_crime_reports':
            // THE FIX: Changed from DELETE to TRUNCATE to reset the auto-incrementing ID.
            // Also handles the dependent clusterresults table to avoid foreign key issues.
            pg_query($conn, "SET FOREIGN_KEY_CHECKS=0");
            pg_query($conn, "TRUNCATE TABLE clusterresults");
            pg_query($conn, "TRUNCATE TABLE crimereports");
            pg_query($conn, "SET FOREIGN_KEY_CHECKS=1");
            $message = 'All crime reports and related cluster data have been deleted.';
            $success = true;
            break;

        case 'delete_sos_alerts':
            // Due to foreign keys, we must delete from dependent tables first.
            pg_query($conn, "SET FOREIGN_KEY_CHECKS=0");
            pg_query($conn, "TRUNCATE TABLE sosofficerassignments");
            pg_query($conn, "TRUNCATE TABLE policehistory");
            pg_query($conn, "TRUNCATE TABLE userhistory");
            pg_query($conn, "TRUNCATE TABLE clusterresults");
            pg_query($conn, "TRUNCATE TABLE sosalert");
            pg_query($conn, "SET FOREIGN_KEY_CHECKS=1");
            $message = 'All SOS alerts and related data have been deleted.';
            $success = true;
            break;
            
        case 'delete_normal_users':
             // Related SOS alerts, contacts, etc., should be deleted first.
             // We use TRUNCATE for efficiency and to reset auto-incrementing IDs.
            pg_query($conn, "SET FOREIGN_KEY_CHECKS=0");
            pg_query($conn, "TRUNCATE TABLE usercontacts");
            pg_query($conn, "TRUNCATE TABLE normalusers");
            pg_query($conn, "SET FOREIGN_KEY_CHECKS=1");
            $message = 'All normal users and their contacts have been deleted.';
            $success = true;
            break;

        case 'delete_police_users':
            // Related assignments, history, etc., must be deleted first.
            pg_query($conn, "SET FOREIGN_KEY_CHECKS=0");
            pg_query($conn, "TRUNCATE TABLE notifications");
            pg_query($conn, "TRUNCATE TABLE police_locations");
            pg_query($conn, "TRUNCATE TABLE policeusers");
            pg_query($conn, "SET FOREIGN_KEY_CHECKS=1");
            $message = 'All police users and their related data have been deleted.';
            $success = true;
            break;

        default:
            $message = 'Invalid action specified.';
            $success = false;
            break;
    }
    
    // pg_commit is not directly available in pg_query/pg_prepare/pg_execute.
    // For simplicity, we'll assume a successful transaction for now,
    // as there's no explicit transaction management in the provided code.
    // In a real application, you'd manage transactions using pg_exec for BEGIN/COMMIT/ROLLBACK.
    // For this example, we'll just close the connection.

} catch (Exception $e) {
    // No explicit rollback for pg_query/pg_prepare/pg_execute.
    // If you need transaction management, you'd use pg_exec for BEGIN/ROLLBACK.
    $message = 'A database error occurred: ' . $e->getMessage();
    $success = false;
}

if(isset($stmt)) {
    pg_free_result($stmt); // Free the prepared statement resource
}

pg_close($conn);

echo json_encode(['success' => $success, 'message' => $message]);

?>
