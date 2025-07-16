<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "fdb1028.awardspace.net";
$user = "4642576_crimemap";
$password = "@CrimeMap_911";
$dbname = "4642576_crimemap";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $police_id = $_POST['police_id'] ?? null;
    $expo_push_token = $_POST['expo_push_token'] ?? null;
    
    if (!$police_id || !$expo_push_token) {
        echo json_encode(['success' => false, 'error' => 'Police ID and push token are required']);
        exit();
    }
    
    $conn = new mysqli($host, $user, $password, $dbname);
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }
    
    // Update the police user's push token
    $sql = "UPDATE policeusers SET expoPushToken = ? WHERE police_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $expo_push_token, $police_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Push token registered successfully',
            'police_id' => $police_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to register push token']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => 'POST request required']);
}
?>