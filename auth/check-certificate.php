<?php
@require_once($_SERVER["DOCUMENT_ROOT"] . "/directory/assets/config/functions.php");
@require_once($_SERVER["DOCUMENT_ROOT"] . "/directory/assets/config/auth.php");

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
if (!check_user_permission(PERMISSION_EDIT_OWN_PROFILE) || (isset($_SERVER["SSL_CLIENT_S_DN_CN"]) && $_SESSION["username"] <> $_SERVER["SSL_CLIENT_S_DN_CN"])) {
    header("HTTP/1.1 403 Forbidden");
    array_push($error, "You do not have permission to continue enrolment - insufficient permissions or user mismatch");
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


    if (!check_certificate_exists($serial)) {
        $sql = "INSERT into user_certificate (user_id, certificate_serial, certificate_cn, certificate_email, certificate_client_issuer_dn, certificate_start, certificate_end)
    VALUES ('$userid', '$serial','$cn','$email','$issuer','$valid_from_formatted','$valid_to_formatted')";
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
    }

    if ($conn->query($sql) === TRUE) {
        echo json_encode("Certificate added successfully");
    } else {
        echo json_encode("An error occurred" . $conn->error);
        // echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
