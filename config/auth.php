<?php
// auth.php
// This script is required on all pages where an authenticated user is required.

@require_once(__DIR__ . "/functions.php");
@define("LOGIN_URL", WEBROOT . "/login.php");
@$header = "HTTP/1.1 401 Unauthorized";

// Start the session
@session_set_cookie_params(0, '/', '.hogwarts.wiz', true, true);
@session_start();

// Check if the user is not authenticated
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    // Redirect to the login page
    redirect(LOGIN_URL, $header);
}

if ($_SESSION["login_method"] == "CLIENT_CERTIFICATE" && $_SESSION["username"] != $_SERVER["SSL_CLIENT_S_DN_CN"]) {
    session_destroy();
    redirect(LOGIN_URL, $header);
}

// Update session to always be 20 minutes from last activity
setcookie("PHPSESSID", $_COOKIE["PHPSESSID"], time() + 1200, "/", '.hogwarts.wiz', 1, 1);


if (!isset($_SESSION["role"]) || strtoupper($_SESSION["role"]) == ROLE_NONE) : ?>
    <?php require_once(FILEROOT . "/header.php"); ?>
    <div class="alert alert-danger bg-danger text-center text-white border-0 border-4 border-top border-bottom border-black rounded-0 p-0 mb-0 mt-0">
        <h6 class="pt-2 fw-lighter">
            Your account has not been setup correctly - contact IT Services for further assistance.
        </h6>
        <pre class="fw-lighter">Reason: Role has not been defined or is set to 'None' for: <?php echo $_SESSION["username"]; ?> (<?php echo $_SESSION["idnumber"]; ?>)</pre>
    </div>
    <?php require_once(FILEROOT . "/footer.php"); ?>
<?php endif; ?>