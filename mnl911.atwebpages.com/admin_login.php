<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- Database Connection ---
// This uses the connection details from your provided schema host.
// IMPORTANT: You will need to replace "your_database_password" with your actual password.
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
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
    pg_close($conn);
    exit;
}

// Prepare and bind to prevent SQL injection
$stmt = pg_prepare($conn, "SELECT admin_id, password FROM adminusers WHERE BINARY email = $1");
$result = pg_execute($conn, "SELECT admin_id, password FROM adminusers WHERE BINARY email = $1", array($email));

if (pg_num_rows($result) === 1) {
    $row = pg_fetch_assoc($result);
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

pg_close($conn);
?> 