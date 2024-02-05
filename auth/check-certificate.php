<?php
@require_once(dirname(__DIR__, 1) . "/config/auth.php");

function handle_error($error, $status = "400 Bad Request")
{
    $_SESSION["error"] = $error;
    $response = [
        'error' => $error,
    ];
    header("HTTP/1.1 $status");
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

//Set up error array
$error = array();

//Permissions check
if (isset($_POST["field"]) && $_POST["field"] == "enabled_by_user" && check_user_permission(PERMISSION_EDIT_OWN_PROFILE)) {
    $update_mode = true;
} else if (isset($_POST["deletecertificate"]) && $_POST["deletecertificate"] == "true") {
    $update_mode = true;
} else if (!check_user_permission(PERMISSION_EDIT_OWN_PROFILE) || (isset($_SERVER["SSL_CLIENT_S_DN_CN"]) && $_SESSION["username"] <> $_SERVER["SSL_CLIENT_S_DN_CN"])) {
    header("HTTP/1.1 403 Forbidden");
    array_push($error, "Certificate was not enroled - insufficient permissions or user/certificate mismatch");
    handle_error($error, "403 Forbidden");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid_cert = validate_client_certificate();
    if (!$valid_cert["success"]) {
        array_push($error, validate_client_certificate()["reason"]);
    }

    // Handle validation errors
    $error && handle_error($error);

    // Good to go
    $conn = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    //Get Fields from server
    $userid = $_SESSION["userid"];
    $serial = $_SERVER["SSL_CLIENT_M_SERIAL"];
    $cn = $_SERVER["SSL_CLIENT_S_DN_CN"];
    $email = $_SERVER["SSL_CLIENT_S_DN_Email"];
    $issuer = $_SERVER["SSL_CLIENT_I_DN"];
    $valid_from = DateTime::createFromFormat('M d H:i:s Y T', $_SERVER["SSL_CLIENT_V_START"]);
    $valid_from_formatted =  $valid_from->format('Y-m-d H:i:s');
    $valid_to = DateTime::createFromFormat('M d H:i:s Y T', $_SERVER["SSL_CLIENT_V_END"]);
    $valid_to_formatted =  $valid_to->format('Y-m-d H:i:s');
    $field = isset($_POST["field"]) && $_POST["field"] != "null" ? sanitise_user_input($_POST["field"]) : null;
    $value = isset($_POST["value"]) && $_POST["value"] != "null" ? sanitise_user_input($_POST["value"]) : null;
    $delete_certificate = isset($_POST["deletecertificate"]) && ($_POST["deletecertificate"] == "true") ? true : null;

    //Declare transaction type for the message
    define("LANG_SQL_UPDATED", "updated");
    define("LANG_SQL_ADDED", "added");
    define("LANG_SQL_DELETED", "deleted");

    if (!is_null($field) && !is_null($value)) {
        $sql = "UPDATE user_certificate 
        SET $field = '$value'
        WHERE user_id = '$userid';";
        $transaction_type_message = LANG_SQL_UPDATED;
    } else if (!check_certificate_exists($serial)) {
        $sql = "INSERT into user_certificate (user_id, certificate_serial, certificate_cn, certificate_email, certificate_client_issuer_dn, certificate_start, certificate_end)
    VALUES ('$userid', '$serial','$cn','$email','$issuer','$valid_from_formatted','$valid_to_formatted')";
        $transaction_type_message = LANG_SQL_ADDED;
    } else if ($delete_certificate == true) {
        $sql = "DELETE from user_certificate WHERE user_id = '$userid';";
        $transaction_type_message = LANG_SQL_DELETED;
    } else {
        $sql = "UPDATE user_certificate 
        SET  certificate_serial = '$serial',
         certificate_cn = '$cn',
         certificate_email = '$email',
         certificate_client_issuer_dn = '$issuer',
         certificate_start = '$valid_from_formatted',
         certificate_end = '$valid_to_formatted',
         enabled_by_user = '0'
         WHERE user_id = '$userid';";
        $transaction_type_message = LANG_SQL_UPDATED;
    }

    if ($conn->query($sql) === TRUE) {
        echo json_encode("Certificate details $transaction_type_message. Refresh the page to see the changes.");
    } else {
        echo json_encode("An error occurred" . $conn->error);
        // echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
