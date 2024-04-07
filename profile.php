<?php

// Set page title and import the header
require_once(__DIR__ . "/config/auth.php");

// Profile page checks
if (isset($_GET["user"])) {
    $username = sanitise_user_input($_GET["user"], "username");
} else {
    $username = $_SESSION["username"];
}

//Global Permission and Role Check
if (!check_user_permission(PERMISSION_VIEW_USER)) {
    @require_once(dirname(__DIR__, 2) . "/htdocs/errordocs/403.php");
    exit();
}

if ($username != $_SESSION["username"]) {
    if (!check_user_permission(PERMISSION_VIEW_ALL_USERS)) {
        redirect("/profile.php", "HTTP/1.1 404 Not Found");
    }
}

// Get user details from database
$user = run_sql2(get_specific_user($username));
if ($user) {
    $user = $user[0];
    // Handle Optional Fields (display only)
    if (strlen($user["middlename"]) == 0) {
        $middlename = htmlentities("No Value");
    } else {
        $middlename = $user["middlename"];
    }

    if (!$user["quidditch"] || $user["quidditch"] == 0) {
        $quidditch = null;
    } else {
        $quidditch = true;
    }

    if (!$user["path"]) {
        $profile_image = "assets/img/user-default.png";
    } else {
        $profile_image = $user["path"];
    }

    $hidden = $user["locked"] ? "hidden" : null;
} else {
    echo "<div class='container my-5 fw-bold alert alert-warning rounded-0 border-0 border-start border-end border-danger border-5 text-danger h5'>
    Error - The user profile was not found. Please contact IT Services for further details, or return to the previous page to try again.
</div>";
    exit();
}

//Get user role and permission details for displayed user
// Get role details first
$user_role_query = run_sql2(get_user_role($user["userid"]));
$user_role_result = $user_role_query[0] ?? null;
if ($user_role_result) {
    $role = ucwords(strtolower(sanitise_user_input($user_role_result["role_name"])));
} else {
    $role = LANG_NO_ROLES;
}

//Now get permission details for the role
if (count($user_role_query) > 0) {
    $role_permissions = run_sql2(get_role_permissions($user_role_result['role_id']));
    if (count($role_permissions) == 0) {
        $permissions = LANG_NO_PERMS;
    } else {
        $permissions = True;
    }
} else {
    $permissions = LANG_NO_PERMS;
}

// Start the html
$title =  $user["firstname"] . " " . $user["lastname"] . " - Profile Page";
require_once(FILEROOT . "/header.php");

?>
<style>
    body {
        background-color: #f2f6fc;
    }

    .img-account-profile {
        height: 50%;
        width: 50%;
    }

    .rounded-circle {
        border-radius: 50% !important;
        border: solid 1px #dedede;
    }

    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgb(33 40 50 / 15%);
    }

    .card .card-header {
        font-weight: 500;
    }

    .card-header:first-child {
        border-radius: 0.35rem 0.35rem 0 0;
    }

    .card-header {
        padding: 1rem 1.35rem;
        margin-bottom: 0;
        background-color: rgba(33, 40, 50, 0.03);
        border-bottom: 1px solid rgba(33, 40, 50, 0.125);
    }

    .card-header {
        max-height: 3.55rem;
    }

    [contenteditable] {
        cursor: pointer;
        transition: all 0.22s ease-in-out;
    }

    [contenteditable]::before {
        content: "\F4CB";
        font-size: 1rem;
        font-weight: bolder;
        font-family: "bootstrap-icons";
        color: blue;
        margin-inline: 0.25rem;
    }

    [contenteditable]:focus {
        background-color: papayawhip;
        padding-left: 5px;
        transition: all 0.2s ease-in-out;
    }

    .styled-table th {
        width: 100%;
    }

    caption {
        caption-side: top;
        text-align: left;
        font-weight: 500;
        font-size: 1rem;
        color: rgb(33, 37, 41);
    }
</style>

<main>
    <div class="container-xl px-4 mt-4">
        <noscript>
            <div class="alert alert-warning border-danger text-danger rounded-0 p-0 py-1 ps-2 border-2" role="alert">
                <i class="pe-2 bi bi-exclamation-triangle"></i>
                This page has limited functionality as Javascript is not enabled
            </div>
        </noscript>
        <div id="lockAccountStatus"></div>
        <?php if ($user["locked"] && (check_user_permission(PERMISSION_UNLOCK_USER) || check_user_permission(PERMISSION_LOCK_USER))) : ?>
            <div id='accountLockedAlert' class="alert alert-warning border border-2 border-danger h6 rounded-0 pt-2 pb-1">
                <h5 class="text-danger text-start fw-bolder">
                    <i class="bi bi-exclamation-triangle pe-2"></i>
                    <span>Account locked</span>
                </h5>
                <p class="text-danger">
                    The user cannot log in to Directory until their account is unlocked.</p>
                <?php if (check_user_permission(PERMISSION_LOCK_USER)) : ?>
                    <hr>
                    <div class="bg-white p-3 border border-1 border-dark mb-1 pb-2 shadow">
                        <p class="py-2 px-3 rounded-0 bg-white border border-primary text-dark">
                            <i class="bi bi-info-circle pe-2"></i>
                            Administrators can unlock the account directly using the ' Unlock Account' button, or IT Services can reset the account.
                        </p>
                        <a id="btnunlockaccount" role="button" class="btn shadow btn-primary border-dark border-1 mb-1 rounded-0" href="<?php echo WEBROOT; ?>/change-account-status.php?action=unlock&user=<?php echo $username; ?>">
                            <i class="bi bi-unlock"></i>
                            Unlock Account
                        </a>
                        <div id="unlockAccountStatus"></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- Account page navigation-->
        <nav class="nav nav-borders">
            <h4 class="m-0"><?php echo $user["firstname"] . " " . $user["lastname"]; ?></h4>
        </nav>
        <hr class="mt-0 mb-4">
        <div class="row">
            <div class="col-xl-4">
                <!-- Profile picture card-->
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header">Profile Picture</div>
                    <div class="card-body text-center">
                        <!-- Profile picture image-->
                        <img class="img-account-profile rounded-circle mb-2" src="<?php echo $profile_image; ?>" alt="Profile photo">
                        <!-- Profile picture upload button-->
                        <?php if (($username == $_SESSION["username"] && check_user_permission(PERMISSION_EDIT_OWN_PROFILE)) || (check_user_role(ROLE_ADMIN) && check_user_permission(PERMISSION_EDIT_ANY_PROFILE))) : ?>
                            <noscript>
                                <div class="alert alert-warning border-danger border-2 m-0 p-0 rounded-0 " role="alert">
                                    <p class="small p-0 m-0 ps-1">
                                        <span>Javascript required to change profile picture on this page.</span>
                                        <span> Your profile picture can be changed without needing Javascript on the Edit Profile page.</span>
                                    </p>
                                </div>
                            </noscript>
                            <form class="d-none form border border-1 border mt-1 py-2 px-3" id="profilePictureForm">
                                <div class="d-grid gap-2">
                                    <label class="form-label p-0" for="profilePicture">Upload New Profile Picture</label>
                                    <div class="text-start small font-italic text-muted">JPG, JFIF, or PNG no larger than 2 MB</div>
                                    <input type="file" name="profilePicture" id="profilePicture" class="rounded-0 border border-secondary border-1 form-control-sm">
                                    <input type="hidden" name="idnumber" id="idnumber" value="<?php echo $user["idnumber"]; ?>">
                                    <button type="submit" class="btn btn-primary rounded-0 btn-sm">Upload</button>
                                </div>
                                <div id="uploadStatus"></div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <!-- Account details card-->
                <div class="card mb-4">
                    <ul class="nav nav-tabs nav-underline nav-fill border-dark rounded-0 shadow d-none" id="tabProfile" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button" role="tab" aria-controls="account" aria-selected="true">Account</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions" type="button" role="tab" aria-controls="permissions" aria-selected="false">Roles and Permissions</button>
                        </li>
                        <?php
                        if (!check_user_permission(PERMISSION_EXTERNAL_USER)) : ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="school-info-tab" data-bs-toggle="tab" data-bs-target="#school-info" type="button" role="tab" aria-controls="school-info" aria-selected="false">School Information</button>
                            </li>
                        <?php endif; ?>
                        <?php if ((check_user_permission(PERMISSION_EDIT_OWN_PROFILE) && $username == $_SESSION["username"]) || check_user_permission(PERMISSION_EDIT_ANY_PROFILE)) : ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="ldap-info-tab" data-bs-toggle="tab" data-bs-target="#ldap-info" type="button" role="tab" aria-controls="ldap-info" aria-selected="false">LDAP Information</button>
                            </li>
                        <?php endif; ?>
                        <?php if ($_SESSION["username"] == $username) : ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="certificates-tab" data-bs-toggle="tab" data-bs-target="#certificates" type="button" role="tab" aria-controls="certificates" aria-selected="false">Certificates</button>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="tab-content" id="tabContent">
                        <div class="tab-pane fade show active" id="account" role="tabpanel" aria-labelledby="account-tab" tabindex="0">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <p class="my-0">Account Details</p>
                                <div class="account-buttons" id="account-buttons">
                                    <?php if ((check_user_permission(PERMISSION_EDIT_OWN_PROFILE) && $username == $_SESSION["username"]) || check_user_permission(PERMISSION_EDIT_ANY_PROFILE)) : ?>
                                        <a role="button" class="btn btn-sm btn-outline-primary border-2 rounded-0" data-enabled="false" id="btneditprofile" href="<?php echo WEBROOT . "/edit-profile.php?user=" . $user["username"]; ?>">Edit Profile</a>
                                    <?php endif; ?>
                                    <?php if ((check_user_permission(PERMISSION_EDIT_OWN_PROFILE) && $username == $_SESSION["username"])) : ?>
                                        <a role="button" id="btnViewEmail" class="btn btn-sm btn-outline-success border-2 rounded-0" href="<?php echo WEBROOT . "/mail.php"; ?>">
                                            <i class="bi bi-envelope pe-1"></i>View Email Account Status
                                        </a>
                                    <?php endif; ?>
                                    <?php if (check_user_permission(PERMISSION_LOCK_USER) && $user["locked"] == 0) : ?>
                                        <a role="button" id="btnlockaccount" class="btn btn-sm btn-outline-danger border-2 rounded-0 <?php echo $hidden; ?>" href="<?php echo WEBROOT . "/change-account-status.php?action=lock&user=$username"; ?>">
                                            <i class="bi bi-lock pe-1"></i>Lock Account
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div id="account-details" class="card-body ms-4">
                                <!-- Form Group (username)-->
                                <div class="mb-2 border-bottom">
                                    <label class="small mb-1" for="inputUsername">Username</label>
                                    <p id="inputUsername" data-editable="false" class="fw-bold"><?php echo $user["username"]; ?></p>
                                </div>
                                <!-- Form Row-->
                                <div class="row gx-3 mb-2">
                                    <!-- Form Group (first name)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputFirstName">First name</label>
                                        <p id="inputFirstName" data-editable="false" class="fw-bold"><?php echo $user["firstname"]; ?></p>
                                    </div>
                                    <!-- Form Group (last name)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputLastName">Last name</label>
                                        <p id="inputLastName" data-editable="false" class="fw-bold"><?php echo $user["lastname"]; ?></p>
                                    </div>
                                </div>
                                <!-- Form Row-->
                                <div class="row gx-3 mb-2 border-bottom">
                                    <!-- Form Group (Other name)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputCommonName">Preferred Name/Title</label>
                                        <p id="inputCommonName" data-editable="true" class="fw-bold"><?php echo $user["commonname"]; ?>
                                        </p>
                                    </div>
                                    <!-- Form Group (Middle)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputMiddleName">Middle Name</label>
                                        <p id="inputMiddleName" data-editable="true" class="fw-bold"><?php echo $middlename; ?></p>
                                    </div>
                                </div>
                                <!-- Form Row-->
                                <div class="row gx-3 mb-2 border-bottom">
                                    <!-- Form Group (email address)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputEmailAddress">Email address</label>
                                        <p id="inputEmailAddress" data-editable="false" class="fw-bold"><?php echo $user["email"]; ?></p>
                                    </div>
                                    <!-- Form Group (ID number)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputID">ID number</label>
                                        <p id="inputID" data-editable="false" class="fw-bold"><?php echo $user["idnumber"]; ?></p>
                                    </div>
                                </div>
                                <div class="row gx-3 mb-2">
                                    <!-- Form Group (Country)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputCountry">Country</label>
                                        <p id="inputCountry" data-editable="true" class="fw-bold"><?php echo $user["country"]; ?></p>
                                    </div>
                                    <!-- Form Group (City)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputCity">City</label>
                                        <p id="inputCity" data-editable="true" class="fw-bold"><?php echo $user["city"]; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade active show" id="permissions" role="tabpanel" aria-labelledby="permissions-tab" tabindex="0">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <p class="my-0">Permissions</p>
                            </div>
                            <div id="account-permissions" class="card-body ms-4">
                                <!-- Form Group (username)-->
                                <div class="mb-2 border-bottom">
                                    <label class="small mb-1" for="role">Role</label>
                                    <p id="role" class="fw-bold"><?php echo $role; ?></p>
                                </div>
                                <div class="mb-2">
                                    <label class="small" for="permissions-text">Permissions</label>
                                    <p id="permissions-text" class="fw-bold">
                                        <?php if (is_bool($permissions)) : ?>
                                    <ul>
                                        <?php foreach ($role_permissions as $role_perm_result) : ?>
                                            <li id="<?php echo $role_perm_result["permission_id"]; ?>" class="mb-1 fw-bolder">
                                                <code><?php echo $role_perm_result["permission"]; ?></code>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <?php echo $permissions; ?>
                                <?php endif;  ?>
                                </p>
                                </div>
                            </div>
                        </div>
                        <?php if (!check_user_permission(PERMISSION_EXTERNAL_USER)) : ?>
                            <div class="tab-pane fade show active" id="school-info" role="tabpanel" aria-labelledby="school-info-tab" tabindex="0">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <p class="my-0">School Information</p>
                                </div>
                                <div id="account-school-info" class="card-body ms-4">
                                    <div class="d-flex justify-content-around align-items-flex-start flex-column">
                                        <div>
                                            <label class="small mb-1" for="inputHouse">House</label>
                                            <p id="inputHouse" data-editable="false" class="fw-bold"><?php echo $user["house"]; ?></p>
                                        </div>
                                        <div>
                                            <label class="small mb-1" for="inputYear">Year</label>
                                            <p id="inputYear" data-editable="false" class="fw-bold"><?php echo $user["year"]; ?></p>
                                        </div>
                                        <?php if ($quidditch) : ?>
                                            <div>
                                                <label class="small mb-1" for="inputTeam">Quidditch Team</label>
                                                <p id="inputTeam" data-editable="false" class="fw-bold">Team Member</p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($_SESSION["username"] == $user["username"]) : ?>
                                            <a href="<?php echo WEBROOT . "/vle.php"; ?>">View VLE Information</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="tab-pane fade show active" id="ldap-info" role="tabpanel" aria-labelledby="ldap-info-tab" tabindex="0">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <p class="my-0">Information held by Hogwarts Authentication Directory Service (HADS)</p>
                            </div>
                            <div id="account-ldap" class="card-body ms-4">
                                <div class="mb-2">
                                    <?php
                                    // Get the information from LDAP
                                    $ldap_user = ldap_get_user_info($user["username"])[0]; //Expect only user, therefore retrn first index
                                    if (gettype($ldap_user) == "array") {
                                        ksort($ldap_user);
                                    }
                                    ?>
                                    <?php if (gettype($ldap_user) == "array") : ?>
                                        <table class="styled-table" id="ldap-attribute-table" name="ldap-attribute-table" style="width:95%;">
                                            <thead>
                                                <tr>
                                                    <th>LDAP attributes</th>
                                                    <th>Value</th>
                                                </tr>
                                            </thead>
                                            <?php foreach ($ldap_user as $k => $v) : ?>
                                                <tr>
                                                    <th>
                                                        <code><?php echo $k; ?></code>
                                                    </th>
                                                    <td>
                                                        <?php
                                                        #special handling for profile image
                                                        if ($k == "jpegphoto") : ?>
                                                            <img width="50%" height="50%" alt="User profile picture stored in LDAP" src="<?php echo ldap_parse_user_photo($v); ?>">
                                                        <?php else :
                                                            echo $v;
                                                        endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php else : ?>
                                        <div class="alert alert-warning h6 fw-bolder rounded-0 shadow-0">
                                            <h6 class="mb-0">
                                                <i class="bi bi-exclamation-triangle pe-2"></i>
                                                <?php echo $ldap_user; ?>
                                            </h6>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>

                        <?php if ($_SESSION["username"] == $user["username"]) : $cert_info = get_certificate_information(); ?>

                            <div class="tab-pane fade show active" id="certificates" role="tabpanel" aria-labelledby="certificates-tab" tabindex="0">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <p class="my-0">Certificate Information</p>
                                </div>
                                <div id="account-certificates" class="card-body ms-4">
                                    <?php if (isset($_SERVER["SSL_CLIENT_S_DN_CN"])) : ?>
                                        <div class="alert alert-info border-2 border-secondary rounded-0 alert-dismissible fade show" role="alert">
                                            <h6 class="text-dark fw-bolder">Client Certificate Detected</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            <p class="">
                                                A client certificate is being presented by the browser. This certificate may be able to be used for login.
                                                Clicking the button will enrol the certificate and overwrite any certifiate details that were previously stored.
                                                This will be the case even if the presented certificate information is identical to the stored certificate information.
                                                <span class="fw-semibold">By default, certificates are not enabled for login.</span>
                                            </p>
                                            <div class="bg-white p-3 border border-1 border-dark mb-1 pb-2 shadow text-dark">
                                                <?php if ($cert_info && $cert_info["certificate_cn"] == $_SERVER["SSL_CLIENT_S_DN_CN"] && $cert_info["certificate_serial"] == $_SERVER["SSL_CLIENT_M_SERIAL"]) : ?>
                                                    <div class="alert alert-warning border border-2 border-danger py-1 text-dark rounded-1" role="alert">
                                                        <i class="bi bi-exclamation-circle fw-bold"></i>
                                                        <span>Certificate appears to match stored information</span>
                                                    </div>
                                                <?php endif; ?>
                                                <h6 class="fw-bolder">Certificate Details</h6>
                                                <ul class="list-unstyled ms-3">
                                                    <li>Name: <?php echo $_SERVER["SSL_CLIENT_S_DN_CN"]; ?></li>
                                                    <li>Issued by: <?php echo $_SERVER["SSL_CLIENT_I_DN_CN"]; ?></li>
                                                    <li>Serial Number: <?php echo $_SERVER["SSL_CLIENT_M_SERIAL"]; ?></li>
                                                    <li> Valid from: <?php echo date("Y-m-d", strtotime($_SERVER["SSL_CLIENT_V_START"])); ?></li>
                                                    <li>Valid to: <?php echo date("Y-m-d", strtotime($_SERVER["SSL_CLIENT_V_END"])); ?></li>
                                                </ul>
                                                <div class="d-flex gap-2">
                                                    <button id="enrol-certificate" type="button" class="ms-3 mb-3 btn btn-primary rounded-0 disabled">
                                                        Enrol Detected Certificate
                                                    </button>
                                                    <noscript>
                                                        <div class="alert alert-warning border-danger text-danger rounded-0 p-3 py-2 ps-2 border-3" role="alert">
                                                            Functionality disabled as javascript is required
                                                        </div>
                                                    </noscript>
                                                </div>
                                                <div id="certificate-error"></div>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <button id="enrol-certificate" type="button" class="mb-3 btn btn-primary rounded-0">
                                            Enrol Certificate
                                        </button>
                                        <div id="certificate-error"></div>
                                    <?php endif; ?>

                                    <?php
                                    if ($cert_info) : ?>
                                        <table class="styled-table" id="certificate-table" name="certificate-table" style="width:100%; margin-top: 0;">
                                            <caption>Enroled Certificate Details</caption>
                                            <thead>
                                                <tr>
                                                    <th>Attribute</th>
                                                    <th>Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th scope="row">Serial Number</th>
                                                    <td><?php echo $cert_info["certificate_serial"]; ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">CN</th>
                                                    <td><?php echo $cert_info["certificate_cn"]; ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Email</th>
                                                    <td><?php echo $cert_info["certificate_email"]; ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Issuer</th>
                                                    <td><?php echo $cert_info["certificate_client_issuer_dn"]; ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Valid From</th>
                                                    <td><?php echo $cert_info["certificate_start"]; ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Valid To</th>
                                                    <td><?php echo $cert_info["certificate_end"]; ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Used for login</th>
                                                    <td id="td-cert-status">
                                                        <code class="fw-bolder text-capitalize">
                                                            <?php echo ($cert_info["enabled_by_user"] == 1) ? "Enabled" : "Disabled"; ?></code>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Options</th>
                                                    <td>
                                                        <noscript>
                                                            <div class="alert alert-warning border-danger text-danger rounded-0 p-0 py-1 ps-2 border-2" role="alert">
                                                                Functionality disabled as javascript is required
                                                            </div>
                                                        </noscript>
                                                        <button id="toggleCertificateState" disabled="disabled" class="disabled mx-2 btn btn-sm btn-primary" type="button"><?php echo ($cert_info["enabled_by_user"] == 1) ? "Disable" : "Enable"; ?></button>
                                                        <button id="deleteCertificate" disabled="disabled" class="disabled mx-2 btn btn-sm btn-danger" type="button">Delete</button>
                                                        <div id="certificate-status-change"></div>
                                                    </td>
                                                </tr>
                                                <tr>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                    <!-- End TabContent -->
                </div>
                <!-- End Account Details Card -->
            </div>
        </div>
    </div>
</main>

<script>
    function loadDynamicElements() {
        window.onload = function() {
            let profilePictureForm = document.getElementById('profilePictureForm');
            let tabProfileTab = document.getElementById('tabProfile');
            let tabId = window.location.hash.substring(1);

            profilePictureForm && profilePictureForm.classList.remove("d-none");
            tabProfileTab && tabProfileTab.classList.remove("d-none");

            // Enable Buttons that require JS to work (i.e. no fallback)
            let enrolCertificateButton = document.getElementById("enrol-certificate");
            let toggleCertificateStateButton = document.getElementById("toggleCertificateState");
            let deleteCertificateButton = document.getElementById("deleteCertificate");
            buttonArray = [enrolCertificateButton, toggleCertificateStateButton, deleteCertificateButton];
            buttonArray.forEach(button => {
                if (button) {
                    button.classList.remove("disabled");
                    button.removeAttribute("disabled");
                }
            })

            //Get each tab - pane class and remove show class
            let tabPanes = document.querySelectorAll(":not(#account).tab-pane");
            tabPanes.forEach((field) => {
                field.classList.remove("show", "active");
            });

            if (tabId) {
                const tabLink = document.querySelector(`[data-bs-target="#${tabId}"]`);
                let tabContent = document.querySelector(tabLink.getAttribute("data-bs-target"));
                if (tabLink && tabContent) {
                    // Activate the tab link
                    new bootstrap.Tab(tabLink).show();
                }
            }
        }
    }

    async function changeAccountStatus(action) {
        const buttonId = action === 'lock' ? 'btnlockaccount' : 'btnunlockaccount';
        const statusElementId = action === 'lock' ? 'lockAccountStatus' : 'unlockAccountStatus';
        const button = document.getElementById(buttonId);
        const statusElement = document.getElementById(statusElementId);

        if (!button) return;

        button.href = "#";
        button.addEventListener('click', async (e) => {
            e.preventDefault();

            try {
                const response = await fetch(`<?php echo WEBROOT; ?>/change-account-status.php?action=${encodeURIComponent(action)}&user=<?php echo urlencode($username); ?>`);
                //Handle incorrect params and permission/authorisation errors 
                if (!response.ok) {
                    let errorMessage;
                    if (response.status === 403 || response.status === 401) {
                        errorMessage = '<?php echo LANG_INSUFFICIENT_PRIVILEGES; ?>';
                    } else if (response.status === 400) {
                        errorMessage = '<?php echo LANG_BAD_REQUEST; ?>';
                    } else {
                        errorMessage = 'An error occurred. Please contact the adminisrator for more information.';
                    }
                    throw new Error(errorMessage);
                }

                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                statusElement.className = '';
                statusElement.classList.add(
                    "text-left", "rounded-0", "py-2", "mt-2", "alert", "alert-success", "border", "border-success"
                );

                statusElement.innerHTML = `<h6 class='fw-bolder pt-1 mb-0'>${data.message}</h6>`;
                if (data.account && data.status) {
                    statusElement.innerHTML += `<p class="my-1 ms-2"><code>${data.account} => ${data.status}</code></p>`;
                }

                button.disabled = true;
                button.hidden = true;
            } catch (error) {
                statusElement.classList.add("text-left", "rounded-0", "py-1", "mt-2", "alert", "alert-danger", "border", "border-danger");
                statusElement.innerHTML = `<h6 class='fw-bolder pt-1 mb-0'>An Error Occurred</h6><p class="mb-1">${error.message}</p>`;
            }
        });
    }

    function uploadProfilePicture() {
        const form = document.getElementById('profilePictureForm');
        if (!form) {
            return false;
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo WEBROOT; ?>/upload-profile-picture.php');

            xhr.onload = () => {
                const response = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && !response.error) {
                    const statusElement = document.getElementById('uploadStatus');
                    document.getElementById('uploadStatus').className = "";
                    document.getElementById('uploadStatus').classList.add("text-left", "rounded-0", "py-2", "mt-2", "alert", "alert-success", "border", "border-success");
                    statusElement.innerHTML = `<h6 class='fw-bold pt-1 mb-0'>${response.message}</h6>`;
                    form.reset();
                    const currentProfileImage = document.querySelector(".img-account-profile");
                    currentProfileImage.src = response.imagePath;
                } else {
                    const statusElement = document.getElementById('uploadStatus');
                    document.getElementById('uploadStatus').className = "";
                    document.getElementById('uploadStatus').classList.add("text-left", "rounded-0", "py-0", "mt-2", "alert", "alert-danger", "border", "border-danger");
                    const errorList = response.error.map(error => `<li class='pb-1'>${error}</li>`).join('');
                    statusElement.innerHTML = `<h6 class='fw-bold pt-1 mb-0'>Upload failed</h6><ul class='list-unstyled mb-0'>${errorList}</ul>`;
                }
            };
            xhr.onerror = () => {
                document.getElementById('uploadStatus').innerHTML = 'Upload failed: Network error';
            };
            xhr.send(formData);
        });
    }


    function toggleEditMode() {
        const editButton = document.getElementById("btneditprofile");
        const editableFields = document.querySelectorAll("[data-editable=true]");
        const nonEditableFields = document.querySelectorAll("[data-editable=false]");
        const navigationTabs = document.querySelectorAll("button[ data-bs-toggle=tab]");

        function enableEditMode() {
            editButton.textContent = "End Profile Editing";
            editButton.classList.replace("btn-outline-primary", "btn-primary");
            editButton.dataset.enabled = "true";

            navigationTabs.forEach((tab) => {
                tab.setAttribute("disabled", true);
            });

            editableFields.forEach((field) => {
                const badge = document.createElement("span");
                badge.classList.add("badge", "text-bg-info", "indicator", "mx-1");
                badge.textContent = "Editable";
                field.previousElementSibling.insertAdjacentElement("beforeend", badge);
                field.contentEditable = true;
            });

            nonEditableFields.forEach((field) => {
                const badge = document.createElement("span");
                badge.classList.add("badge", "text-bg-warning", "indicator", "mx-1");
                badge.textContent = "Read Only";
                field.previousElementSibling.insertAdjacentElement("beforeend", badge);
            });
        }

        function disableEditMode() {
            const updateStatusBadges = document.querySelectorAll("span.update-status");
            editButton.textContent = "Edit Profile";
            editButton.classList.replace("btn-primary", "btn-outline-primary");
            editButton.dataset.enabled = "false";

            navigationTabs.forEach((tab) => {
                tab.removeAttribute("disabled");
            });

            //Remove any status badges due to DB operations
            updateStatusBadges.forEach(badge => {
                if (badge) {
                    badge.remove();
                }
            })

            nonEditableFields.forEach((field) => {
                const indicatorBadge = field.previousElementSibling.querySelector(".badge.indicator");
                if (indicatorBadge) {
                    indicatorBadge.remove();
                }
            });

            editableFields.forEach((field) => {
                const indicatorBadge = field.previousElementSibling.querySelector(".badge.indicator");
                field.removeAttribute("contenteditable");

                if (indicatorBadge) {
                    indicatorBadge.remove();
                }

                // Remove Save and Cancel buttons
                const saveButton = field.nextElementSibling;
                const cancelButton = saveButton && saveButton.nextElementSibling;

                if (saveButton) {
                    saveButton.remove();
                }

                if (cancelButton) {
                    cancelButton.remove();
                }
            });
        }

        function handleFieldClick(event) {
            const field = event.currentTarget;
            const isEditModeEnabled = editButton.dataset.enabled === "true";

            if (isEditModeEnabled) {
                // Check if buttons already exist
                let saveButton = field.nextElementSibling;
                let cancelButton = saveButton && saveButton.nextElementSibling;

                if (!cancelButton) {
                    cancelButton = createButton("Cancel", "btn-danger", () => {
                        field.textContent = field.dataset.originalContent;
                        cancelButton.style.display = "none";
                        saveButton.style.display = "none";
                    });
                    field.parentNode.insertBefore(cancelButton, field.nextSibling);
                }

                if (!saveButton) {
                    saveButton = createButton("Save", "btn-primary", () => {
                        updateField(field);
                        cancelButton.style.display = "none";
                        saveButton.style.display = "none";
                    });
                    field.parentNode.insertBefore(saveButton, field.nextSibling);
                }
                cancelButton.style.display = "inline-block";
                saveButton.style.display = "inline-block";
            }
        }

        function createButton(text, className, clickHandler) {
            const button = document.createElement("button");
            button.textContent = text;
            button.classList.add("btn", "btn-sm", className, "mx-1", "mb-1", "rounded-0");
            button.addEventListener("click", clickHandler);
            return button;
        }

        function init() {
            if (!editButton) {
                return false;
            }
            editableFields.forEach((field) => {
                field.dataset.originalContent = field.textContent;
                field.addEventListener("click", handleFieldClick);
            });

            editButton.addEventListener("click", (e) => {
                e.preventDefault();
                const isEditModeEnabled = editButton.dataset.enabled === "true";
                isEditModeEnabled ? disableEditMode() : enableEditMode();
            });
        }

        // Initialize the functionality
        init();
    }


    async function updateField(field) {
        // Use this as a generic function to validate the data/field before send it to SQL
        // const profileElements = document.querySelectorAll("[contenteditable]");
        if (field.dataset.editable && field.contentEditable == "true") {
            let newValue = field.textContent;
            let oldValue = field.dataset.originalContent
            if (newValue != oldValue) {
                let fieldName = field.id.split("input")[1].toLowerCase();
                let userid = <?php echo $user["userid"]; ?>;
                let output = await updateDatabase(userid, fieldName, newValue);
                const existingBadge = field.previousElementSibling;
                if (existingBadge && existingBadge.classList.contains("badge", "output", "mx-1")) {
                    existingBadge.remove();
                }
                if (output.result == "error") {
                    const badgeError = document.createElement("span");
                    badgeError.classList.add("badge", "update-status", "text-bg-danger", "output", "mx-1");
                    badgeError.textContent = output.message;
                    field.insertAdjacentElement("beforeBegin", badgeError);
                } else if (output.result == "success") {
                    const badgeSuccess = document.createElement("span");
                    badgeSuccess.classList.add("badge", "update-status", "text-bg-success", "output", "mx-1");
                    badgeSuccess.textContent = output.message;
                    field.insertAdjacentElement("beforeBegin", badgeSuccess);
                }
            }
        } else {
            return false;
        }

        async function updateDatabase(userid, fieldName, fieldValue) {
            try {
                const response = await fetch('<?php echo WEBROOT; ?>/update-user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `userid=${encodeURIComponent(userid)}&field=${encodeURIComponent(fieldName)}&value=${encodeURIComponent(fieldValue)}`,
                });
                if (!response.ok) {
                    const errorText = await response.json();
                    throw new Error(errorText.error);
                } else {
                    const result = await response.json();
                    return {
                        "result": "success",
                        "message": result.success
                    };
                }
            } catch (error) {
                return {
                    "result": "error",
                    "message": error
                };
            }
        }
    }

    function updateCertificate() {
        const enrolCertificate = document.getElementById('enrol-certificate');
        let userid = <?php echo sanitise_user_input($_SESSION["userid"]); ?>;
        let errorDiv = document.getElementById("certificate-error");
        let certTable = document.getElementById("certificate-table");

        <?php if (isset($cert_info["enabled_by_user"])) : ?>
            var enableButton = document.getElementById("toggleCertificateState");
            var deleteButton = document.getElementById("deleteCertificate");
            var tdCertStatus = document.getElementById("td-cert-status");
            var certificateStatus = tdCertStatus.textContent.trim() === "Enabled" ? 1 : 0;
            var new_status = certificateStatus === 0 ? "Enabled" : "Disabled";
            let statusDiv = document.getElementById("certificate-status-change");


            enableButton.addEventListener("click", async (e) => {
                try {
                    if (certificateStatus === 0) {
                        change_status = await updateDatabase(userid, "enabled_by_user", 1);
                        new_status = "Enabled";
                    } else {
                        change_status = await updateDatabase(userid, "enabled_by_user", 0);
                        new_status = "Disabled";
                        <?php if ($_SESSION["login_method"] == "CLIENT_CERTIFICATE") : ?>
                            window.location.href = "logout.php?certificate-disabled";
                        <?php endif; ?>
                    }
                    statusDiv.className = '';
                    statusDiv.classList.add("text-center", "rounded-0", "py-0", "mt-2", "alert", "alert-success", "border", "border-success");
                    statusDiv.innerHTML = `<h6 class='fw-bold pt-1 mb-0'>Update Succesful</h6><p class="mb-0 pb-0">Certificate information has been <span class="fw-bolder">${new_status}</span>.<br>${change_status}</p>`
                    tdCertStatus.innerHTML = `<span class="p-0 fw-bold text-danger">${new_status}</span>`;
                    enableButton.parentNode.removeChild(enableButton);
                    deleteButton.parentNode.removeChild(deleteButton);
                } catch (error) {
                    console.error('Error:', error.message);
                }
            });

            deleteButton.addEventListener("click", async e => {
                try {
                    delete_certificate = await updateDatabase(userid, null, null, true);
                    statusDiv.className = '';
                    statusDiv.classList.add("text-center", "rounded-0", "py-0", "mt-2", "alert", "alert-success", "border", "border-success");
                    statusDiv.innerHTML = `<h6 class='fw-bold pt-1 mb-0'>Deletion Succesful</h6><p class="mb-0 pb-0">Certificate has been <span class="fw-bolder">deleted</span>.<br>You must log in using your username and password.</p><hr><p class='fw-semibold'>If you were logged in via certifiate authentication, then that session has been destroyed and you have been logged out.</h6>`
                    enableButton.parentNode.removeChild(enableButton);
                    deleteButton.parentNode.removeChild(deleteButton);
                } catch (error) {
                    console.error('Error', error.message);
                }
            });
        <?php endif; ?>

        enrolCertificate.addEventListener("click", async e => {
            // Can only be done by user, no-one else (including admins)
            try {
                enrol_new_certificate = await updateDatabase(userid);
                errorDiv.className = '';
                errorDiv.classList.add("text-center", "rounded-0", "py-0", "mt-2", "alert", "alert-success", "border", "border-success");
                errorDiv.innerHTML = `<h6 class='fw-bold pt-1 mb-0'>Enrolment Successful</h6><p class="mb-0 pb-0">${enrol_new_certificate}</p>`;
                if (certTable) {
                    certTable.remove();
                }
            } catch (error) {
                console.error('Error:', error.message);
            }
        });

        async function updateDatabase(userid, fieldName = null, fieldValue = null, delete_certificate = null) {
            try {
                const response = await fetch('<?php echo WEBROOT; ?>/auth/check-certificate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `userid=${encodeURIComponent(userid)}&field=${encodeURIComponent(fieldName)}&value=${encodeURIComponent(fieldValue)}&deletecertificate=${encodeURIComponent(delete_certificate)}`,
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    const errorReturned = errorData.error;
                    errorDiv.className = '';
                    errorDiv.classList.add("text-center", "rounded-0", "py-0", "mt-2", "alert", "alert-danger", "border", "border-danger");
                    errorDiv.innerHTML = `<h6 class='fw-bold pt-1 mb-0'>Enrolment Failed</h6><p class="mb-0 pb-0">${errorReturned}</p>`;
                    throw new Error(`HTTP error! Status: ${response.status}`);
                } else {
                    const result = await response.json();
                    return result;
                }
            } catch (error) {
                console.error('Error:', error.message);
                throw error;
            }
        }
    }

    loadDynamicElements();
    uploadProfilePicture();
    toggleEditMode();
    changeAccountStatus('lock');
    changeAccountStatus('unlock');
    <?php if ($_SESSION["username"] == $username) : ?>
        updateCertificate();
    <?php endif; ?>
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tabLinks = document.querySelectorAll(".nav-link");
        tabLinks.forEach((link) => {
            link.addEventListener("click", function(event) {
                this.preventDefault;
                const tabId = this.getAttribute("data-bs-target");
                const scrollPosition = window.scrollY;
                location.hash = tabId; // Update the URL hash
                window.scrollTo(0, -1); //Prevent scrolling
            });
        });
    });
</script>

<?php require_once(FILEROOT . "/footer.php"); ?>