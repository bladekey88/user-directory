<?php
@require_once("auth.php");


$ldap = new LDAPConnection(LDAP_PROTOCOL . LDAP_HOST);
$filter = "uid=" . $_SESSION["username"];
$core_attributes = [
    "displayname",
    "employeenumber",
    "employeetype",
    "givenname",
    "l",
    "middlename",
    "o",
    "objectclass",
    "preferredlanguage",
    "sn",
    "title",
    "uid",
    "ou",
    "cn",
    "preferredfullname",
    "schoolhouse",
    "schoolyear",
    "mail",
    "sex",
    "prefect",
    "suspendedaccount",
];

echo "<h4>User</h4>";
$userinfo = ldap_get_user_info();
$img_data =  $userinfo[0]['jpegphoto'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
// // Get the MIME type
$mime_type = finfo_buffer($finfo, $img_data);
// // Close the Fileinfo object
finfo_close($finfo);
//Convert the binary data to base64 encoding then create the data URI 
$base64ImageData = base64_encode($img_data);
$dataUri = 'data:' . $mime_type . ';base64,' . $base64ImageData;
// echo $dataUri;
$userinfo[0]['jpegphoto'] = base64_encode($userinfo[0]['jpegphoto']);

echo "<h4>Group</h4>";
echo "<pre>";
print_r($userinfo);
echo "</pre>";

// echo $cn;
