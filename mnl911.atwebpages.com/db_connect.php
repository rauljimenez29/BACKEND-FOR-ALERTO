<?php
// Set the default content type for all API responses
header('Content-Type: application/json');

// --- Database Credentials ---
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911"; // Your password is in one place
$dbname = "4642576_crimemap";

// --- Create and Check Connection ---
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    // Stop script execution and return a clear error message
    echo json_encode([
        'success' => false,
        'message' => 'Database Connection Error: ' . $conn->connect_error
    ]);
    exit(); // Important to stop the script if the DB connection fails
}
?>