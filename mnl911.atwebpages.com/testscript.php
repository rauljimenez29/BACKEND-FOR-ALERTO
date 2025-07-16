<?php
$dsn = 'postgresql://postgres.uyqspojnegjmxnedbtph:09123433140aa@aws-0-ap-southeast-1.pooler.supabase.com:5432/postgres';
$conn = pg_connect($dsn);

if (!$conn) {
    echo "❌ Connection Failed: " . pg_last_error($conn);
    exit();
}

echo "✅ Connected to Supabase Postgres!";

// Optional: Run a simple query
$result = pg_query($conn, "SELECT NOW()");
if ($result) {
    $row = pg_fetch_row($result);
    echo "<br>Current time on DB: " . $row[0];
} else {
    echo "<br>Query failed: " . pg_last_error($conn);
}

pg_close($conn);
?> 