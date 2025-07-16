<?php
// PostgreSQL connection file. All includes should use pg_connect and the provided DSN.
header('Content-Type: application/json');

// --- PostgreSQL Connection ---
$dsn = 'postgresql://postgres.uyqspojnegjmxnedbtph:09123433140aa@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database Connection Error: ' . pg_last_error($conn)
    ]);
    exit();
}
?>