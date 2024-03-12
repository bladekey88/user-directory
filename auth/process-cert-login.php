<?php
@require_once(dirname(__DIR__, 1) . "/config/functions.php");
session_start();

// Exit if already logged in
if (isset($_SESSION["userid"])) {
    redirect("/");
}


########################################################
################ BEGIN LOGIN PROCESSING ################ 
########################################################
@$conn = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

### Handle No Certificate ##
if ($_SERVER['SSL_CLIENT_VERIFY'] == 'NONE') {
    $_SESSION["error"] = "No certificate presented";
    redirect("/login.php");
}



// Process Presented Certificate
if ($_SERVER['SSL_CLIENT_VERIFY'] == 'SUCCESS') {
    $cert_validate = validate_client_certificate();
    if ($cert_validate["success"]) {
        $username = sanitise_user_input($_SERVER["SSL_CLIENT_S_DN_CN"]);
        $email = sanitise_user_input($_SERVER["SSL_CLIENT_S_DN_Email"]);
        $login_message = authenticateWithClientCertificate($conn, $username, $email);
    } else {
        $login_message = $cert_validate["reason"];
    }
}


########################################################
################ AUTH AND LOGIN FUNCTIONS ##############
########################################################
function authenticateWithClientCertificate($conn, $username, $email)
{
    $sql = "SELECT t1.userid, t1.username, t1.email,t1.commonname,t1.lastname,t1.idnumber,t1.locked,t1.hidden,t3.role_name,t4.*
    FROM users t1
    LEFT JOIN user_role t2 ON t1.userid = t2.user_id
    LEFT JOIN roles t3 ON t2.role_id = t3.role_id
    LEFT JOIN user_certificate t4 ON t1.userid = t4.user_id
    WHERE username = ? AND email = ? AND hidden IS NULL";
    $params = [$username, $email];
    $types = "ss";
    $result = executeQueryWithParams($conn, $sql, $params, $types);
    $row = $result->fetch_assoc();

    if ($row && strval($row["locked"]) == "0") {
        # If there is a registered certificate
        if ($row["certificate_serial"]) {
            if (
                $row["certificate_serial"] == $_SERVER['SSL_CLIENT_M_SERIAL'] &&
                $row["certificate_cn"] == $_SERVER['SSL_CLIENT_S_DN_CN'] &&
                $row["certificate_email"] == $_SERVER['SSL_CLIENT_S_DN_Email'] &&
                $row["enabled_by_user"] == 1 &&
                $row["certificate_end"] > date("Y-m-d H:i:s")
            ) {
                startSecureSession();
                storeUserInfoInSession($row);
                redirect("/");
            }
        }
    }
    $_SESSION["error"] =  "<p>The certificate is not valid or cannot be used for login.</p>
    <p>Select another certificate, or use username and password.</p>";
    redirect("/login.php");
}


function executeQueryWithParams($conn, $sql, $params, $types)
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);  //Use ... (splat) to bind params into separate arguments
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

function startSecureSession()
{
    session_destroy();
    session_set_cookie_params(3600, '/', '.hogwarts.wiz', true, true);
    session_start();
    session_regenerate_id(true);     // Regenerate session ID to prevent session fixation attacks
}

function storeUserInfoInSession($row)
{
    $_SESSION['userid'] = $row['userid'];
    $_SESSION['idnumber'] = $row['idnumber'];
    $_SESSION['username'] = $row['username'];
    $_SESSION["name"] = $row["commonname"] . " " . $row["lastname"];
    $_SESSION["role"] = $row["role_name"];
    $_SESSION["email"] = $row["email"];
    $_SESSION["login_method"] = "CLIENT_CERTIFICATE";
}
