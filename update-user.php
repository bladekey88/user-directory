<?php
require_once(__DIR__ . "/config/auth.php");


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
$direct = (isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"] == "application/json") ? false : true;

//Permissions check
if (!check_user_permission(PERMISSION_EDIT_OWN_PROFILE) && !check_user_permission((PERMISSION_EDIT_ANY_PROFILE))) {
    header("HTTP/1.1 403 Forbidden");
    redirect("/");
}

//Set up error array
$error = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $field = sanitise_user_input($_POST["field"]);
    $value = sanitise_user_input($_POST["value"]);
    $userid = sanitise_user_input($_POST["userid"], "numeric");

    //mandatory validation for allowable editable fields
    if ($field == "commonname" && (strlen($value) == 0 || !isset($value))) {
        array_push($error, "Preferred Name cannot be blank.");
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
        header("Location:" . WEBROOT . "/add-user.php");
        exit();
    }


    $sql = "UPDATE users
    SET $field = '$value'
    WHERE userid = '$userid';";

    $conn = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    if ($conn->query($sql) === TRUE) {
        echo json_encode("'$field' updated successfully");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    // Close the database connection
    $conn->close();
} else {
    // If the form is not submitted, redirect to the add_user page
    echo ("SOMETHING WENT WRONG");
}
