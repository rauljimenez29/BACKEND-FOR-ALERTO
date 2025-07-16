<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- Database Connection ---
// This uses the connection details from your provided schema host.
// IMPORTANT: You will need to replace "your_database_password" with your actual password.
$servername = "fdb1028.awardspace.net";
$username = "4642576_crimemap";
$password = "@CrimeMap_911"; // <-- IMPORTANT: REPLACE WITH YOUR PASSWORD
$dbname = "4642576_crimemap";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please contact support.']);
    exit();
}

// Get the posted data
$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    $conn->close();
    exit;
}

// Prepare and bind to prevent SQL injection
$stmt = $conn->prepare("SELECT admin_id, password FROM adminusers WHERE BINARY email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $hashed_password = $row['password'];

    // Verify the password against the stored hash.
    // This assumes your admin passwords are created using password_hash().
    if (password_verify($password, $hashed_password)) {
        // Successful login
        echo json_encode(['success' => true, 'message' => 'Login successful!']);
    } else {
        // Invalid password
        echo json_encode(['success' => false, 'message' => 'Invalid credentials provided.']);
    }
} else {
    // No user found
    echo json_encode(['success' => false, 'message' => 'Invalid credentials provided.']);
}

$stmt->close();
$conn->close();
?> 