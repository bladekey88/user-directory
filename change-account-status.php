<?php
require_once(__DIR__ . "/config/auth.php"); #

$messages = array();
$errors = array();
$action = $_GET["action"]  ??  "";

if (!isset($_GET["userid"]) || strlen($_GET["userid"] == "")) {
    sendErrorResponse(400, "Bad Request - User ID key not provided. Please contact support for further assistance.");
}

$editable = filter_var(check_current_user_can_edit_user($_GET["userid"]), FILTER_VALIDATE_BOOL) ?? "";

$action = sanitise_user_input($action);
$validActions = [
    'lock' => "locked",
    'unlock' => "unlocked",
    'hide' => "hidden"
];

// Check User Permissions
if (!$editable === True)
    sendErrorResponse(403, "Current user has insufficient privileges to modify specific account - editing account user role must be higher than the edited account.");

if (!check_user_permission(PERMISSION_UNLOCK_USER) && $action == "unlock")
    sendErrorResponse(403, "Forbidden");

if (!check_user_permission(PERMISSION_LOCK_USER)  && $action == "lock")
    sendErrorResponse(403, "Forbidden");

if (!check_user_permission(PERMISSION_HIDE_USER)  && $action == "hide")
    sendErrorResponse(403, "Forbidden");


// Check for missing username
if (!isset($_GET["user"]) || strlen($_GET["user"]) == 0) {
    handleMissingParameter("'User'");
}
// Check for Missing Action Parameter
if (!isset($action) || strlen($action) == 0) {
    handleMissingParameter("'Account status change' action");
}


// Check for Valid Action
if (!isValidAction($action, $validActions)) {
    sendErrorResponse(400, "Bad Request - invalid or unknown action '$action' provided. Please contact support for further assistance.");
} else {
    // Don't allow a user to hide their own account
    @$username = sanitise_user_input($_GET["user"], $type = "username");
    if ($action == "hide" && $username == $_SESSION["username"]) {
        sendErrorResponse(401, "Unauthorized - User cannot set their own account status to hidden.");
    }
    //No errors so proceed;    
    if ($action == "hide") {
        run_sql2(enable_user_hidden_status($username));
        run_sql2(toggle_user_lock_status($username, "lock"));
        if ($_SESSION["username"] == $username) session_destroy();
    } else {
        run_sql2(toggle_user_lock_status($username, $action));
    }
    echo json_encode(
        array(
            "message" => "Account has been updated successfully",
            "status" => $validActions[$action],
            "account" => "$username",
            "method" => $_SERVER["REQUEST_METHOD"]
        )
    );
}

// Function to Send Error Response
function sendErrorResponse($statusCode, $message)
{
    $message = $message;
    header("HTTP/1.1 $statusCode $message");
    @require_once(dirname(FILEROOT, 2) . "/htdocs/errordocs/$statusCode.php");
    exit();
}

// Function to Handle Missing Parameter
function handleMissingParameter($paramName)
{
    sendErrorResponse(400, "Bad Request - $paramName was not supplied. Please contact support for further assistance.");
}

function isValidAction($action, $actionsArray)
{
    return array_key_exists(strtolower($action), $actionsArray);
}
