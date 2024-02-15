<?php
// auth.php
// This script is required on all pages where an authenticated user is required.

@require_once(__DIR__ . "/functions.php");
@define("LOGIN_URL", WEBROOT . "/login.php");
@$header = "HTTP/1.1 401 Unauthorized";

// Start the session
@session_set_cookie_params(0, '/', '.hogwarts.wiz', true, false);
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

if (!isset($_SESSION["role"])) : ?>
    <?php require_once(FILEROOT . "/header.php"); ?>
    <!-- <div class="alert alert-danger text-center mb-0 rounded-0"> -->
    <div class="alert alert-danger bg-danger text-center text-white border-0 rounded-0 h6 p-0 px-5 py-1 mb-0">
        <h6 class="pt-2 fw-semibold">
            Your account has not been setup correctly. Please contact IT Services for further assistance.
        </h6>
        <p class="fw-lighter mb-2 fst-italic">
            Reason: User Role has not been defined for <?php echo $_SESSION["username"]; ?>
        </p>
    </div>
    <p>
    </p>
    <?php require_once(FILEROOT . "/footer.php"); ?>
<?php endif; ?>