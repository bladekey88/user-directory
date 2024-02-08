<?php
@require_once("auth.php");


$ldap = new LDAPConnection;
$filter = "uid=" . $_SESSION["username"];
$core_attributes = [
    "displayname", "employeenumber", "employeetype", "givenname", "l", "middlename", "o", "objectclass", "preferredlanguage", "sn", "title", "uid", "ou", "cn", "preferredfullname", "schoolhouse", "schoolyear", "mail", "sex", "prefect", "suspendedaccount",
];


$userinfo = ldap_get_user_info()[0];

$img_data =  $userinfo['jpegphoto'];


$finfo = finfo_open(FILEINFO_MIME_TYPE);
// Get the MIME type
$mime_type = finfo_buffer($finfo, $img_data);
// Close the Fileinfo object
finfo_close($finfo);

//Convert the binary data to base64 encoding then create the data URI 
$base64ImageData = base64_encode($img_data);
$dataUri = 'data:' . $mime_type . ';base64,' . $base64ImageData;

echo $dataUri;
