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
if ($_POST["editable"] === "false") {
    // header("HTTP/1.1 403 Forbidden");
    array_push($error, "Certificate was not enrolled due to insufficient permissions");
    handle_error($error, "403 Forbidden");
}


if (isset($_POST["field"]) && $_POST["field"] == "enabled_by_user" && check_user_permission(PERMISSION_EDIT_OWN_PROFILE)) {
    $update_mode = "login-toggle";
} else if (isset($_POST["deletecertificate"]) && $_POST["deletecertificate"] == "true") {
    $update_mode = "delete-certificate";
} else if (!check_user_permission(PERMISSION_EDIT_OWN_PROFILE) || (isset($_SERVER["SSL_CLIENT_S_DN_CN"]) && $_SESSION["username"] <> $_SERVER["SSL_CLIENT_S_DN_CN"])) {
    header("HTTP/1.1 403 Forbidden");
    array_push($error, "Certificate was not enrolled - insufficient permissions or user/certificate mismatch");
    handle_error($error, "403 Forbidden");
}


if (!isset($update_mode)) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $valid_cert = validate_client_certificate();
        if (!$valid_cert["success"]) {
            array_push($error, $valid_cert["reason"]);
        }
    }
}


// Handle validation errors
$error && handle_error($error);

// Good to go
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
if ($mysqli->connect_error) {
    die("Database Connection could not be established: " . $conn->connect_error);
}

//Get POST Information
$userid = sanitise_user_input($_POST["userid"]);
$field = isset($_POST["field"]) && $_POST["field"] != "null" ? sanitise_user_input($_POST["field"]) : null;
$value = isset($_POST["value"]) && $_POST["value"] != "null" ? sanitise_user_input($_POST["value"]) : null;
$delete_certificate = isset($_POST["deletecertificate"]) && ($_POST["deletecertificate"] == "true") ? true : null;
$toggle_certificate = isset($field) && $field == "enabled_by_user" ? true : null;

// If  doing a deletion no need to check any details
// Same for toggling status
if ($delete_certificate == true) {
    $stmt = $mysqli->prepare("DELETE FROM user_certificate WHERE user_id = ?");
    $stmt->bind_param("s", $userid);
    $transaction_type_message = LANG_SQL_DELETED;
} else if ($toggle_certificate == true) {
    $stmt = $mysqli->prepare("UPDATE user_certificate SET $field = ? WHERE user_id = ?");
    $stmt->bind_param("ss", $value, $userid);
    $transaction_type_message = LANG_SQL_UPDATED;
} else {
    $serial = $_SERVER["SSL_CLIENT_M_SERIAL"];
    $cn = $_SERVER["SSL_CLIENT_S_DN_CN"];
    $email = $_SERVER["SSL_CLIENT_S_DN_Email"];
    $issuer = $_SERVER["SSL_CLIENT_I_DN"];
    $valid_from = DateTime::createFromFormat('M d H:i:s Y T', $_SERVER["SSL_CLIENT_V_START"]);
    $valid_from_formatted =  $valid_from->format('Y-m-d H:i:s');
    $valid_to = DateTime::createFromFormat('M d H:i:s Y T', $_SERVER["SSL_CLIENT_V_END"]);
    $valid_to_formatted =  $valid_to->format('Y-m-d H:i:s');

    // New Certificate Enrolment
    if (!check_certificate_exists($serial)) {
        $stmt = $mysqli->prepare("INSERT INTO user_certificate (user_id, certificate_serial, certificate_cn, certificate_email, certificate_client_issuer_dn, certificate_start, certificate_end) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $userid, $serial, $cn, $email, $issuer, $valid_from_formatted, $valid_to_formatted);
        $transaction_type_message = LANG_SQL_ADDED;
    } else {
        $cert_status = 0;
        // Used for enrolling a new certificate where one already exists
        $stmt = $mysqli->prepare("UPDATE user_certificate SET certificate_serial = ?, certificate_cn = ?, certificate_email = ?, certificate_client_issuer_dn = ?, certificate_start = ?, certificate_end = ?, enabled_by_user = ? WHERE user_id = ?");
        $stmt->bind_param("ssssssss", $serial, $cn, $email, $issuer, $valid_from_formatted, $valid_to_formatted, $cert_status, $userid);
        $transaction_type_message = LANG_SQL_UPDATED;
    }
}

if ($stmt->execute()) {
    echo json_encode("Certificate details $transaction_type_message. Refresh the page to see the changes.");
} else {
    echo json_encode("An error occurred" . $stmt->error);
}

$stmt->close();
