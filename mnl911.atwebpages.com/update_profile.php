<?php
ob_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
function log_debug($msg) {
    file_put_contents(__DIR__ . '/php_debug.log', date('c') . " " . $msg . "\n", FILE_APPEND);
}
log_debug("Request: " . json_encode($_POST));

// Database credentials
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    log_debug("DB connection failed: " . pg_last_error());
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    ob_end_flush();
    exit();
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_debug("Not a POST request");
    echo json_encode(["success" => false, "message" => "POST request required"]);
    ob_end_flush();
    exit();
}

// Get POST data
$nuser_id = $_POST['nuser_id'] ?? null;
$f_name = $_POST['firstName'] ?? null;
$l_name = $_POST['lastName'] ?? null;
$email = $_POST['email'] ?? null;
$phone = $_POST['phone'] ?? null;
$currentPassword = $_POST['currentPassword'] ?? null;
$newPassword = $_POST['newPassword'] ?? null;

if (!$nuser_id || !$f_name || !$l_name || !$email || !$phone) {
    log_debug("Missing required fields");
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    ob_end_flush();
    exit();
}

// Fetch profile lockout info
$sql = "SELECT profile_failed_attempts, profile_lockout_until FROM normalusers WHERE nuser_id = $1";
$stmt = pg_prepare($conn, "fetch_profile_lockout", $sql);
if (!$stmt) {
    log_debug("Prepare failed: " . pg_last_error());
    echo json_encode(["success" => false, "message" => "Prepare failed: " . pg_last_error()]);
    pg_close($conn);
    ob_end_flush();
    exit();
}
$result = pg_execute($conn, "fetch_profile_lockout", array($nuser_id));
if (pg_num_rows($result) === 0) {
    log_debug("User not found: $nuser_id");
    echo json_encode(["success" => false, "message" => "User not found"]);
    pg_close($conn);
    ob_end_flush();
    exit();
}
$row = pg_fetch_assoc($result);
pg_free_result($result);

$profile_failed_attempts = $row['profile_failed_attempts'];
$profile_lockout_until = $row['profile_lockout_until'];
pg_close($conn);

$current_time = round(microtime(true) * 1000);

// --- Reset lockout if expired ---
if ($profile_lockout_until && $current_time >= $profile_lockout_until) {
    $sql_reset = "UPDATE normalusers SET profile_failed_attempts = 0, profile_lockout_until = NULL WHERE nuser_id = $1";
    $stmt_reset = pg_prepare($conn, "reset_profile_lockout", $sql_reset);
    if (!$stmt_reset) {
        log_debug("Prepare failed (reset lockout): " . pg_last_error());
        echo json_encode(["success" => false, "message" => "Prepare failed: " . pg_last_error()]);
        pg_close($conn);
        ob_end_flush();
        exit();
    }
    $result = pg_execute($conn, "reset_profile_lockout", array($nuser_id));
    if (!$result) {
        log_debug("Execute failed (reset lockout): " . pg_last_error());
        echo json_encode(["success" => false, "message" => "Execute failed: " . pg_last_error()]);
        pg_close($conn);
        ob_end_flush();
        exit();
    }
    pg_free_result($result);
    $profile_failed_attempts = 0;
    $profile_lockout_until = null;
}

// --- Enforce lockout if still active ---
if ($profile_lockout_until && $current_time < $profile_lockout_until) {
    $msg = "You've tried to change your password too many times. You cannot update your profile for";
    echo json_encode([
        "success" => false,
        "message" => $msg,
        "lockout" => true,
        "profile_lockout_until" => $profile_lockout_until
    ]);
    pg_close($conn);
    ob_end_flush();
    exit();
}

if ($currentPassword && !$newPassword) {
    echo json_encode(["success" => false, "message" => "Please enter a new password to change your password."]);
    pg_close($conn);
    ob_end_flush();
    exit();
}

if ($newPassword) {
    // Verify current password
    $sql = "SELECT password FROM normalusers WHERE nuser_id = $1";
    $stmt = pg_prepare($conn, "check_password", $sql);
    if (!$stmt) {
        log_debug("Prepare failed (password check): " . pg_last_error());
        echo json_encode(["success" => false, "message" => "Prepare failed: " . pg_last_error()]);
        pg_close($conn);
        ob_end_flush();
        exit();
    }
    $result = pg_execute($conn, "check_password", array($nuser_id));
    if (pg_num_rows($result) === 0) {
        log_debug("User not found (password check): $nuser_id");
        echo json_encode(["success" => false, "message" => "User not found"]);
        pg_free_result($result);
        pg_close($conn);
        ob_end_flush();
        exit();
    }
    $row = pg_fetch_assoc($result);
    pg_free_result($result);

    if (!password_verify($currentPassword, $row['password'])) {
        $profile_failed_attempts++;
        $new_profile_lockout = null;
        if ($profile_failed_attempts == 5) {
            $new_profile_lockout = $current_time + 5 * 60 * 1000;
        } elseif ($profile_failed_attempts == 10) {
            $new_profile_lockout = $current_time + 30 * 60 * 1000;
        }

        // Update lockout
        if ($new_profile_lockout === null) {
            $sql_update = "UPDATE normalusers SET profile_failed_attempts = $1, profile_lockout_until = NULL WHERE nuser_id = $2";
            $stmt_update = pg_prepare($conn, "update_profile_lockout_attempts", $sql_update);
            if (!$stmt_update) {
                log_debug("Prepare failed (update lockout attempts): " . pg_last_error());
                echo json_encode(["success" => false, "message" => "Prepare failed: " . pg_last_error()]);
                pg_close($conn);
                ob_end_flush();
                exit();
            }
            $result = pg_execute($conn, "update_profile_lockout_attempts", array($profile_failed_attempts, $nuser_id));
        } else {
            $sql_update = "UPDATE normalusers SET profile_failed_attempts = $1, profile_lockout_until = $2 WHERE nuser_id = $3";
            $stmt_update = pg_prepare($conn, "update_profile_lockout_attempts_and_lockout", $sql_update);
            if (!$stmt_update) {
                log_debug("Prepare failed (update lockout attempts and lockout): " . pg_last_error());
                echo json_encode(["success" => false, "message" => "Prepare failed: " . pg_last_error()]);
                pg_close($conn);
                ob_end_flush();
                exit();
            }
            $result = pg_execute($conn, "update_profile_lockout_attempts_and_lockout", array($profile_failed_attempts, $new_profile_lockout, $nuser_id));
        }
        if (!$result) {
            log_debug("Execute failed (update lockout): " . pg_last_error());
            echo json_encode(["success" => false, "message" => "Execute failed: " . pg_last_error()]);
            pg_close($conn);
            ob_end_flush();
            exit();
        }
        pg_free_result($result);
        $msg = "The current password you entered is incorrect.";
        echo json_encode([
            "success" => false,
            "message" => $msg,
            "lockout" => !!$new_profile_lockout,
            "profile_lockout_until" => $new_profile_lockout ?? null
        ]);
        pg_close($conn);
        ob_end_flush();
        exit();
    }

    // Valid password, update with new password and reset profile lockout
    $hashed_new_password = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE normalusers SET f_name = $1, l_name = $2, email = $3, m_number = $4, password = $5, profile_failed_attempts = 0, profile_lockout_until = NULL WHERE nuser_id = $6";
    $stmt = pg_prepare($conn, "update_user_with_password", $sql);
    if (!$stmt) {
        log_debug("Prepare failed (update with password): " . pg_last_error());
        echo json_encode(["success" => false, "message" => "Prepare failed: " . pg_last_error()]);
        pg_close($conn);
        ob_end_flush();
        exit();
    }
    $result = pg_execute($conn, "update_user_with_password", array($f_name, $l_name, $email, $phone, $hashed_new_password, $nuser_id));
    if (!$result) {
        log_debug("Execute failed (update with password): " . pg_last_error());
        echo json_encode(["success" => false, "message" => "Execute failed: " . pg_last_error()]);
        pg_close($conn);
        ob_end_flush();
        exit();
    }
    pg_free_result($result);
} else {
    //  No password change, reset lockout and attempts
    $sql = "UPDATE normalusers SET f_name = $1, l_name = $2, email = $3, m_number = $4, profile_failed_attempts = 0, profile_lockout_until = NULL WHERE nuser_id = $5";
    $stmt = pg_prepare($conn, "update_user_no_password", $sql);
    if (!$stmt) {
        log_debug("Prepare failed (update no password): " . pg_last_error());
        echo json_encode(["success" => false, "message" => "Prepare failed: " . pg_last_error()]);
        pg_close($conn);
        ob_end_flush();
        exit();
    }
    $result = pg_execute($conn, "update_user_no_password", array($f_name, $l_name, $email, $phone, $nuser_id));
    if (!$result) {
        log_debug("Execute failed (update no password): " . pg_last_error());
        echo json_encode(["success" => false, "message" => "Execute failed: " . pg_last_error()]);
        pg_close($conn);
        ob_end_flush();
        exit();
    }
    pg_free_result($result);
}

// Fetch updated user data
$sql = "SELECT f_name, l_name, email, m_number, profile_lockout_until FROM normalusers WHERE nuser_id = $1";
$stmt2 = pg_prepare($conn, "fetch_updated_user_data", $sql);
if (!$stmt2) {
    log_debug("Prepare failed (fetch updated user data): " . pg_last_error());
    echo json_encode(["success" => false, "message" => "Prepare failed: " . pg_last_error()]);
    pg_close($conn);
    ob_end_flush();
    exit();
}
$result = pg_execute($conn, "fetch_updated_user_data", array($nuser_id));
if (pg_num_rows($result) === 0) {
    log_debug("User not found (fetch updated user data): $nuser_id");
    echo json_encode(["success" => false, "message" => "User not found"]);
    pg_free_result($result);
    pg_close($conn);
    ob_end_flush();
    exit();
}
$row = pg_fetch_assoc($result);
pg_free_result($result);

echo json_encode([
    "success" => true,
    "message" => "Profile updated successfully",
    "firstName" => $row['f_name'],
    "lastName" => $row['l_name'],
    "email" => $row['email'],
    "phone" => $row['m_number'],
    "profile_lockout_until" => $row['profile_lockout_until']
]);

pg_close($conn);
ob_end_flush();
?>