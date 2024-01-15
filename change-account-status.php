<?php

require_once("./assets/config/functions.php");
require_once("./assets/config/auth.php");

$messages = array();
$errors = array();

$action = $_GET["action"]  ??  "";
$action = sanitise_user_input($action);

// Check User Permissions
if (!check_user_permission(PERMISSION_LOCK_USER) && !check_user_permission(PERMISSION_UNLOCK_USER)) {
    sendErrorResponse(403, "Forbidden");
} else if (!check_user_permission(PERMISSION_UNLOCK_USER) && $action == "unlock") {
    sendErrorResponse(403, "Forbidden");
} else if (!check_user_permission(PERMISSION_LOCK_USER)  && $action == "lock") {
    sendErrorResponse(403, "Forbidden");
}

// Check for missing username
if (!isset($_GET["user"]) || strlen($_GET["user"]) == 0) {
    handleMissingParameter("'User'");
}
// Check for Missing Action Parameter
elseif (!isset($action) || strlen($action) == 0) {
    handleMissingParameter("'Account status change' action");
}
// Check for Valid Action
else if (!isValidAction($action)) {
    sendErrorResponse(400, "Bad Request");
} else {
    //No errors so proceed;
    $username = sanitise_user_input($_GET["user"], $type = "username");
    $action = sanitise_user_input($action);
    $toggle_lock_status = run_sql(toggle_user_lock_status($username, $action));
    echo json_encode(
        array(
            "message" => "Account has been updated successfully",
            "status" => $action . "ed",
            "account" => "$username"
        )
    );
}


// Function to Send Error Response
function sendErrorResponse($statusCode, $message)
{
    header("HTTP/1.1 $statusCode $message");
    @require_once($_SERVER['DOCUMENT_ROOT'] . "/errordocs/$statusCode.php");
    exit();
}

// Function to Handle Missing Parameter
function handleMissingParameter($paramName)
{
    global $errors, $messages;
    array_push($errors, "$paramName was not supplied.");
    $messages["message"] = "Account status could not be changed";
    $messages["error"] = $errors;
    echo json_encode($messages);
    exit();
}

// Function to Check Valid Action
function isValidAction($action)
{
    return strtolower($action) == "lock" || strtolower($action) == "unlock";
}
