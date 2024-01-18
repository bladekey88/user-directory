<?php
require_once(__DIR__ . "/config/auth.php");

//Local Functions
function user_exists($username, $email)
{
    return mysqli_num_rows(run_sql(get_attribute_exists("username", $username))) ||
        mysqli_num_rows(run_sql(get_attribute_exists("email", $email)));
}

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

//Use for fallback 
$direct = ($_SERVER["CONTENT_TYPE"] == "application/x-www-form-urlencoded") ? true : false;

//Permissions check
if (!check_user_permission(PERMISSION_ADD_USER)) {
    header("HTTP/1.1 403 Forbidden");
    header("Location:/directory");
    exit();
}

//Set up error array
$error = array();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the required fields are set
    $required_fields = ["username", "email", "password", "confirm-password", "firstname", "lastname", "house", "year"];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || strlen($_POST[$field] == 0)) {
            array_push($error, "<span class='fw-bolder'>$field</span> is not set or has no value");
        }
    }

    //Want to exit early if field values are missing
    $error && handle_error($error);

    // Retrieve user input
    $username = sanitise_user_input($_POST["username"], "username");
    $email = sanitise_user_input($_POST["email"], "email");
    $password = sanitise_user_input($_POST["password"]);
    $password2 = sanitise_user_input($_POST["confirm-password"]);
    $firstname = sanitise_user_input($_POST["firstname"]);
    $lastname = sanitise_user_input($_POST["lastname"]);
    $sanitised_middlename = sanitise_user_input($_POST["middlename"]);
    $middlename = strlen($sanitised_middlename) > 0 ? $sanitised_commonname : null;
    $sanitised_commonname = sanitise_user_input($_POST["commonname"]);
    $commonname = strlen($sanitised_commonname) > 0 ? $sanitised_commonname : $firstname;
    $house = sanitise_user_input($_POST["house"]);
    $year = sanitise_user_input($_POST["year"]);
    $idnumber = str_pad(random_int(1000000, 1000000000), 10, "0", STR_PAD_LEFT);
    $locked = 0;

    //Validations    
    $username_pattern = "/^[a-z]\.[a-z]+[0-9]{0,2}$/";
    //Does the user exist already with username or email?
    if (user_exists($username, $email)) {
        array_push($error, "The username or email address already exists");
    }

    if ($password <> $password2) {
        array_push($error, "The passwords do not match");
    }

    if (!preg_match($username_pattern, $username)) {
        array_push($error, "Username should be in the format 'a.bcd' (with numbers as necessary for distinguishing users)");
    }

    if (explode("@", $email)[1] <> "hogwarts.wiz") {
        array_push($error, "The email domain is not valid  it must be hogwarts.wiz");
    }

    if ($year == "STAFF" && $house <> "HOGWARTS") {
        array_push($error, "House must be set to Hogwarts when Year is set to Staff");
    }

    if ($year == "NONE" && $house <> "NONE") {
        array_push($error, "House must be set to None when Year is set to None");
    }

    if ($year != "NONE" && $house == "NONE") {
        array_push($error, "House must not be set to None when Year is set to $year");
    }

    if ($error) {
        $_SESSION["error"] = $error;
        $response = [
            'error' => $error,
        ];
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
        header("Location:" . WEBROOT . "add-user.php");
        exit();
    }

    // Good to go!
    $conn = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Hash the password (consider using password_hash() in production)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    //Year and House should be be in standard case (legacy db)
    $house = ucwords(strtolower($house));
    $year = ucwords(strtolower($year));

    // Insert data into the database (replace "users" with your actual table name)
    $sql = "INSERT INTO users (username, email, password, firstname, middlename, lastname, commonname, house, year, idnumber, locked) 
            VALUES ('$username', '$email', '$hashed_password', '$firstname','$middlename', '$lastname', '$commonname', '$house', '$year', '$idnumber', '$locked')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode("User added successfully");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    // Close the database connection
    $conn->close();
    header("Location:" . WEBROOT . " /profile.php?user=$username");
} else {
    // If the form is not submitted, redirect to the add_user page
    echo ("SOMETHING WENT WRONG");
}
