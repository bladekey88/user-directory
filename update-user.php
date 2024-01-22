<?php
require_once(__DIR__ . "/config/auth.php");

//Use for fallback 
$direct = (isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"] == "application/json") ? false : true;

// Permissions check
if (!check_user_permission(PERMISSION_EDIT_OWN_PROFILE) && !check_user_permission((PERMISSION_EDIT_ANY_PROFILE))) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

// Set up error array
$errors = array();

// Sanitise User data
$field = isset($_POST["field"]) ? sanitise_user_input($_POST["field"]) : null;
$value = isset($_POST["value"]) ? sanitise_user_input($_POST["value"]) : null;
$userid = isset($_POST["userid"]) ? sanitise_user_input($_POST["userid"], "numeric") : null;

// Mandatory validation for allowable editable fields
if ($field === "commonname" && (strlen($value) == 0 || $value === null || !isset($value))) {
    array_push($errors, "'Preferred Name/Title' cannot be blank.");
}

// Check for errors and call the error function
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => $errors]);
    exit();
}

// If no errors, then proceed to update
$sql = "UPDATE users SET $field = ? WHERE userid = ?";

$conn = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("si", $value, $userid);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Saved']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Statement preparation failed']);
}

// Close the database connection
$conn->close();
