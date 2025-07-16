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
$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

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

// Connect to database
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    log_debug("DB connection failed: " . $conn->connect_error);
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    ob_end_flush();
    exit();
}

// Fetch profile lockout info
$sql = "SELECT profile_failed_attempts, profile_lockout_until FROM normalusers WHERE nuser_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    log_debug("Prepare failed: " . $conn->error);
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    $conn->close();
    ob_end_flush();
    exit();
}
$stmt->bind_param("i", $nuser_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    log_debug("User not found: $nuser_id");
    echo json_encode(["success" => false, "message" => "User not found"]);
    $stmt->close();
    $conn->close();
    ob_end_flush();
    exit();
}
$stmt->bind_result($profile_failed_attempts, $profile_lockout_until);
$stmt->fetch();
$stmt->close();

$current_time = round(microtime(true) * 1000);

// --- Reset lockout if expired ---
if ($profile_lockout_until && $current_time >= $profile_lockout_until) {
    $sql_reset = "UPDATE normalusers SET profile_failed_attempts = 0, profile_lockout_until = NULL WHERE nuser_id = ?";
    $stmt_reset = $conn->prepare($sql_reset);
    $stmt_reset->bind_param("i", $nuser_id);
    $stmt_reset->execute();
    $stmt_reset->close();
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
    $conn->close();
    ob_end_flush();
    exit();
}

if ($currentPassword && !$newPassword) {
    echo json_encode(["success" => false, "message" => "Please enter a new password to change your password."]);
    $conn->close();
    ob_end_flush();
    exit();
}

if ($newPassword) {
    // Verify current password
    $sql = "SELECT password FROM normalusers WHERE nuser_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        log_debug("Prepare failed (password check): " . $conn->error);
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        $conn->close();
        ob_end_flush();
        exit();
    }
    $stmt->bind_param("i", $nuser_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        log_debug("User not found (password check): $nuser_id");
        echo json_encode(["success" => false, "message" => "User not found"]);
        $stmt->close();
        $conn->close();
        ob_end_flush();
        exit();
    }
    $stmt->bind_result($hashed_password_db);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($currentPassword, $hashed_password_db)) {
        $profile_failed_attempts++;
        $new_profile_lockout = null;
        if ($profile_failed_attempts == 5) {
            $new_profile_lockout = $current_time + 5 * 60 * 1000;
        } elseif ($profile_failed_attempts == 10) {
            $new_profile_lockout = $current_time + 30 * 60 * 1000;
        }

        // Update lockout
        if ($new_profile_lockout === null) {
            $sql_update = "UPDATE normalusers SET profile_failed_attempts = ?, profile_lockout_until = NULL WHERE nuser_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $profile_failed_attempts, $nuser_id);
        } else {
            $sql_update = "UPDATE normalusers SET profile_failed_attempts = ?, profile_lockout_until = ? WHERE nuser_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("iii", $profile_failed_attempts, $new_profile_lockout, $nuser_id);
        }
        $stmt_update->execute();
        $stmt_update->close();
        $msg = "The current password you entered is incorrect.";
        echo json_encode([
            "success" => false,
            "message" => $msg,
            "lockout" => !!$new_profile_lockout,
            "profile_lockout_until" => $new_profile_lockout ?? null
        ]);
        $conn->close();
        ob_end_flush();
        exit();
    }

    // Valid password, update with new password and reset profile lockout
    $hashed_new_password = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE normalusers SET f_name = ?, l_name = ?, email = ?, m_number = ?, password = ?, profile_failed_attempts = 0, profile_lockout_until = NULL WHERE nuser_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        log_debug("Prepare failed (update with password): " . $conn->error);
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        $conn->close();
        ob_end_flush();
        exit();
    }
    $stmt->bind_param("sssssi", $f_name, $l_name, $email, $phone, $hashed_new_password, $nuser_id);
} else {
    //  No password change, reset lockout and attempts
    $sql = "UPDATE normalusers SET f_name = ?, l_name = ?, email = ?, m_number = ?, profile_failed_attempts = 0, profile_lockout_until = NULL WHERE nuser_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        log_debug("Prepare failed (update no password): " . $conn->error);
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        $conn->close();
        ob_end_flush();
        exit();
    }
    $stmt->bind_param("ssssi", $f_name, $l_name, $email, $phone, $nuser_id);
}

// Execute final update
if ($stmt->execute()) {
    log_debug("Profile updated for user $nuser_id");
    // Fetch updated user data
    $sql = "SELECT f_name, l_name, email, m_number, profile_lockout_until FROM normalusers WHERE nuser_id = ?";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("i", $nuser_id);
    $stmt2->execute();
    $stmt2->bind_result($f_name_db, $l_name_db, $email_db, $m_number_db, $profile_lockout_until_db);
    $stmt2->fetch();
    $stmt2->close();

    echo json_encode([
        "success" => true,
        "message" => "Profile updated successfully",
        "firstName" => $f_name_db,
        "lastName" => $l_name_db,
        "email" => $email_db,
        "phone" => $m_number_db,
        "profile_lockout_until" => $profile_lockout_until_db
    ]);
} else {
    log_debug("Update failed for user $nuser_id: " . $stmt->error);
    echo json_encode(["success" => false, "message" => "Update failed. Please try again."]);
}

$stmt->close();
$conn->close();
ob_end_flush();
?>