<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// Database credentials
$dsn = 'postgresql://postgres:[09123433140aa]@db.uyqspojnegjmxnedbtph.supabase.co:5432/postgres';
$conn = pg_connect($dsn);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . pg_last_error()]);
    exit();
}

function handle_lockout($conn, $table, $email, $row, $user_type, $success_data) {
    $failed_attempts = $row['failed_attempts'];
    $lockout_until = $row['lockout_until'];
    $current_time = round(microtime(true) * 1000);

    // Check lockout
    if ($lockout_until && $current_time < $lockout_until) {
        $remaining = $lockout_until - $current_time;
        $min = floor($remaining / 60000);
        $sec = floor(($remaining % 60000) / 1000);
        $msg = " ";
        if ($min > 0) $msg .= "$min minute(s)";
        if ($min > 0 && $sec > 0) $msg .= " and ";
        if ($sec > 0) $msg .= "$sec second(s)";
        $msg .= ".";
        echo json_encode([
            "success" => false,
            "message" => $msg,
            "lockout" => true,
            "lockout_until" => $lockout_until
        ]);
        pg_close($conn);
        exit();
    }

    // Password check
    if (password_verify($_POST['password'], $row['password'])) {
        // Reset failed_attempts and lockout
        $sql = "UPDATE $table SET failed_attempts = 0, lockout_until = NULL WHERE email = ?";
        $stmt = pg_prepare($conn, "reset_lockout", $sql);
        $params = [$email];
        $result = pg_execute($conn, "reset_lockout", $params);
        if ($result) {
            echo json_encode($success_data);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to reset lockout: " . pg_last_error()]);
        }
        pg_close($conn);
        exit();
    } else {
        // Increment failed_attempts and set lockout if needed
        $failed_attempts++;
        $new_lockout = null;
        if ($failed_attempts == 5) {
            $new_lockout = $current_time + 5 * 60 * 1000;
        } elseif ($failed_attempts == 10) {
            $new_lockout = $current_time + 30 * 60 * 1000;
        }
        if ($new_lockout === null) {
            $sql_update = "UPDATE $table SET failed_attempts = ?, lockout_until = NULL WHERE email = ?";
            $stmt_update = pg_prepare($conn, "update_normal_user", $sql_update);
            $params_update = [$failed_attempts, $email];
            $result_update = pg_execute($conn, "update_normal_user", $params_update);
        } else {
            $sql_update = "UPDATE $table SET failed_attempts = ?, lockout_until = ? WHERE email = ?";
            $stmt_update = pg_prepare($conn, "update_normal_user_with_lockout", $sql_update);
            $params_update = [$failed_attempts, $new_lockout, $email];
            $result_update = pg_execute($conn, "update_normal_user_with_lockout", $params_update);
        }
        if ($result_update) {
            $msg = "Invalid credentials.";
            if ($new_lockout) {
                $min = floor(($new_lockout - $current_time) / 60000);
                $sec = floor((($new_lockout - $current_time) % 60000) / 1000);
                $msg = "";
                if ($min > 0) $msg .= "$min minute(s)";
                if ($min > 0 && $sec > 0) $msg .= " and ";
                if ($sec > 0) $msg .= "$sec second(s)";
                $msg .= ".";
                echo json_encode([
                    "success" => false,
                    "message" => $msg,
                    "lockout" => true,
                    "lockout_until" => $new_lockout
                ]);
                pg_close($conn);
                exit();
            }
            echo json_encode([
                "success" => false,
                "message" => $msg,
                "lockout" => false
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update failed attempts: " . pg_last_error()]);
        }
        pg_close($conn);
        exit();
    }
}

// Try normal user first
$sql = "SELECT nuser_id, f_name, l_name, m_number, email, password, failed_attempts, lockout_until, account_status, termination_reason FROM normalusers WHERE email = ?";
$stmt = pg_prepare($conn, "select_normal_user", $sql);
$params = [$email];
$result = pg_execute($conn, "select_normal_user", $params);

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    // Check account status for normal users
    if ($row['account_status'] === 'Terminated') {
        $reason = $row['termination_reason'] ? "Reason: " . $row['termination_reason'] : "Please contact support for more information.";
        echo json_encode(["success" => false, "message" => "Your account has been terminated. " . $reason]);
        pg_close($conn);
        exit();
    }
    
    handle_lockout($conn, "normalusers", $email, $row, "regular", [
        "success" => true,
        "user_type" => "regular",
        "nuser_id" => $row['nuser_id'],
        "first_name" => $row['f_name'],
        "last_name" => $row['l_name'],
        "email" => $row['email'],
        "phone" => $row['m_number'],
        "message" => "Login successful"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid credentials"]);
}
pg_close($conn);

// Try police user
$sql = "SELECT police_id, f_name, l_name, m_number, email, password, badge_number, station_name, failed_attempts, lockout_until, account_status, suspension_end_date, termination_reason FROM policeusers WHERE email = ?";
$stmt = pg_prepare($conn, "select_police_user", $sql);
$params = [$email];
$result = pg_execute($conn, "select_police_user", $params);

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    // Check account_status before lockout logic
    $status = $row['account_status'];
    $suspension_end = $row['suspension_end_date'];
    if ($status !== 'P.Active') {
        if ($status === 'P.Verification') {
            echo json_encode(["success" => false, "message" => "You need to ask your I.T Officer to verify your status."]);
            pg_close($conn);
            exit();
        } elseif ($status === 'P.Suspended') {
            // Check if suspension expired
            if ($suspension_end && strtotime($suspension_end) < time()) {
                // Auto-reactivate
                $update = pg_prepare($conn, "reactivate_police_user", "UPDATE policeusers SET account_status = 'P.Active', suspension_end_date = NULL WHERE email = ?");
                $params_update = [$email];
                $result_update = pg_execute($conn, "reactivate_police_user", $params_update);
                if ($result_update) {
                    $row['account_status'] = 'P.Active';
                } else {
                    echo json_encode(["success" => false, "message" => "Failed to reactivate police user: " . pg_last_error()]);
                    pg_close($conn);
                    exit();
                }
            } else {
                $reason = $row['suspension_reason'] ? "Reason: " . $row['suspension_reason'] : "";
                $msg = "Your account is suspended. " . $reason;
                if ($suspension_end) {
                    $msg .= " Suspension ends: " . date("F j, Y, g:i a", strtotime($suspension_end));
                }
                echo json_encode(["success" => false, "message" => $msg]);
                pg_close($conn);
                exit();
            }
        } elseif ($status === 'P.Terminated') {
            $reason = $row['termination_reason'] ? "Reason: " . $row['termination_reason'] : "Please contact your administrator for more information.";
            echo json_encode(["success" => false, "message" => "Your account has been terminated. " . $reason]);
            pg_close($conn);
            exit();
        }
    }
    handle_lockout($conn, "policeusers", $email, $row, "police", [
        "success" => true,
        "user_type" => "police",
        "police_id" => $row['police_id'],
        "first_name" => $row['f_name'],
        "last_name" => $row['l_name'],
        "email" => $row['email'],
        "phone" => $row['m_number'],
        "badge_number" => $row['badge_number'],
        "station_name" => $row['station_name'],
        "message" => "Login successful"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid credentials"]);
}
pg_close($conn);
?>