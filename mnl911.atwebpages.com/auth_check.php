<?php
// backend/auth_check.php

function checkOfficerStatusAndExit($conn, $police_id) {
    if (!$police_id) {
        // This case should ideally be handled before calling this function,
        // but as a safeguard:
        echo json_encode([
            "success" => false, 
            "error" => "auth_failed", 
            "message" => "Authentication failed: User ID not provided."
        ]);
        $conn->close();
        exit();
    }

    $stmt = $conn->prepare("SELECT account_status FROM policeusers WHERE police_id = ?");
    $stmt->bind_param("i", $police_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['account_status'] !== 'P.Active') {
            // If not active, send an auth_failed error and exit
            echo json_encode([
                "success" => false, 
                "error" => "auth_failed", 
                "message" => "Your account access has been revoked. Please contact your administrator."
            ]);
            $stmt->close();
            $conn->close();
            exit();
        }
    } else {
        // If officer doesn't exist, treat as an auth failure
        echo json_encode([
            "success" => false, 
            "error" => "auth_failed", 
            "message" => "Invalid user."
        ]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();
}
?>
