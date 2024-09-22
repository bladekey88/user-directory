<?php

require_once(__DIR__ . "/config/auth.php");

// Set up arrays
$errors = array();
$messages = array();

if (!isset($_POST["userID"])) {
    array_push($errors, "UserID is required to use this functionality.");
}

if (!isset($_POST["userRole"])) {
    array_push($errors, "A role must be selected");
}

// Check for errors and call the error function
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => $errors]);
    exit();
}

$userid = $_POST["userID"];
$new_role = $_POST["userRole"];

// Don't allow a "NONE" role
if ($new_role == 0) {
    array_push($errors, "The new role cannot be set to 'None'.");
}

// Don't allow non-admins to change to admin role
if ($new_role == 1 && !check_user_role(ROLE_ADMIN)) {
    array_push($errors, "You do not have permission to change the user's role to 'Administrator'.");
}

// Don't allow the superadmin's role to be changed (userid:1)
if ($userid == 1) {
    array_push($errors, "For Security Reasons the role for this user cannot be changed.");
}

// Check user's current role
$current_role = run_sql2(get_user_role($userid));
if ($current_role[0]["role_id"] == $new_role) {
    array_push($errors, "New role and existing role are the same.");
}

// If there are errors than tell the user and exit the script - no db commits
if (!empty($errors)) {
    $messages["message"] = "Upload Failed - One or more errors have occurred.";
    $messages["error"] = $errors;
    echo json_encode($messages);
    exit();
}

// Good to go
$update_row = run_sql2(update_user_role($userid, $new_role));
echo json_encode(
    array(
        "message" => "Role updated - refresh the page to see the changes.",
    )
);
