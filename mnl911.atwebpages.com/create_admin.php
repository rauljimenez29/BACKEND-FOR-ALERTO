<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// --- IMPORTANT: 
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Get the posted data
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password cannot be empty.']);
    pg_close($conn);
    exit;
}

// Check if email already exists to prevent duplicates
$stmt = pg_prepare($conn, "SELECT admin_id FROM adminusers WHERE email = $1");
$result = pg_execute($conn, "SELECT admin_id FROM adminusers WHERE email = $1", array($email));

if (pg_num_rows($result) > 0) {
    echo json_encode(['success' => false, 'message' => 'An admin with this email already exists.']);
    pg_close($conn);
    exit;
}
pg_close($conn);

// Securely hash the password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Prepare and bind to insert the new admin user
$stmt = pg_prepare($conn, "INSERT INTO adminusers (email, password) VALUES ($1, $2)");
$result = pg_execute($conn, "INSERT INTO adminusers (email, password) VALUES ($1, $2)", array($email, $hashed_password));

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Admin user (' . $email . ') created successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create admin user.']);
}

pg_close($conn);
?> 