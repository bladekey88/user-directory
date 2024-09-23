<!-- TODO Refactor SQL to standard SQL File -->
<!-- TODO Refactor functions to either global function file or auth function file -->

<?php
require_once(__DIR__ . "/config/functions.php");
session_start();
if (isset($_SESSION["userid"])) {
    redirect("/");
}

########################################################
################ AUTH AND LOGIN FUNCTIONS ##############
########################################################
function executeQueryWithParams($conn, $sql, $params, $types)
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);  //Use ... (splat) to bind params into separate arguments
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

function authenticateWithUsernameAndPassword($conn, $username, $password)
{
    $sql = "SELECT t1.userid, t1.username, t1.email, t1.password, t1.firstname, t1.commonname, t1.lastname, t1.idnumber, t1.locked, t1.hidden, t3.role_name
    FROM users t1
    LEFT JOIN user_role t2 ON t1.userid = t2.user_id
    LEFT JOIN roles t3 ON t2.role_id = t3.role_id
    WHERE username = ? and hidden is null";
    $params = [$username];
    $types = "s";
    $result = executeQueryWithParams($conn, $sql, $params, $types);
    $row = $result->fetch_assoc();

    if ($row) {
        if (password_verify($password, $row['password']) && strval($row["locked"]) == "0") {
            startSecureSession();
            storeUserInfoInSession($row);
            redirect("/");
        } else {
            return "Invalid credentials or account is locked";
        }
    } else {
        return "Invalid username or password";
    }
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
    $_SESSION["firstname"] = $row["firstname"];
    $_SESSION["lastname"] = $row["lastname"];
    $_SESSION["commonname"] = $row["commonname"];
    $_SESSION["name"] = $row["commonname"] . " " . $row["lastname"];
    $_SESSION["role"] = $row["role_name"];
    $_SESSION["email"] = $row["email"];
    $_SESSION["login_method"] = isset($row['password']) ? "AUTHENTICATION" : "CLIENT_CERTIFICATE";
}

########################################################
################ BEGIN LOGIN PROCESSING ################ 
########################################################
// Create connection
@$conn = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = array();
$username = null;
if (isset($_SESSION["error"])) {
    array_push($errors, $_SESSION["error"]);
    unset($_SESSION["error"]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // First check for Standard Authentication (UN + PW)

    // Validations
    $username = isset($_POST["username"]) ? sanitise_user_input($_POST["username"]) : null;
    $password = isset($_POST["password"]) ? sanitise_user_input($_POST["password"]) : null;

    if (empty($username)) {
        array_push($errors, "Username is required");
    }
    if (empty($password)) {
        array_push($errors, "Password is required");
    }

    // If both username and password are provided, attempt login
    if (!$errors) {
        array_push($errors, authenticateWithUsernameAndPassword($conn, $username, $password));
    }
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Test</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <!-- <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> -->
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="preload"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Murecho:wght@100..900&display=swap" rel="preload" as="style">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Murecho:wght@100..900&display=swap" rel="stylesheet">
</head>

<body>
    <div class="login-box">
        <div class="login-form">
            <div class="login-form__logo">
                <img src="assets\img\logo-crest.png" alt="House Logo">
                <h1 class="logo-text">
                    Hogwarts Directory
                </h1>
            </div>
            <div class="login-content">
                <h1 class="login-title">
                    Log In Using Username and Password
                </h1>
                <form id="loginFormUNPW" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div id="login-input">
                        <label for="inputUsername">Username</label>
                        <input type="text" name="username" id="inputUsername" required maxlength="30">
                        <label for="inputPassword">Password</label>
                        <input type="password" name="password" id="inputPassword" required maxlength="64">
                    </div>
                    <div id="loginMessages">
                        <?php if (isset($errors)) : ?>
                            <?php foreach ($errors as $error): ?>
                                <div class="login-error"><?php echo $error; ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <input id="submitButton" type="submit" value="Log In" aria-label="Log in">
                </form>
                <div id="forgottenPassword">
                    <a href="/support/front/helpdesk.php" target="_blank">Forgotten Password</a>
                </div>
            </div>
        </div>
        <div class="login-cert">
            <h1 class="login-title">
                <a href="auth/process-cert-login.php" class="login-title">Log In Using Certificate</a>
            </h1>
        </div>
    </div>
</body>

<script>
    function setBGImage() {

        const documentBody = document.body;
        const prefersDarkMode = window.matchMedia("(prefers-color-scheme:dark)").matches
        let currentTime = new Date().getHours();
        if (7 <= currentTime && currentTime < 20 && !prefersDarkMode) {
            document.body.classList.add("day");
        } else {
            document.body.classList.add("night");
        }
    }

    setBGImage();
</script>

</html>