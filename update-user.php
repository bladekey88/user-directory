<?php
require_once(__DIR__ . "/config/auth.php");

// Use for fallback 
$direct = (isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"] == "application/json") ? false : true;

// Permissions check
if (!check_user_permission(PERMISSION_EDIT_OWN_PROFILE) && !check_user_permission((PERMISSION_EDIT_ANY_PROFILE))) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

if (filter_var(check_current_user_can_edit_user($_POST["userid"]), FILTER_VALIDATE_BOOL) == false) {
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

if ($field === "firstname" && (strlen($value) == 0 || $value === null || !isset($value))) {
    array_push($errors, "'First Name' cannot be blank.");
}

if ($field === "lastname" && (strlen($value) == 0 || $value === null || !isset($value))) {
    array_push($errors, "'Last Name' cannot be blank.");
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


try {
    // Prepare the statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Statement preparation failed");
    }

    // Bind parameters
    $stmt->bind_param("si", $value, $userid);

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $conn->error);
    }
    // Success
    echo json_encode(['success' => 'Saved']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    // Close the statement (regardless of success or failure)
    if ($stmt) {
        $stmt->close();
    }

    $conn->close();
}
