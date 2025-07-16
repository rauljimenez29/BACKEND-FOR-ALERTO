<?php
// PostgreSQL connection file. All includes should use pg_connect and the provided DSN.
header('Content-Type: application/json');

// --- PostgreSQL Connection ---
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database Connection Error: ' . pg_last_error()
    ]);
    exit();
}
?>