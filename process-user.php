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
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // If not using a POST request
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    header('Content-Type: application/json');

    print_r(json_encode([
        "status_code" => 405,
        "message" => "Method not allowed."
    ]));
    exit;
}


// Check if the required fields are set
$required_fields = ["firstname", "lastname", "username", "email", "password", "confirm-password", "house", "year", "role"];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || strlen($_POST[$field]) == 0) {
        $field = $field == "firstname" ? "first name" : $field;
        $field = $field == "lastname" ? "last name" : $field;
        $field = ucwords($field);
        $error[] = "<span>'<strong>$field</strong>' is not set or has no value.</span>";
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
$role = sanitise_user_input($_POST["role"]);
$idnumber = generate_user_idnumber();
$quidditch = isset($_POST["quidditch"]) ? sanitise_user_input($_POST["quidditch"]) : null;
$prefect = isset($_POST["prefect"]) ? sanitise_user_input($_POST["prefect"]) : null;
$valid_prefect_year = ["FIFTH YEAR", "SIXTH YEAR", "SEVENTH YEAR"];
$sexgender = isset($_POST["sexgender"]) ? sanitise_user_input($_POST["sexgender"]) : "Unknown";

// Validations //
// Check if username or email already exist
if (user_exists($username, $email)) $error[] = "Username or email address already exists";

// Check passwords match
if ($password <> $password2) $error[] = "Passwords do not match";

// Check username format 
$username_pattern = "/^[a-z]\.[a-z]+[0-9]{0,2}$/";
if (!preg_match($username_pattern, $username)) $error[] = "Username should be in the format 'a.bcd' (with numbers as necessary for distinguishing users)";

// Check email format, then check domain if valid
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (explode("@", $email)[1] <> "hogwarts.wiz") $error[] = "The email domain is not valid - must be '@hogwarts.wiz'";
} else {
    $error[] = "The email address is not in a valid format";
}

// Role logic checks
switch ($role) {
    case ROLE_PARENT:
        if ($year != "NONE") $error[] = "Parents cannot have a Year assigned. Year must be set to 'None'.";
        if ($house != "NONE") $error[] = "Parents cannot be assigned to a House. House must be set to 'None'.";
        if ($quidditch) $error[] = "Parents cannot be Quidditch Players";
        if ($prefect) $error[] = "Parents cannot be Prefects.";
        break;

    case ROLE_STUDENT:
        $formatted_house = ucwords(strtolower($house));
        $formatted_year = ucwords(strtolower($year));

        if ($year == "NONE" || $year == "STAFF") {
            $error[] = "The current Year '$formatted_year' is invalid for the Student role.";
        }
        if ($house == "NONE") {
            $error[] = "The current House '$formatted_house' is invalid for the Student role.";
        } elseif ($house == "HOGWARTS" && $year != "FIRST YEAR") {
            $error[] = "Students must have a House assigned when they are in a year above First Year.";
        }
        if ($prefect && !in_array($year, VALID_PREFECT_YEARS)) {
            $error[] = "Students cannot be Prefects unless they are in the Fifth Year or above.";
        }
        if ($prefect && !in_array($house, VALID_STUDENT_HOUSES)) {
            $error[] = "Prefects can only be assigned to students sorted into a House.";
        }
        if ($quidditch && !in_array($year, VALID_QUIDDITCH_YEARS)) {
            $error[] = "Students cannot be Quidditch Players when the Year is set to '" . ucwords(strtolower($year)) . "'.";
        }
        if ($quidditch && !in_array($house, VALID_STUDENT_HOUSES)) {
            $error[] = "Students cannot be Quidditch Players unless they are sorted into a valid House. $formatted_house is not a valid House.";
        }
        break;

    case ROLE_STAFF:
    case ROLE_SENIOR_STAFF:
    case ROLE_ADMIN:
        if ($house != "HOGWARTS") $error[] = "House must be set to 'Hogwarts' for the Staff or Senior Staff roles.";
        if ($year != "STAFF") $error[] = "Year must be set to 'Staff' for the Staff or Senior Staff roles.";
        if ($quidditch) $error[] = "Staff cannot be Quidditch Players.";
        if ($prefect) $error[] = "Staff cannot be Prefects.";
        break;
}

if (!empty($error)) {
    handle_error($error);
}

// Hash the password (consider using password_hash() in production)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
// Year and House should be in standard case (legacy db)
$house = ucwords(strtolower($house));
$year = ucwords(strtolower($year));
// Get the values for the role as an id
$role_id = get_role_details($role_name = $role)["by_name"][0]["role_id"];
// Get quidditch and prefect_ids
$quidditch_id = $quidditch ? 1 : 0;
$prefect_id = $prefect  ? 1 : null;

// Run SQL to add user (may need to create add user function if other user creations exist)
$add_user = create_user(
    $username,
    $email,
    $hashed_password,
    $firstname,
    $lastname,
    $middlename,
    $commonname,
    $house,
    $year,
    $idnumber,
    $role_id,
    $quidditch_id,
    $prefect_id,
    $sexgender
);

echo json_encode("User added successfully");
redirect(WEBROOT . "/profile.php?user=$username");
