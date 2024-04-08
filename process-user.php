<?php
require_once(__DIR__ . "/config/auth.php");

//Local Functions
function handle_error($error)
{
    $_SESSION["error"] = $error;
    $response = [
        'error' => $error,
    ];
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

#################################
######## BEGIN EXECUTION ########
#################################

//Permissions check
if (!check_user_permission(PERMISSION_ADD_USER)) {
    header("HTTP/1.1 403 Forbidden");
    redirect("/");
    exit("Insufficient Permissions");
}

// Set up error array
$error = array();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the required fields are set
    $required_fields = ["username", "email", "password", "confirm-password", "firstname", "lastname", "house", "year"];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || strlen($_POST[$field]) == 0) {
            array_push($error, "<span class='fw-bolder'>$field</span> is not set or has no value");
        }
    }

    // Exit early if mandatory values are missing
    if (!empty($error)) {
        var_dump(handle_error($error));
    }

    // Retrieve user input
    $username = sanitise_user_input($_POST["username"], "username");
    $email = sanitise_user_input($_POST["email"], "email");
    $password = sanitise_user_input($_POST["password"]);
    $password2 = sanitise_user_input($_POST["confirm-password"]);
    $firstname = sanitise_user_input($_POST["firstname"]);
    $lastname = sanitise_user_input($_POST["lastname"]);
    $middlename = sanitise_user_input($_POST["middlename"]);
    $commonname = sanitise_user_input($_POST["commonname"]);
    $house = sanitise_user_input($_POST["house"]);
    $year = sanitise_user_input($_POST["year"]);
    $idnumber = generate_user_idnumber();

    // Validations 
    // Check if username or email already exist
    if (user_exists($username, $email)) {
        array_push($error, "Username or email address already exists");
    }
    // Check passwords match
    if ($password <> $password2) {
        array_push($error, "Passwords do not match");
    }
    // Check username format 
    $username_pattern = "/^[a-z]\.[a-z]+[0-9]{0,2}$/";
    if (!preg_match($username_pattern, $username)) {
        array_push($error, "Username should be in the format 'a.bcd' (with numbers as necessary for distinguishing users)");
    }
    // Check email format, then check domain if valid
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (explode("@", $email)[1] <> "hogwarts.wiz") {
            array_push($error, "The email domain is not valid - must be '@hogwarts.wiz'");
        }
    } else {
        array_push($error, "The email address is not in a valid format");
    }
    // House and Year logic checks
    if ($year == "STAFF" && $house <> "HOGWARTS") {
        array_push($error, "House must be set to Hogwarts when Year is set to Staff");
    }
    if ($year == "NONE" && $house <> "NONE") {
        array_push($error, "House must be set to None when Year is set to None");
    }
    if ($year != "NONE" && $house == "NONE") {
        array_push($error, "House must not be set to None when Year is set to $year");
    }

    if (!empty($error)) {
        handle_error($error);
    }

    // Hash the password (consider using password_hash() in production)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // Year and House should be in standard case (legacy db)
    $house = ucwords(strtolower($house));
    $year = ucwords(strtolower($year));

    // Run SQL to add user (may need to create add user function if other user creation exist)
    $add_user = run_sql2(insert_new_user(
        $username,
        $email,
        $hashed_password,
        $firstname,
        $lastname,
        $middlename,
        $commonname,
        $house,
        $year,
        $idnumber
    ));

    echo json_encode("User added successfully");
    redirect(WEBROOT . "/profile.php?user=$username");
} else {
    // If not using a POST request
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    header('Content-Type: application/json');

    print_r(json_encode([
        "status_code" => 405,
        "message" => "Method not allowed"
    ]));
    exit;
}
