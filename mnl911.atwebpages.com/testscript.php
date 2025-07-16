<?php
$host = 'db.uyqspojnegjmxnedbtph.supabase.co';
$port = '5432';
$db   = 'postgres';
$user = 'postgres';
$pass = '09123433140aa';
$sslmode = 'require';

$dsn = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=$sslmode";
$conn = pg_connect($dsn);

if (!$conn) {
    echo "❌ Connection Failed: " . pg_last_error();
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