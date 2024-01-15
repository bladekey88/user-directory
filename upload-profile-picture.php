<?php
// File handling logic
require_once("./assets/config/functions.php");
require_once("./assets/config/auth.php");

if (!isset($_POST["idnumber"])) {
    echo "An ID Number is required to use this functionality.";
    exit();
}

$messages = array();
$errors = array();

// Error if the user clicks upload without selecting a file
if (!isset($_FILES['profilePicture']) || empty($_FILES['profilePicture']['name'])) {
    array_push($errors, "Please select a file to upload.");
    $messages["message"] = "Upload Failed";
    $messages["error"] = $errors;
    echo json_encode($messages);
    exit();
}

// Get file information
$fileName = $_FILES['profilePicture']['name'];
$fileTmpName = $_FILES['profilePicture']['tmp_name'];
$fileSize = $_FILES['profilePicture']['size'];
$fileError = $_FILES['profilePicture']['error'];
$fileType = $_FILES['profilePicture']['type'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExts = array('jpg', 'jpeg', 'png', 'jfif');

// Error is the file is the wrong format (use mime-type in the future
if (!in_array($fileExt, $allowedExts)) {
    array_push($errors, "Invalid file format: '$fileExt'");
}

// Error is the files is too large
if ($fileSize > 2397152) { // 2MB
    array_push($errors, "File size is greater than 2MB (~" . round($fileSize / 1E6, 1) . "MB)");
}

// If there are errors than tell the user and exit the script - no db commits
if (!empty($errors)) {
    $messages["message"] = "Upload Failed - One or more errors have occurred.";
    $messages["error"] = $errors;
    echo json_encode($messages);
    exit();
}

// Process the File
// Generate a new filename and create directories as necessary. If this fails capture the error and block continuing
$newFileName = $_POST["idnumber"] . '-' . uniqid() . '.' . $fileExt;
$uploadDir = 'uploads/' . date("Y") . "/" . date("m") . "/" . date("d") . "/"; // Create this directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Upload the image and insert/update the database as necessary
if (move_uploaded_file($fileTmpName, $uploadDir . $newFileName)) {
    $imgPath = $uploadDir . $newFileName;

    //For now update icalcs using Exec
    $command = "icacls \"$imgPath\" /grant:r \"Authenticated Users:(OI)(CI)F\"";
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        array_push($errors, "A server error occured.<br>Please contact support.");
        $messages["message"] = "Upload Failed";
        $messages["error"] = $errors;
        echo json_encode($messages);
        die();
    }

    $checkImage = run_sql(check_profile_picture_exists($_POST["idnumber"]));
    $row = mysqli_fetch_assoc($checkImage);
    if (!$row) {
        // No rows returned so need to insert
        $insert_row = run_sql(insert_new_profile_picture($_POST["idnumber"], $imgPath));
    } else {
        //Update existing row in DB, then delete the old image from disk
        $update_row = run_sql(update_profile_picture($_POST["idnumber"], $imgPath));
        @unlink($row["path"]);
    }
    // Return to use and inform them of completion
    echo json_encode(
        array(
            "message" => "Profile picture updated",
            "imagePath" => $uploadDir . $newFileName,
        )
    );
} else {
    //Generic Issue
    echo "Upload failed";
}
