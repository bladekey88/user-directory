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
            return "Invalid username, password, or permissions configuration";
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
$username_error = null;
$password_error = null;
$username = null;
$login_message = $_SESSION["error"] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // First check for Standard Authentication (UN + PW)

    // Validations
    $username = isset($_POST["username"]) ? sanitise_user_input($_POST["username"]) : null;
    $password = isset($_POST["password"]) ? sanitise_user_input($_POST["password"]) : null;
    $username_error = empty($username) ? "Username is required" : null;
    $password_error = empty($password) ? "Password is required" : null;

    // If both username and password are provided, attempt login
    if (!$username_error && !$password_error) {
        $login_message = authenticateWithUsernameAndPassword($conn, $username, $password);
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
    <title>Login Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url("assets/img//bg.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .login-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            flex-basis: 50%;
        }

        @media (width >=1024px) {
            .login-container {
                flex-basis: 33%;
            }
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }

        input {
            width: calc(100%);
            padding: 8px;
            margin-bottom: 16px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #4caf50;
            color: #fff;
            cursor: pointer;
        }

        .certificate-button {
            display: block;
            background-color: #2874A6;
            padding: 0.5rem;
            margin-block: 1rem;
            font-size: 0.85rem;
            text-align: center;
            box-sizing: border-box;
            color: white;
            cursor: pointer;
            width: calc(100%);
            text-decoration: none;
            border: solid 1px black
        }

        .error-message {
            color: red;
            margin-top: 8px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <h2>Login</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="username">Username:</label>
            <input type="text" name="username" autocomplete="username" id="username" value="<?php echo $username; ?>">

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" autocomplete="current-password">

            <input type="submit" value="Login">
        </form>
        <div>
            <hr>
            <a href="auth/process-cert-login.php" role="button" class="certificate-button">
                Certificate Login
            </a>
        </div>
        <div class="error-message"><?php echo $username_error; ?></div>
        <div class="error-message"><?php echo $password_error; ?></div>
        <div class="error-message"><?php echo $login_message; ?></div>
    </div>

</body>

</html>