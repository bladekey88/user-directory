<?php

// Set page title and import the header
@session_start();
require_once("assets/config/functions.php");
@require_once("./assets/config/auth.php");

// Profile page checks
if (isset($_GET["user"])) {
    $username = sanitise_user_input($_GET["user"], "username");
} else {
    $username = $_SESSION["username"];
}

//Global  Permission and Role Check
if (!check_user_permission(PERMISSION_VIEW_USER)) {
    @require_once($_SERVER['DOCUMENT_ROOT'] . "/errordocs/403.php");
    exit();
}

if ($username != $_SESSION["username"]) {
    if (!check_user_permission(PERMISSION_VIEW_ALL_USERS)) {
        header("Location:profile.php");
        exit();
    }
}

// Get user details from database
$user = run_sql(get_specific_user($username));
$row = mysqli_fetch_assoc($user);  #Only expect one result so don't loop TODO//FUNCTION THIS
if ($row) {
    // Handle Optional Fields (display only)
    if (strlen($row["middlename"]) == 0) {
        $middlename = htmlentities("No Value");
    } else {
        $middlename = $row["middlename"];
    }

    if (!$row["quidditch"] || $row["quidditch"] == 0) {
        $quidditch = null;
    } else {
        $quidditch = true;
    }

    if (!$row["path"]) {
        // $profile_image = "https://bootdey.com/img/Content/avatar/avatar1.png";
        $profile_image = "assets/img/user-default.png";
    } else {
        $profile_image = $row["path"];
    }

    $hidden = $row["locked"] ? "hidden" : null;
} else {
    echo "<div class='container my-5 fw-bold alert alert-warning rounded-0 border-0 border-start border-end border-danger border-5 text-danger h5'>
    Error - The user profile was not found. Please contact IT Services for further details, or return to the previous page to try again.
</div>";
    exit();
}

//Get user role and permission details for displayed user
// Get role details first
$user_role = run_sql(get_user_role($row["userid"]));
$user_role_result = mysqli_fetch_assoc($user_role);
if ($user_role_result) {
    $role = ucwords(strtolower(sanitise_user_input($user_role_result["role_name"])));
} else {
    $role = LANG_NO_ROLES;
}

//Now get permission details for the role
if (mysqli_num_rows($user_role) > 0) {
    $role_permissions = run_sql(get_role_permissions($user_role_result['role_id']));
    if (mysqli_num_rows($role_permissions) == 0) {
        $permissions = LANG_NO_PERMS;
    } else {
        $permissions = True;
    }
} else {
    $permissions = LANG_NO_PERMS;
}

// Start the html
$title =  $row["firstname"] . " " . $row["lastname"] . " - Profile Page";
require_once("header.php");

?>
<style>
    body {
        background-color: #f2f6fc;
    }

    .img-account-profile {
        height: 15rem;
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
</style>

<main>
    <div class="container-xl px-4 mt-4">
        <noscript>
            <div class="alert alert-warning border-danger text-danger rounded-0 p-0 py-1 ps-2 border-2">
                <i class="pe-2 bi bi-exclamation-triangle"></i>
                This page has limited functionality as Javascript is not enabled
            </div>
        </noscript>
        <div id="lockAccountStatus"></div>
        <?php if ($row["locked"] && (check_user_permission(PERMISSION_UNLOCK_USER) || check_user_permission(PERMISSION_LOCK_USER))) : ?>
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
                        <a id="btnunlockaccount" role="button" class="btn shadow btn-primary border-dark border-1 mb-1 rounded-0" href="/directory/change-account-status.php?action=unlock&user=<?php echo $username; ?>">
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
            <h4 class="m-0"><?php echo $row["firstname"] . " " . $row["lastname"]; ?></h4>
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
                                <div class="alert alert-warning border-danger border-2 m-0 p-0 rounded-0 ">
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
                                    <input type="hidden" name="idnumber" id="idnumber" value="<?php echo $row["idnumber"]; ?>">
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
                                <button class="nav-link" id="cert-info-tab" data-bs-toggle="tab" data-bs-target="#cert-info" type="button" role="tab" aria-controls="cert-info" aria-selected="false">Certificates</button>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="tab-content" id="tabContent">
                        <div class="tab-pane fade show active" id="account" role="tabpanel" aria-labelledby="account-tab" tabindex="0">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <p class="my-0">Account Details</p>
                                <div class="account-buttons" id="account-buttons">
                                    <?php if ((check_user_permission(PERMISSION_EDIT_OWN_PROFILE) && $username == $_SESSION["username"]) || check_user_permission(PERMISSION_EDIT_ANY_PROFILE)) : ?>
                                        <a role="button" class="btn btn-sm btn-outline-primary border-2 rounded-0" data-enabled="false" id="btneditprofile" href="edit-profile.php?user=<?php echo $row["username"]; ?>">Edit Profile</a>
                                    <?php endif; ?>
                                    <?php if (check_user_permission(PERMISSION_LOCK_USER) && $row["locked"] == 0) : ?>
                                        <a role="button" id="btnlockaccount" class="btn btn-sm btn-outline-danger border-2 rounded-0 <?php echo $hidden; ?>" href="/directory/change-account-status.php?action=lock&user=<?php echo $username; ?>">
                                            <i class="bi bi-lock"></i>Lock Account
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div id="account-details" class="card-body ms-4">
                                <!-- Form Group (username)-->
                                <div class="mb-2 border-bottom">
                                    <label class="small mb-1" for="inputUsername">Username</label>
                                    <p id="inputUsername" data-editable="false" class="fw-bold"><?php echo $row["username"]; ?></p>
                                </div>
                                <!-- Form Row-->
                                <div class="row gx-3 mb-2">
                                    <!-- Form Group (first name)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputFirstName">First name</label>
                                        <p id="inputFirstName" data-editable="false" class="fw-bold"><?php echo $row["firstname"]; ?></p>
                                    </div>
                                    <!-- Form Group (last name)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputLastName">Last name</label>
                                        <p id="inputLastName" data-editable="false" class="fw-bold"><?php echo $row["lastname"]; ?></p>
                                    </div>
                                </div>
                                <!-- Form Row-->
                                <div class="row gx-3 mb-2 border-bottom">
                                    <!-- Form Group (Other name)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputCommonName">Preferred Name/Title</label>
                                        <p id="inputCommonName" data-editable="true" class="fw-bold"><?php echo $row["commonname"]; ?>
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
                                        <p id="inputEmailAddress" data-editable="false" class="fw-bold"><?php echo $row["email"]; ?></p>
                                    </div>
                                    <!-- Form Group (ID number)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputID">ID number</label>
                                        <p id="inputID" data-editable="false" class="fw-bold"><?php echo $row["idnumber"]; ?></p>
                                    </div>
                                </div>
                                <div class="row gx-3 mb-2">
                                    <!-- Form Group (Country)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputCountry">Country</label>
                                        <p id="inputCountry" data-editable="true" class="fw-bold"><?php echo $row["country"]; ?></p>
                                    </div>
                                    <!-- Form Group (City)-->
                                    <div class="col-md-6">
                                        <label class="small mb-1" for="inputCity">City</label>
                                        <p id="inputCity" data-editable="true" class="fw-bold"><?php echo $row["city"]; ?></p>
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
                                        <?php
                                        if (gettype($permissions) == "boolean") : ?>
                                    <ul>
                                        <?php
                                            while ($role_perm_result = mysqli_fetch_assoc($role_permissions)) {
                                                echo "<li id='" . $role_perm_result["permission_id"] . "' class='mb-1 fw-bolder' >
                                                        <code>" . $role_perm_result["permission"] . "</code>
                                                    </li>";
                                            }
                                        ?>
                                    </ul>
                                <?php else :
                                            echo $permissions;
                                        endif;  ?>
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
                                            <p id="inputHouse" data-editable="false" class="fw-bold"><?php echo $row["house"]; ?></p>
                                        </div>
                                        <div>
                                            <label class="small mb-1" for="inputYear">Year</label>
                                            <p id="inputYear" data-editable="false" class="fw-bold"><?php echo $row["year"]; ?></p>
                                        </div>
                                        <?php if ($quidditch) : ?>
                                            <div>
                                                <label class="small mb-1" for="inputTeam">Quidditch Team</label>
                                                <p id="inputTeam" data-editable="false" class="fw-bold">Team Member</p>
                                            </div>
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
                                    $ldap_user = ldap_get_user_info($row["username"])[0]; //Expect only user, therefore retrn first index
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
                                                <tr style='cursor:auto'>
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

                        <?php if ($_SESSION["username"] == $row["username"]) : ?>
                            <div class="tab-pane fade show active" id="cert-info" role="tabpanel" aria-labelledby="cert-info-tab" tabindex="0">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <p class="my-0">Certificate Information</p>
                                </div>
                                <div id="account-certificates" class="card-body ms-4">
                                    <h6>Enrol Certificate</h6>
                                    <button id="enrol-certificate" type="button" class="btn btn-primary rounded-0 btn-sm">Enrol Current Certificate</button>
                                    <button type="button" class="btn btn-primary rounded-0 btn-sm disabled">Upload Certificate File</button>
                                    <div id="certificate-error"></div>

                                    <?php
                                    $cert_info = get_certificate_information();
                                    if ($cert_info) : ?>
                                        <table class="styled-table" id="certificate-table" name="certificate-table" style="width:100%;">
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
                                                    <td><?php echo ($cert_info["enabled_by_user"] == 1) ? "Enabled" : "Disabled"; ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Options</th>
                                                    <td>
                                                        <button class="mx-2 btn btn-sm btn-primary" type="button">Enable/Disable</button>
                                                        <button class="mx-2 btn btn-sm btn-danger" type="button">Delete</button>
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
            profilePictureForm && profilePictureForm.classList.remove("d-none");
            tabProfileTab && tabProfileTab.classList.remove("d-none");
            //Get each tab-pane class and remove show class
            let tabPanes = document.querySelectorAll(":not(#account).tab-pane");
            tabPanes.forEach((field) => {
                field.classList.remove("show", "active");
            });
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
                const response = await fetch(`change-account-status.php?action=${encodeURIComponent(action)}&user=<?php echo urlencode($username); ?>`);
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
            xhr.open('POST', 'upload-profile-picture.php');

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
            editButton.textContent = "Edit Profile";
            editButton.classList.replace("btn-primary", "btn-outline-primary");
            editButton.dataset.enabled = "false";

            navigationTabs.forEach((tab) => {
                tab.removeAttribute("disabled");
            });

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

    function updateField(field) {
        // Use this as a generic function to validate the data/field before send it to SQL
        // const profileElements = document.querySelectorAll("[contenteditable]");
        if (field.dataset.editable && field.contentEditable == "true") {
            let newValue = field.textContent;
            let oldValue = field.dataset.originalContent
            if (newValue != oldValue) {
                let fieldName = field.id.split("input")[1].toLowerCase();
                let userid = <?php echo $row["userid"]; ?>;
                updateDatabase(userid, fieldName, newValue);
            }
        } else {
            return false;
        }

        async function updateDatabase(userid, fieldName, fieldValue) {
            try {
                const response = await fetch('update-user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `userid=${encodeURIComponent(userid)}&field=${encodeURIComponent(fieldName)}&value=${encodeURIComponent(fieldValue)}`,
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const result = await response.text();
                console.log(result); // Handle the response from the server
            } catch (error) {
                console.error('Error:', error.message);
            }
        }
    }

    function updateCertificate() {
        const enrolCertificate = document.getElementById('enrol-certificate');
        let userid = <?php echo sanitise_user_input($_SESSION["userid"]); ?>;
        let errorDiv = document.getElementById("certificate-error")

        enrolCertificate.addEventListener("click", (e) => {
            // Can only be done by user, no-one else (including admins)
            updateDatabase(userid);
        });

        async function updateDatabase(userid, fieldName, fieldValue) {
            try {
                const response = await fetch('auth/check-certificate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `userid=${encodeURIComponent(userid)}`,
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    const errorReturned = errorData.error;
                    errorDiv.className = '';
                    errorDiv.classList.add("text-center", "rounded-0", "py-0", "mt-2", "alert", "alert-danger", "border", "border-danger");
                    errorDiv.innerHTML = `<h6 class='fw-bold pt-1 mb-0'>Enrolment Failed</h6><p class="mb-0 pb-0">${errorReturned}</p>`;
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                if (response.ok) {
                    const result = await response.json();
                    console.log(result);
                    errorDiv.className = '';
                    errorDiv.classList.add("text-center", "rounded-0", "py-0", "mt-2", "alert", "alert-success", "border", "border-success");
                    errorDiv.innerHTML = `<h6 class='fw-bold pt-1 mb-0'>Enrolment Succeeded</h6><p class="mb-0 pb-0">${result}</p>`
                }
            } catch (error) {
                console.error('Error:', error.message);
            }
        }

    }


    loadDynamicElements();
    uploadProfilePicture();
    toggleEditMode();
    changeAccountStatus('lock');
    changeAccountStatus('unlock');
    updateCertificate();
</script>
<?php require_once("footer.php"); ?>