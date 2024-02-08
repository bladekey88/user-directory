<?php
require_once(__DIR__ . "/config/auth.php");

function handle_error($error, $response_code)
{
    $response = [
        'error' => $error,
    ];
    header("HTTP/1.1 $response_code");
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$error = array();

// Permissions Check
if (!check_user_permission(PERMISSION_EDIT_OWN_PROFILE)) {
    array_push($error, LANG_INSUFFICIENT_PRIVILEGES);
    $error && handle_error($error, "403 Forbidden");
}

// Connect to HMAIL COM
try {
    // Create COM Instance and connect to it
    $obBaseApp = new COM("hMailServer.Application", NULL, CP_UTF8);
    $obBaseApp->Connect();
    $login = $obBaseApp->Authenticate(HMAIL_ADMIN_USER, HMAIL_ADMIN_PW);

    // Handle Authentication Failure (returns null on failure, object on success)
    if (!$login) {
        array_push($error, "Authentication failed. Please contact IT Services.");
        $error && handle_error($error, "403 Forbidden");
        throw new Exception("Authentication failed. Please contact IT Services.");
    }
    $domain = $obBaseApp->Domains->ItemByName(HMAIL_DOMAIN);

    // Use a temporary email address until approved
    $actual_email_address = $_SESSION["username"] . "@" . HMAIL_DOMAIN;
    $temporary_email_address = "ZZ_PENDING_" . $_SESSION["username"] . "@" . HMAIL_DOMAIN;

    // Check if the actual email exists (idempotency)
    try {
        $domain->Accounts->ItemByAddress($actual_email_address);
        array_push($error, "Email account already exists.");
        $error && handle_error($error, "302 Found");
    } catch (Exception $e) {
        $e;
    }

    try {
        $domain->Accounts->ItemByAddress($temporary_email_address);
        array_push($error, "Account request has already been created.");
        $error && handle_error($error, "302 Found");
    } catch (Exception $e) {
        $account = $domain->Accounts->Add();
        echo "<pre>";
        print_r($_SESSION);
        print_r(com_print_typeinfo($account));
        echo "</pre>";
        $account->Address = $temporary_email_address;
        $account->Password = HMAIL_ADMIN_PW;
        $account->Active = 0; // Inactive
        $account->PersonFirstName = explode(" ", $_SESSION["name"])[0];
        $account->PersonLastName = explode(" ", $_SESSION["name"])[1];
        $account->Save();
    }
} catch (Exception $e) {
    // Handle Exception
    $error_message = $e->getMessage();
    array_push($error, $error_message);
    $error && handle_error($error, "400 Bad Request");
}
