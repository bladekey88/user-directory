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

$roles = [
    6 => ROLE_NONE,
    5 => ROLE_PARENT,
    4 => ROLE_STUDENT,
    3 => ROLE_STAFF,
    2 => ROLE_SENIOR_STAFF,
    1 => ROLE_ADMIN
];

$userid = $_POST["userID"];
$new_role_id = intval($_POST["userRole"]);
$new_role_name = $roles[$new_role_id];
$current_role_id = intval(run_sql2(get_user_role($userid))[0]["role_id"]);
$current_role_name = $roles[$current_role_id];
$actioning_user_role_id = run_sql2(get_user_role($_SESSION["userid"]))[0]["role_id"];

// Don't allow a "NONE" role
if ($new_role_name == ROLE_NONE) {
    array_push($errors, "The new role cannot be set to 'None'.");
}

// Don't allow the superadmin's role to be changed (userid:1)
if ($userid == 1) {
    array_push($errors, "For security reasons the role for this user cannot be changed.");
}

// Don't allow non-admins to change to admin role
if ($new_role_name == ROLE_ADMIN && !check_user_role(ROLE_ADMIN)) {
    array_push($errors, "You do not have permission to change the user's role to 'Administrator'.");
}


// Only allow non-admins to change roles for roles below them
if (($current_role_id >=  $actioning_user_role_id) && !check_user_role(ROLE_ADMIN)) {
    array_push($errors, "You cannot change the role of a user that has the same or higher role.");
} else if
// Only allow user with valid permissions to change to a role lower than their own (except for admins)
($new_role_id >= $actioning_user_role_id && !check_user_role(ROLE_ADMIN)) {
    array_push($errors, "You cannot change the user to a role that is the same or greater than your role.");
}



// Check user's current role is not the same as the new role
if ($current_role_id == $new_role_id) {
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
$update_row = run_sql2(update_user_role($userid, $new_role_id));
echo json_encode(
    array(
        "message" => "Role updated - refresh the page to see the changes.",
    )
);
