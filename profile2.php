<!-- TODO ADD IMAGE UPLOAD STATS -->
<?php
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
        redirect("/profile2.php", "HTTP/1.1 404 Not Found");
        exit();
    }
}

// Get user details from database
$get_user = run_sql2(get_specific_user($username));

if (!$get_user) {
    redirect("/profile2.php", "HTTP/1.1 404 Not Found");
    exit();
} else {
    $user = $get_user[0];
    $username = $user["username"];
    $userid = $user["userid"];
    $t_idnumber = $user["idnumber"];
    if (!$_GET["idnumber"] || ($_GET["idnumber"] != $t_idnumber)) {
        redirect("/profile2.php?user=$username&idnumber=$t_idnumber", "HTTP/1.1 301 Moved Permanently");
        exit();
    }
}

/// Get profile picture if its exists else show default
if (!$user["path"]) {
    $profile_image = "https://secure.hogwarts.wiz/directory/uploads/2025/01/02/57693158-6776ce11114d4.png";
} else {
    $profile_image = $user["path"];
}

// Generate the page title
$title =  $user["firstname"] . " " . $user["lastname"] . " - Profile Page";

// Get house crest 
$house_crest = "assets/img/crest-" . substr($user["house"], 0, 2) . ".png";

// Get house year
$year_mapping = [
    "zero" => 0,
    "first" => 1,
    "second" => 2,
    "third" => 3,
    "fourth" => 4,
    "fifth" => 5,
    "sixth" => 6,
    "seventh" => 7,
    "staff" => "s",
    "unknown" => "-"
];
$year_as_int = $year_mapping[explode(" ", strtolower($user["year"]))[0]] ?? "-";
$year_crest = "assets/img/$year_as_int.png";

// Get Permissions for role
//Now get permission details for the role
if ($user["role_name"]) {
    $role_permissions = run_sql2(get_role_permissions_by_role_name($user['role_name']));
    if (count($role_permissions) == 0) {
        $permissions = LANG_NO_PERMS;
    } else {
        $permissions = True;
    }
} else {
    $permissions = LANG_NO_PERMS;
}

// General Editing
$editable = check_current_user_can_edit_user($user["userid"]);

// Get access information
# As this relies on a multiquery (running through several stored procedure)
# Scope the php query code here
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, "status");
$mysqli->multi_query("CALL getSystemStatus('$username')");

// Iterate and store in an array so we can check if there are any results
$access_results = [];
do {
    if ($result = $mysqli->store_result()) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $access_results[] = $rows;
        $result->free();
    }
} while ($mysqli->next_result());

$hasResults = false;
foreach ($access_results as $rowset) {
    if (count($rowset) > 0) {
        $hasResults = true;
        break;
    }
}

# Also call openfire check status
# if the user flag exists, it means the account is locked
$mysqli->multi_query("CALL GetUserStatusOpenfire('$username')");

do {
    if ($chat_result = $mysqli->store_result()) {
        $chat_rows = $chat_result->fetch_all(MYSQLI_ASSOC);
    }
} while ($mysqli->next_result());
$openfire_status = count($chat_rows) > 0 ? "Disabled" : "Enabled";

// VLE Specific set up
// Get vle for specified user from get params or get from session
if (check_user_permission(PERMISSION_VIEW_ALL_USERS)) {
    $vle_username = htmlentities($user["username"]);
    $idnumber = htmlentities($user["idnumber"]);
} else {
    $vle_username = $_SESSION["username"];
    $idnumber = $_SESSION["idnumber"];
}
$vle_account = false;
$account_exist = true;

// Check the user exists
$username_exists = run_sql2(get_attribute_exists("username", $vle_username));
$idnumber_exists = run_sql2(get_attribute_exists("idnumber", $idnumber));

if (!$username_exists || !$idnumber_exists) {
    $account_exist = false;
}

// Use named parameters (i.e. function variable: overide data to get this to work PHP 8+ only)
$get_user_vle_info_query = run_sql2(get_user_vle_info($vle_username, $idnumber), database: MOODLE_DB);
$get_user_vle_cohort_query = run_sql2(get_user_vle_cohort($vle_username, $idnumber), database: MOODLE_DB);
$get_user_vle_enrolment_query = run_sql2(get_user_vle_enrolments($vle_username, $idnumber), database: MOODLE_DB);

if ((count($get_user_vle_info_query) +
    count($get_user_vle_cohort_query) +
    count($get_user_vle_enrolment_query)
) > 0) {
    $vle_account = true;
    $vle_info = $get_user_vle_info_query[0];  //This should only ever return one result
}

// LDAP Query
// Get the information from LDAP
$ldap_user = ldap_get_user_info($user["username"])[0]; //Expect only user, therefore return first index
if (gettype($ldap_user) == "array") {
    ksort($ldap_user);
}

// Move this to config
$core_ldap_attributes = [
    "dn",
    "cn",
    "displayname",
    "employeenumber",
    "employeetype",
    "givenname",
    "headofhouse",
    "jpegphoto",
    "l",
    "mail",
    "mailalias",
    "manager",
    "o",
    "objectclass",
    "ou",
    "overridequidditchrestriction",
    "prefect",
    "preferredfullname",
    "preferredlanguage",
    "quidditchplayer",
    "schoolhouse",
    "schoolyear",
    "sex",
    "sn",
    "suspendedaccount",
    "title",
    "uid",
];

// Certificate Setup
$cert_info = get_certificate_information($user["userid"]);
$cert_toggle = "false";
$cert_toggle_disabled = "disabled";

if ($cert_info) {
    $cert_login_status = $cert_info["enabled_by_user"] ? "enabled" : "disabled";
    $cert_toggle = $cert_info["enabled_by_user"] ? "true" : "false";

    // Disable toggle under circumstances
    if ($editable === True) {
        // Only allow user, or admin and senior staff roles to edit (regardless of general editing)
        if ((check_user_has_any_of_roles([ROLE_SENIOR_STAFF, ROLE_ADMIN])) || ($_SESSION["userid"] == $user["userid"])) {
            $cert_toggle_disabled = null;
        }
    }
}

$presented_cert = $_SERVER["SSL_CLIENT_VERIFY"] == "SUCCESS" ? True : False;

// Bring in header and begin page load
@require_once(FILEROOT . "/components/header.php");

?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.css" integrity="sha512-087vysR/jM0N5cp13Vlp+ZF9wx6tKbvJLwPO8Iit6J7R+n7uIMMjg37dEgexOshDmDITHYY5useeSmfD1MYiQA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        text-align: center;
    }

    .user-profile-picture {
        grid-row: 1;
        color: white;
    }

    .user-profile-picture .img-account-profile {
        max-width: 12.5rem;
        aspect-ratio: 4/5;
        object-fit: cover;
        border-radius: 0.25rem;
        margin: auto;
    }

    .user-account-details {
        color: black;
        text-align: center;
    }

    .user-account-details~.command-section {
        margin-inline: 1rem;
    }

    .user-basic-aspects,
    .user-icons {
        margin-top: 0.5rem;
        display: flex;
        gap: 1rem;
        justify-content: center;
        align-items: center;
    }

    .user-basic-aspects span {
        margin-top: 0.5rem;
        display: flex;
        gap: 0.5rem;
        align-items: center;
        justify-content: flex-start;
        flex-grow: 0;
    }

    .user-details {
        display: grid;
        gap: 1rem 1.5rem;
        align-items: self-start;
        padding-inline-end: 0.25rem;
        margin-inline: 1rem;
    }


    .user-basic-aspects img {
        width: clamp(1rem, 0.5rem + 1vw, 3rem)
    }

    .user-details label {
        font-weight: 600;
        display: block;
    }

    .user-icon {
        display: flex;
        flex-direction: column;
        justify-items: flex-start;
        align-items: center;
        padding-inline: 0.75rem;
        padding-top: 0.75rem;
        padding-bottom: 0;
        border-radius: 0.25rem;
    }

    .user-icon img {
        max-width: 7rem;
        max-height: 7rem;
        aspect-ratio: 1/1;
        object-fit: fill;
        margin-bottom: 0rem;
        padding: 0;
    }

    .user-icon p {
        margin-block: 0;
        padding-block: 0.55rem;
    }

    .user-account-status .alert {
        border-radius: 0;
        box-shadow: none;
    }

    .user-account-status p {
        text-align: center;
    }

    #alertVleSuspended {
        text-align: center;
    }

    .highlight {
        font-size: 1.15rem;
        margin-block: 1rem;
        text-align: center;
        outline: solid 1px var(--colour-primary-dark);
        background-color: var(--colour-subtle);
    }

    .certificate-comparison>* {
        flex: 1 1 0;
    }

    #certificate-login {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
    }

    #certificate-login span {
        font-size: 0.75rem;
        font-style: italic;
    }

    .secondary-information {
        display: none;
    }


    /* DIALOG */
    dialog .container {
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        gap: 1rem;
        margin-block-end: 1rem;
    }

    dialog .container>div>div {
        border: 2px solid black;
    }

    dialog img {
        display: block;
        max-width: 100%;
    }

    dialog .img-container {
        display: block;
        min-width: 12.5rem;
        min-height: 15.625rem;
        max-width: 20rem;
    }

    dialog #img-preview {
        display: block;
        width: 12.5rem;
        height: 15.625rem;
        /* min-width: 0px 
            min-height: 0px 
            max-width: none; */
        overflow: hidden;
    }

    .img-container.show,
    #img-preview.show {
        display: block;
    }


    dialog div:has(#getImage) {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        align-items: center;
        justify-content: flex-start;
    }

    dialog div:has(#getImage)>* {
        flex: 1 1 10rem;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }


    #profilePictureMessages {
        display: none;
    }

    @media screen and (min-width: 50rem) {

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            text-align: unset;
        }

        .user-profile-picture .img-account-profile {
            margin: 1rem;
        }

        .user-account-details {
            margin-right: 1rem;
            grid-row: 1;
            grid-column: 2;
            text-align: start;
        }

        .user-basic-aspects {
            justify-content: flex-start;
        }

        .user-details {
            grid-template-columns: 1fr 1fr;
        }

        .user-account-details~.command-section {
            grid-row: 2;
            grid-column-start: 1;
            grid-column-end: -1;
            margin-inline: 1rem;
        }

        .user-account-status p {
            text-align: start;
        }

        .highlight {
            text-align: start;
            font-size: unset;
            outline: unset;
            background-color: inherit;
        }

        #alertVleSuspended {
            text-align: start;
        }

        .secondary-information {
            display: revert;
        }
    }

    @media screen and (min-width: 1000px) {
        .user-details {
            grid-template-columns: repeat(3, 1fr);
        }
    }
</style>


<div class="wrapper">
    <?php require("components/sidebar.php"); ?>
    <main class="content">
        <?php require("components/nav-header.php"); ?>

        <section id="user-overview">
            <noscript>
                <section id="site-messages">
                    <div class="alert danger alert-double" role="status">
                        <img class="alert-icon" src="assets/img/icon-warning2.svg" alt="javascript disabled"
                            label="javascript disabled warning icon">
                        <span>Javascript disabled. This site will have reduced functionality.</span>
                    </div>
                </section>
            </noscript>
            <h1>
                <?php
                echo "User Profile: $user[firstname] $user[lastname]";
                if ($user["firstname"] <> $user["commonname"]): ?>
                    <span class="fs-5">(<?php echo $user["commonname"]; ?>)</span>
                <?php endif; ?>
            </h1>

            <nav class="page-sections-navigation">
                <ul>
                    <li><a href="#user-overview">Overview</a></li>
                    <li><a href="#role-information">Roles and Permissions</a></li>
                    <li><a href="#school-information">School</a></li>
                    <li><a href="#access-information">Accesses</a></li>
                    <li><a href="#vle-information">VLE</a></li>
                    <li><a href="#ldap-information">HADS</a></li>
                    <li><a href="#certificate-information">Certificate Information</a></li>
                </ul>
            </nav>

            <div class="user-account-status">
                <?php if ($user["locked"]) : ?>
                    <div class="alert danger alert-double" role="status">
                        <h2>
                            Account Locked
                        </h2>
                        <hr>
                        <p> The user will not be able to log in to directory services. This status may also propagate to downstream systems.</p>
                        <?php if (check_user_permission(PERMISSION_UNLOCK_USER) && $editable === True): ?>
                            <div class="command-section">
                                <h3>Unlock Account</h3>
                                <hr>
                                <div class="command-option">
                                    <button type="button" name="Unlock Account" id="btnUnlockAccount" class="button">
                                        <img class="alert-icon" src='assets/img/icon-locked.svg' alt='Account Locked' title='Account Locked'>
                                        <span>Unlock Account</span>
                                    </button>
                                    <span>
                                        You can unlock the account directly using the '<em>Unlock Account</em> ' button, or IT Services can reset the account.
                                    </span>
                                </div>
                                <noscript>
                                    <p>Javascript is disabled. Please click <a href="<?php echo WEBROOT; ?>/change-account-status.php?action=unlock&user=<?php echo $username; ?>">this link</a> to unlock the account.</p>
                                </noscript>
                                <div id="unlockAccountStatus"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif ?>

            </div>

            <section id="user-heading" class="page-section">

                <div class="user-profile-picture">
                    <img class="img-account-profile" src="<?php echo $profile_image; ?>" alt="Profile photo">
                </div>

                <!-- <div class="user-basic-details"> -->
                <div class="user-account-details">
                    <h2>
                        <?php echo "$user[firstname] $user[middlename] $user[lastname]"; ?>
                    </h2>
                    <div class="user-basic-aspects">
                        <span>
                            <img src="assets/img/icon-sex.svg" alt="Sex: " aria-hidden="true" label="sex">
                            <?php echo $user["sex"]; ?>
                        </span>
                        <span>
                            <img src="assets/img/icon-role.svg" alt="Role: " aria-hidden="true" label="role">
                            <?php echo $user["role_name"]; ?>
                        </span>
                        <span>
                            <img src="assets/img/icon-id.svg" alt="Hogwarts ID: " aria-hidden="true" label="Hogwarts ID">
                            <?php echo $user["idnumber"]; ?></span>
                    </div>
                    <div class="user-details information-section">
                        <div>
                            <label for="user-detail-firstname">First Name</label>
                            <?php if (check_user_has_any_of_roles([ROLE_ADMIN, ROLE_SENIOR_STAFF])): ?>
                                <span data-editable="true" id="user-detail-firstname"><?php echo $user["firstname"]; ?></span>
                            <?php else: ?>
                                <span data-editable="false" id="user-detail-firstname"><?php echo $user["firstname"]; ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="user-detail-middlename">Middle Name</label>
                            <span id="user-detail-middlename" data-editable="true">
                                <?php
                                if ($user["middlename"] == "") {
                                    echo "<em>No Middle Name</em>";
                                } else {
                                    echo $user["middlename"];
                                } ?>
                            </span>
                        </div>
                        <div>
                            <label for="user-detail-lastname">Last Name</label>
                            <?php if (check_user_has_any_of_roles([ROLE_ADMIN, ROLE_SENIOR_STAFF])): ?>
                                <span id="user-detail-lastname" data-editable="true"> <?php echo $user["lastname"]; ?></span>
                            <?php else: ?>
                                <span id="user-detail-lastname" data-editable="false"> <?php echo $user["lastname"]; ?></span>

                            <?php endif; ?>

                        </div>
                        <div>
                            <label for="user-detail-commonname">Form of Address</label>
                            <span id="user-detail-commonname" data-editable="true"> <?php echo $user["commonname"]; ?></span>
                        </div>
                        <div>
                            <label for="user-detail-username">Username</label>
                            <span id="user-detail-username" data-editable="false"> <?php echo $user["username"]; ?></span>
                        </div>
                        <div>
                            <label for="user-detail-email">Email Address</label>
                            <span id="user-detail-email" data-editable="false"> <?php echo $user["email"]; ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($editable === true && (check_user_permission(PERMISSION_EDIT_ANY_PROFILE) || check_user_permission((PERMISSION_EDIT_OWN_PROFILE)))): ?>
                    <div class="command-section">
                        <h3>Account Options</h3>
                        <hr>
                        <div class="command-option">
                            <button class="button" type="button" id="btn-edit-account">
                                <img class="filter-icon" src="assets/img/icon-user-edit.svg" aria-hidden="true" alt="User outline with a pencil overlaid">
                                <span>Edit Account</span>
                            </button>
                            <span class="btn-information">Click the button to edit account details.</span>
                        </div>
                        <div class="command-option">
                            <button class="button dialog-show" type="button" id="btn-edit-profile-picture" data-linked-dialog="edit-profile-modal" disabled>
                                <img class="filter-icon" src="assets/img/icon-profile-picture-edit.svg" aria-hidden="true" alt="Picture with a box with a star overlaid">
                                <span>Edit Profile Picture</span>
                            </button>
                            <span class="btn-information">Click the button to change profile picture.</span>
                            <noscript>
                                <div class="alert danger alert-double">
                                    <img class="alert-icon" src="assets/img/icon-info.svg" aria-hidden="true" alt="information icon" label="icon indicating this alert message is informational">
                                    <span>Javascript is required to change the profile picture.</span>
                                </div>
                            </noscript>
                        </div>
                        <?php if (check_user_permission(PERMISSION_LOCK_USER) && (!$user["locked"])) : ?>
                            <div class="command-option">
                                <button class="button" type="button" id="btnLockAccount">
                                    <img class="filter-icon" src="assets/img/icon-lock.svg" aria-hidden="true" alt="User outline with a padlock overlaid">

                                    <span>Lock Account</span>
                                </button>
                                <span>Locking the account denies Directory access to the user.</span>
                            </div>
                        <?php endif; ?>

                        <?php if (check_user_permission(PERMISSION_HIDE_USER)) : ?>
                            <div class="command-option">
                                <button class="button" type="button" id="btnHideAccount">
                                    <img class="filter-icon" src="assets/img/icon-user-hide.svg" aria-hidden="true" alt="An eye with a cross through it">
                                    <span>Hide Account</span>
                                </button>
                                <span>Hiding the account locks and removes visibility from Directory.</span>
                            </div>
                        <?php endif; ?>
                        <div id="genericAccountStatus"></div>
                    </div>

                    <dialog id="edit-profile-modal">
                        <div class="dialog-title">
                            <h3>Change Profile Image</h3>
                            <button type="button" class="button close-modal" data-linked-dialog="edit-profile-modal">
                                <img class="filter-icon" src="assets/img/icon-close.svg" alt="Close Modal Dialog" aria-hidden="true">
                                Close (Unsaved changes will be discarded)
                            </button>
                        </div>
                        <div class="dialog-body command-section">
                            <form id="formUpdateProfilePicture" data-idnumber-key=<?php echo $user["idnumber"]; ?>>

                                <div>
                                    <label for="inputProfilePicture">Upload New Profile Picture</label>
                                    <input type="file" name="profilePicture" id="inputProfilePicture" accept="image/*">
                                    <div id="profile-image-info">
                                        <h4>File Information</h4>
                                        <div id="img-info">
                                        </div>
                                    </div>
                                    <p class="information-section">
                                        Ideally, images should be at least 200px by 200px. Images that are smaller than this will get scaled up and may look blurry.
                                        Once the image is uploaded, you can drag either the image or the box below to select a crop area.
                                        You can also scroll the mouse wheel to zoom in and out.
                                        Once you are satisifed with the preview image (on the right), click 'Update Profile Picture'.
                                    </p>
                                </div>
                                <div id="uploaded-images-container" class="container">
                                    <div name="uploaded-image">
                                        <h4>Uploaded Image</h4>
                                        <div class="img-container">
                                            <img src="" id="profileImage" alt="No Image" />
                                        </div>
                                    </div>
                                    <div name="profile-preview">
                                        <h4>Profile Picture Preview</h4>
                                        <div id="img-preview">
                                            <img alt="No Image" id="profileImagePreview" />
                                        </div>
                                    </div>
                                </div>
                                <div id="profilePictureMessages"></div>
                                <div class="form-controls">
                                    <button class="button" id="getImage" type="submit">
                                        <img src="assets/img/icon-save.svg" aria-hidden="true" alt="save icon" />
                                        <span>Update Profile Picture</span>
                                    </button>
                                    <button class="button" id="resetForm" type="reset">
                                        <img src="assets/img/icon-clear.svg" aria-hidden="true" alt="reset icon" />
                                        <span>Discard Changes</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </dialog>

                <?php endif; ?>

                <!-- </div> -->
            </section>

            <section id="role-information" class="page-section-no-grid">
                <h2>Directory Role and Permissions</h2>
                <div class="information-section">
                    <span>
                        The role you are assigned determines the permissions you have within the Directory Service.
                        This role is synced downstream to other systems where appropriate, though such systems may have
                        different names or concepts.
                    </span>
                    <p>
                        Additionally, further permissions can be granted in such systems but
                        these do not sync back to the Directory System (by design).
                    </p>
                </div>
                <h3>Role Name</h3>
                <p class="highlight"><?php echo $user["role_name"]; ?></p>
                <h3>Permissions</h3>
                <p id=" permissions-text">
                    <?php if (is_bool($permissions)) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($role_permissions as $role_perm_result) : ?>
                            <tr>
                                <td id="<?php echo $role_perm_result["permission_id"]; ?>">
                                    <code>
                                        <?php echo $role_perm_result["permission"]; ?>
                                    </code>
                                </td>
                                <td>
                                    <?php echo $role_perm_result["description"]; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <?php echo $permissions; ?>
            <?php endif;  ?>
            </p>
            </section>

            <section id="school-information" class="page-section-no-grid">
                <h2>School Information</h2>
                <div class="user-icons information-section">
                    <div class="user-icon">
                        <img src="<?php echo $house_crest; ?>" aria-hidden="true" alt="<?php echo $user["house"]; ?>" title="House: <?php echo $user["house"]; ?>">
                        <p>House: <?php echo $user["house"]; ?></p>
                    </div>
                    <div class="user-icon">
                        <img src="<?php echo $year_crest; ?>" aria-hidden="true" alt="<?php echo $user["year"]; ?>" title="Year: <?php echo $user["year"]; ?>">
                        <p>Year: <?php echo $user["year"]; ?></p>
                    </div>
                    <?php if ($user["prefect"]): ?>
                        <div class="user-icon">
                            <?php
                            if (str_contains(strtolower($user["prefect"]), "house")) {
                                $prefect_badge = "prefect_house.png";
                            } elseif (str_contains(strtolower($user["prefect"]), "head")) {
                                $prefect_badge = "prefect_hbhg.png";
                            }
                            ?>
                            <img src="assets/img/<?php echo $prefect_badge; ?>" aria-hidden="true" alt="<?php echo $user["prefect"]; ?>" title="Prefect: <?php echo $user["prefect"]; ?>">
                            <p>Prefect: <?php echo $user["prefect"]; ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="user-icon">
                        <?php if ($user["quidditch"]) {
                            switch ($user["sex"]) {
                                case "Male":
                                    $quidditch_icon =  "quidditch_M.png";
                                    break;
                                case "Female":
                                    $quidditch_icon =  "quidditch_F.png";
                                    break;
                                default:
                                    $quidditch_icon =  "quidditch.png";
                            }
                            echo "<img src='assets/img/$quidditch_icon' aria-hidden='true' alt='Icon showing quidditch player on broom'>";
                            echo "Quidditch Player";
                        } ?>
                    </div>
                </div>
            </section>

            <section id="access-information" class="page-section-no-grid">
                <h2>System Accesses</h2>
                <div class="information-section">
                    <span>
                        You can request acceses to various systems from the access catalogue.
                        The request will go through an approval process, and you will receive an
                        email with the outcome.
                    </span>
                    <p>
                        Please note, that unless urgent, <em>most</em> access approvals take a working day.
                    </p>
                </div>

                <?php
                // Only render the tables if there are results
                if ($hasResults): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>System</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($access_results as $rowset) :
                                foreach ($rowset as $row) : ?>
                                    <tr>
                                        <?php
                                        $class = (strtolower($row["status"]) == "disabled");
                                        $alertClass = $class ? 'text-danger emphasis' : '';
                                        ?>
                                        <td> <?php echo $row["system"]; ?></td>
                                        <td> <?php echo $row["username"]; ?></td>
                                        <td class="<?php echo $alertClass; ?>"> <?php echo $row["status"]; ?></td>
                                        <td>
                                            <a href="<?php echo $row["link"]; ?>" target='_blank'>
                                                Click link to access <?php echo $row["system"]; ?>
                                            </a>
                                        </td>
                                    </tr>
                            <?php
                                endforeach;
                            endforeach;

                            # Special override for openfire due to it not storing ldap data in DB
                            # And disabling user is handling only within DB
                            if (is_array($ldap_user)) {
                                $target_value = "openfire";
                                $filteredArray = array_filter($ldap_user["groups"], function ($item) use ($target_value) {
                                    return $item['ou'] === $target_value;
                                });

                                if (count($filteredArray) > 0) {
                                    // Get openfire suspended status
                                    echo "<tr>";
                                    echo "<td>Openfire (Chat)</td>";
                                    echo "<td>{$ldap_user["uid"]}</td>";
                                    echo "<td>{$openfire_status}</td>";
                                    echo "<td>N/A - Use IM Client such as Pidgin</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert info" role="status">
                        <img class="alert-icon" src="assets/img/icon-info.svg" aria-hidden="true" alt="exclamation icon" label="icon indicating this alert message is a warning" />
                        <span>
                            No access information found.
                        </span>
                    </div>

                <?php endif; ?>
            </section>

            <section id="vle-information" class="page-section-no-grid">
                <div>
                    <?php if (isset($vle_info)): ?>
                        <?php
                        if (isset($_GET["user"]) && ($vle_username <> $_SESSION["username"]) && check_user_permission(PERMISSION_VIEW_ALL_USERS)) : ?>
                            <div class="alert warning" role="status">
                                <img class="alert-icon" src="assets/img/icon-warning2.svg" aria-hidden="true" alt="exclamation icon" label="icon indicating this alert message is a warning">
                                <span>You are viewing the details of another user's VLE account. Any verbiage referring to you, indcates that user and not yourself.</span>
                            </div>
                        <?php endif; ?>
                        <h2 class="m-0">HogwartsVLE Account Details for <?php echo $vle_info["firstname"] . " " . $vle_info["lastname"]; ?></h2>
                    <?php elseif (isset($_GET["user"])): ?>
                        <h2 class="m-0">HogwartsVLE Account Details for <?php echo $_GET["user"]; ?></h2>
                    <?php else: ?>
                        <h2 class="m-0"><?php echo $_SESSION["firstname"] . " " . $_SESSION["lastname"]; ?></h2>
                    <?php endif; ?>

                    <?php
                    if (!$account_exist) : ?>
                        <div class="alert danger alert-double" role="status">
                            <h3>VLE Account for User not found</h3>
                            <hr>
                            <p>The details provided do not match any known user - <?php echo " username: <strong>$vle_username</strong> and idnumber: <strong>$idnumber</strong>." ?> </p>
                        </div>
                    <?php #die();
                    endif; ?>

                    <?php
                    if (isset($vle_info) &&  $vle_info["suspended"] == "true") : ?>
                        <div class="alert danger alert-double" role="status" id="alertVleSuspended">
                            <div>
                                <img class=" alert-icon" src="assets/img/icon-stop.svg" aria-hidden="true" alt="stop/no-entry icon" label="icon indicating this alert message is a danger" />
                                <span class="emphasis">VLE Account Suspended</span>
                            </div>
                            <hr>
                            <p>You will not be able to login in to any VLE services. Please contact ITServices for further details.</p>
                            <p><a target="_blank" rel="external noreferrer" href="/vle/">Visit VLE (Opens in a new tab)</a></p>
                            </h6>
                        </div>
                    <?php endif; ?>

                    <?php if ($vle_account) : ?>
                        <?php if (isset($vle_account) &&  $vle_info["suspended"] == "false") :
                            echo "<p>You have access as <mark><strong>$vle_username</strong></mark> to HogwartsVLE. "; ?>
                            <p><a target="_blank" rel="external noreferrer" href="/vle/">Visit VLE (Opens in a new tab)</a></p>
                            </p>
                        <?php endif; ?>
                        <section name="vle-info" id="vle-info">
                            <h3>General VLE Information</h3>
                            <table class="styled-table" id="vle-account-table" name="vle-account-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Username</th>
                                        <?php if (check_user_role(ROLE_SENIOR_STAFF)): ?>
                                            <th scope="col">Authorisation Source</th>
                                        <?php endif; ?>
                                        <th scope="col">Policy Agreed</th>
                                        <th scope="col">Suspended</th>
                                        <th scope="col">ID</th>
                                        <th scope="col">First Name</th>
                                        <th class="secondary-information" scope="col">Middle Name(s)</th>
                                        <th scope="col">Last Name</th>
                                        <th scope="col">Known As</th>
                                        <th scope="col">House</th>
                                        <th scope="col">Year</th>
                                        <th scope="col">Quidditch Player</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo $vle_info["username"]; ?></td>
                                        <?php if (check_user_role(ROLE_SENIOR_STAFF)): ?>
                                            <td><?php echo strtoupper($vle_info["auth"]); ?></td>
                                        <?php endif; ?>
                                        <?php
                                        $policy_class = ($vle_info["policyagreed"] == "false");
                                        $alertClass = $policy_class ? 'text-danger emphasis' : '';
                                        ?>
                                        <td class="<?php echo $alertClass; ?>">
                                            <?php echo ucfirst($vle_info["policyagreed"]); ?>
                                        </td>
                                        <?php
                                        $suspended_class = ($vle_info["suspended"] == "true");
                                        $alertClass = $suspended_class ? 'text-danger emphasis' : '';
                                        ?>
                                        <td class="<?php echo $alertClass; ?>">
                                            <?php echo ucfirst($vle_info["suspended"]); ?>
                                        </td>
                                        <td><?php echo $vle_info["idnumber"]; ?></td>
                                        <td><?php echo $vle_info["firstname"]; ?></td>
                                        <td class="secondary-information"><?php echo $vle_info["middlename"]; ?></td>
                                        <td><?php echo $vle_info["lastname"]; ?></td>
                                        <td><?php echo $vle_info["common_name"]; ?></td>
                                        <td><?php echo $vle_info["house"]; ?></td>
                                        <td><?php echo $vle_info["year"]; ?></td>
                                        <td><?php echo ucfirst($vle_info["quidditch"]); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>
                        <section name="cohorts" id="cohorts">
                            <h3>Cohorts</h3>
                            <table class=" table-responsive styled-table mt-3" id="vle-cohort-table" name="vle-cohort-table">
                                <thead>
                                    <tr>
                                        <th>Cohort Name</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Scope</th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($get_user_vle_cohort_query as $row) : ?>
                                        <tr>
                                            <td><?php echo $row["cohort_name"]; ?></td>
                                            <td><?php echo $row["description"]; ?></td>
                                            <td><?php echo $row["cohort_type"]; ?></td>
                                            <td><?php echo $row["cohort_scope"]; ?></td>
                                            <td><?php echo (!empty($row["category_name"])) ? $row["category_name"] : "N/A"; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </section>

                        <section name="enrolments" id="enrolments">
                            <h3>Roles and Enrolments</h3>
                            <?php
                            $showCategoryColumns = false; // Flag to determine if the category columns should be shown
                            $showCourseColumns = false; // Flag to determine if the course columns should be shown
                            $showParentColumns = false; // Flag to determine if the parent-related columns should be shown
                            $showSystemColumns = false; //Flag to show if system level colums should be shown                

                            // Iterate through the dataset to check if any row has a non-empty categoryname
                            foreach ($get_user_vle_enrolment_query  as $row) {

                                if ($row["context"] == "Category") {
                                    $showCategoryColumns = true;
                                    continue;
                                }
                                if ($row["context"] == "System") {
                                    $showSystemColumns = true;
                                    continue;
                                }
                                if ($row["context"] == "Course") {
                                    $showCourseColumns = true;
                                    continue;
                                }
                                if ($row["context"] == "User") {
                                    $showParentColumns = true;
                                    continue;
                                }
                            }
                            ?>

                            <?php if ($showCourseColumns) : ?>
                                <h4>Enrolment Details for Roles at Course Level</h4>
                                <table class="table-responsive styled-table mt-3" id="vle-course-enrolments-table" name="vle-course-enrolments-table">
                                    <thead>
                                        <tr>
                                            <th>Enrolled as</th>
                                            <th>Context</th>
                                            <th>Course</th>
                                            <th>Course Category</th>
                                            <th>Enrolment Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($get_user_vle_enrolment_query  as $row) : ?>
                                            <?php if ($row["context"] == "Course") : ?>
                                                <tr>
                                                    <td><?php echo $row["role"]; ?></td>
                                                    <td><?php echo $row["context"]; ?></td>
                                                    <td><?php echo $row["coursename"]; ?></td>
                                                    <td><?php echo $row["course_categoryname"]; ?></td>
                                                    <td><?php echo $row["component"] ? ucfirst(preg_split("/_/", $row["component"])[1]) : "Manual"; ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>

                            <?php if ($showCategoryColumns) : ?>
                                <h4>Enrolment Details for Roles at a Category Level</h4>
                                <table class="table-responsive styled-table mt-3" id="vle-category-enrolments-table" name="vle-category-enrolments-table">
                                    <thead>
                                        <tr>
                                            <th>Enrolled as</th>
                                            <th>Context</th>
                                            <th>Category</th>
                                            <th>Courses in Category</th>
                                            <th>Enrolment Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($get_user_vle_enrolment_query  as $row) : ?>
                                            <?php if ($row["context"] == "Category") : ?>
                                                <tr>
                                                    <td><?php echo $row["role"]; ?></td>
                                                    <td><?php echo $row["context"]; ?></td>
                                                    <td><?php echo $row["categoryname"]; ?></td>
                                                    <td><?php echo $row["category_coursename"]; ?></td>
                                                    <td><?php echo $row["component"] ? ucfirst(preg_split("/_/", $row["component"])[1]) : "Manual"; ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>

                            <?php if ($showParentColumns) : ?>
                                <h4>Details for VLE Parental Users</h4>
                                <table class="table-responsive styled-table mt-3" id="vle-user-enrolments-table" name="vle-user-enrolments-table">
                                    <thead>
                                        <tr>
                                            <th>Enrolled as</th>
                                            <th>Context</th>
                                            <th>Dependent Username</th>
                                            <th>Dependent Fullname</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($get_user_vle_enrolment_query  as $row) : ?>
                                            <?php if ($row["context"] == "User") : ?>
                                                <tr>
                                                    <td><?php echo $row["role"]; ?></td>
                                                    <td><?php echo $row["context"]; ?></td>
                                                    <td><?php echo $row["parent_child_username"]; ?></td>
                                                    <td><?php echo $row["child_name"]; ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>

                            <?php if ($showSystemColumns) : ?>
                                <h4>Enrolment Details for Roles at System Level</h4>
                                <table class="table-responsive styled-table mt-3" id="vle-system-enrolments-table" name="vle-system-enrolments-table">
                                    <thead>
                                        <tr>
                                            <th>Enrolled as</th>
                                            <th>Context</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($get_user_vle_enrolment_query  as $row) : ?>
                                            <?php if ($row["context"] == "System") : ?>
                                                <tr>
                                                    <td><?php echo $row["role"]; ?></td>
                                                    <td><?php echo $row["context"]; ?></td>
                                                    <td style="width:66%"><?php echo $row["description"]; ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </section>

                    <?php else : ?>
                        <div class="alert">
                            <h3>
                                HogwartsVLE Account for <?php echo $vle_username; ?> does not exist
                            </h3>
                            <hr>
                            <p>
                                You can raise a request to create an HogwartsVLE account by clicking the button below.
                                This will then be sent for approval, and if authorisation is granted, will be processed
                                within 24 hours. Your HogwartsVLE account with then be available with the same credentials.
                                If you have access via HADS, then those credentials will be used instead.
                            </p>

                            <div class="alert secondary alert-subtle-border">
                                <img class="alert-icon" src="assets/img/icon-info.svg" aria-hidden="true" alt="informatin icon" label="icon indicating this alert message is informational">
                                <span>Account will be automatically created at login - no request needed</span>
                                <hr>
                                <a href="/vle/" target="_blank">Click here to go to HogwartsVLE</a>
                            </div>
                        </div>
                    <?php endif; ?>
            </section>

            <!-- LDAP SECTION -->
            <section id="ldap-information" class="page-section-no-grid">
                <h2>Information held by Hogwarts Authentication Directory Service (HADS)</h2>
                <?php if (gettype($ldap_user) == "array") : ?>
                    <noscript>
                        <div class="alert warning">
                            <img class="alert-icon" src="assets/img/icon-info.svg" aria-hidden="true" alt="informatin icon" label="icon indicating this alert message is informational">
                            <span>
                                Javascript is required to show HADS information.
                            </span>
                        </div>
                    </noscript>
                    <p>
                        <button type="button" class="button dialog-show" hidden data-linked-dialog="ldap-dialog">
                            <img class="filter-icon" src="assets/img/icon-show.svg" alt="Show LDAP Information">
                            Show LDAP Information
                        </button>
                    </p>
                    <dialog id="ldap-dialog">
                        <div class="dialog-title">
                            <h3>Hogwarts Authentication Directory Service (HADS)</h3>
                            <button type="button" class="button close-modal" data-linked-dialog="ldap-dialog">
                                <img class="filter-icon" src="assets/img/icon-close.svg" alt="Close LDAP Information" aria-hidden="true">
                                Close LDAP Information
                            </button>
                        </div>
                        <div class="dialog-body">
                            <h4>User Details held in HADS</h4>
                            <table id="ldap-attribute-table" name="ldap-attribute-table">
                                <thead>
                                    <tr>
                                        <th>LDAP Attribute</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($core_ldap_attributes as $k) : ?>
                                        <?php if (array_key_exists($k, $ldap_user)) : ?>
                                            <tr>
                                                <th class='index-column'><code>
                                                        <?php echo $k; ?></code>
                                                </th>
                                                <td>
                                                    <?php if ($k == "jpegphoto"): ?>
                                                        <img width="50%" height="50%" alt="User profile picture stored in LDAP" src="<?php echo ldap_parse_user_photo($ldap_user[$k]); ?>">
                                                    <?php else: ?>
                                                        <?php echo $ldap_user[$k]; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if ($ldap_user["groups"]): ?>
                                <h4>Group Membership</h4>
                                <table id="ldap-group-table" name="ldap-group-table">
                                    <thead>
                                        <tr>
                                            <th>Group Name</th>
                                            <th>System Name</th>
                                            <th>Group Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ldap_user["groups"] as $group) : ?>
                                            <tr>
                                                <?php if (!is_array($group)): ?>
                                                    <th class='index-column' colspan="3">
                                                        <?php echo $group; ?>
                                                    </th>
                                                <?php else: ?>
                                                    <th class='index-column'>
                                                        <?php echo $group["cn"]; ?>
                                                    </th>
                                                    <td>
                                                        <code>
                                                            <?php if (strtolower($group["ou"]) == "groups"): ?>
                                                                Global (HADS Root Level)
                                                            <?php else: ?>
                                                                <?php echo ucfirst($group["ou"]); ?>
                                                            <?php endif; ?>
                                                        </code>
                                                    </td>
                                                    <td><?php echo @$group["description"]; ?></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </dialog>
                <?php else : ?>
                    <div class="alert danger" role="status">
                        <img class="alert-icon" src="assets/img/icon-warning.svg" aria-hidden="true" alt="exclamation icon" label="icon indicating this alert message is a warning" />
                        <span>
                            <?php echo $ldap_user; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </section>

            <!-- CERTIFICATE SECTION -->
            <section id="certificate-information" class="page-section-no-grid">
                <h2>Certificate Information</h2>

                <?php if ($_SESSION["username"] == $user["username"] && !check_user_permission(PERMISSION_EDIT_OWN_PROFILE)) : ?>
                    <div class="alert danger" role="status">
                        <img class="alert-icon" src="assets/img/icon-warning.svg" aria-hidden="true" alt="exclamation icon" label="icon indicating this alert message is a warning" />
                        <span>You do not have permission to enrol a certificate.</span>
                    </div>
                <?php endif; ?>

                <div class="information-section">
                    <span>
                        It is possible register a user certificate with your account. This will
                        allow you to login without providing a username and password, if you prefer.
                        Certificates can be requested from the access catalogue.
                        Once a certificate has been enrolled, it can toggle for login use below.
                    </span>
                    <p>
                        <em>Note: You are responsible for keeping your certificate information secure, and maintaining its validity.</em>
                    </p>
                </div>

                <?php if (check_user_permission(PERMISSION_EDIT_OWN_PROFILE) || (check_user_permission(PERMISSION_EDIT_ANY_PROFILE) && check_user_has_any_of_roles([ROLE_ADMIN, ROLE_SENIOR_STAFF, ROLE_STAFF]))): ?>
                    <div class="command-section">
                        <h3>Certificate Login</h3>
                        <hr>
                        <div id="cert-login-status">
                            <?php if ($cert_info): ?>
                                <div class='alert success alert-double'>
                                    <p>Certificate login is <strong><?php echo $cert_login_status; ?></strong></p>
                                </div>
                            <?php else: ?>
                                <div class='alert secondary'>
                                    <img src="assets/img/icon-disabled.svg" alt="disabled icon" aria-hidden="true" focusable="false" class="alert-icon">
                                    <span>Certificate login is not available as there is no enrolled certificate.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div id="certificate-login">
                            <button id="toggle-certificate-login-state" class="toggle button" type="button" aria-pressed="<?php echo $cert_toggle; ?>" <?php echo $cert_toggle_disabled; ?>>
                                <span class="toggle-switch">
                                    <img src="assets/img/toggle_tick.svg" alt="checkmark icon" aria-hidden="true" focusable="false" class="toggle-icons checkmark">
                                    <img src="assets/img/toggle_cross.svg" alt="cross icon" aria-hidden="true" focusable="false" class="toggle-icons cross">
                                </span>
                                Toggle Certificate Login
                            </button>
                            <?php if ($cert_info): ?>
                                <span>(If you are logged in via certificate and disable this option, you will automatically be logged out.)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <h3>Certificate Details</h3>
                <div class="certificate-comparison">
                    <?php if (!$presented_cert) : ?>
                        <p class="highlight">No certificate presented.</p>
                    <?php else: ?>
                        <div id="presented-certificate">
                            <h4 class="fw-bolder">Presented Certificate</h4>
                            <table class="table">
                                <caption>The table shows the certificate that is being presented via the browser (or some other software).</caption>
                                <thead>
                                    <tr>
                                        <th>Attribute</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th scope=" row">Serial Number</th>
                                        <td><?php echo $_SERVER["SSL_CLIENT_M_SERIAL"]; ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">CN (Common Name)</th>
                                        <td><?php echo $_SERVER["SSL_CLIENT_S_DN_CN"]; ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Email</th>
                                        <td><?php echo @$_SERVER["SSL_CLIENT_S_DN_Email"]; ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="secondary-information">Issued by</th>
                                        <td class="secondary-information"><?php echo $_SERVER["SSL_CLIENT_I_DN"]; ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Valid from</th>
                                        <td><?php echo date("Y-m-d H:i:s", strtotime($_SERVER["SSL_CLIENT_V_START"])); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Valid to</th>
                                        <td><?php echo date("Y-m-d H:i:s", strtotime($_SERVER["SSL_CLIENT_V_END"])); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <?php if ($_SESSION["username"] == $user["username"] && check_user_permission(PERMISSION_EDIT_OWN_PROFILE)) : ?>
                                <div class="command-section">
                                    <h3>Enrol Presented Certificate</h3>
                                    <hr>
                                    <?php if ($cert_info && $cert_info["certificate_cn"] == $_SERVER["SSL_CLIENT_S_DN_CN"] && $cert_info["certificate_serial"] == $_SERVER["SSL_CLIENT_M_SERIAL"] && $cert_info["certificate_client_issuer_dn"] == $_SERVER["SSL_CLIENT_I_DN"]) : ?>
                                        <div class="alert" role="status">
                                            <img class="alert-icon" src="assets/img/icon-info.svg" aria-hidden="true" alt="info icon" label="icon indicating this alert message is informational" />
                                            <span>The presented certificate matches the stored certificate and therefore does not need to be enrolled.</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="command-option">
                                            <button id="enrol-certificate" type="button" class="button">
                                                <img class="filter-icon" src="assets/img/icon-certificate.svg" aria-hidden="true" alt="An icon image of a certificate">
                                                <span>Enrol Certificate</span>
                                            </button>
                                            <span>
                                                Click the button to enrol this certificate. You will then need to enable it for login use.
                                                If a certificate is already enrolled, this action will <strong>overwrite</strong> the existing certificate.
                                                <p>The cn <em>(common name)</em> of the certificate <strong>must exactly</strong> match your username to be eligible for enrolment.</p>
                                            </span>
                                        </div>
                                        <div id="enrol-certificate-status" class="status-message"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <h4>Enrolled Certificate</h4>
                    <?php if (!$cert_info) : ?>
                        <p class="highlight">No certificate enrolled.</p>
                    <?php else: ?>
                        <div>
                            <caption>The table shows the certificate information stored for the user</caption>

                            <?php if (strtotime(date("Y-m-d")) > strtotime($cert_info["certificate_end"])) : ?>
                                <div class="alert danger alert-double" role="status">
                                    <img class="alert-icon" src="assets/img/icon-warning.svg" aria-hidden="true" alt="stop/no-entry icon" label="icon indicating this alert message is a danger" />
                                    <span class="emphasis">Enrolled Certificate Has Expired
                                    </span>
                                    <hr>
                                    <p>The enrolled has certificate has expired and cannot be used for login.</p>
                                </div>
                            <?php endif; ?>

                            <table class="table" id="enrolled-certificate-table" name="enrolled-certificate-table">
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
                                        <th scope="row">CN (Common Name)</th>
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
                                </tbody>
                            </table>

                            <div class="command-section">
                                <h3>Remove Enrolled Certificate</h3>
                                <hr>
                                <div class="command-option">
                                    <button id="remove-certificate" type="button" class="button" <?php echo $cert_toggle_disabled; ?>>
                                        <img class="filter-icon" src="assets/img/icon-certificate-remove.svg" aria-hidden="true" alt="An icon image of a certificate with an exclamation symbol">
                                        <span>Remove Certificate</span>
                                    </button>
                                    <span>
                                        Click the button to remove the certificate from the system. You will need to log in using your username and password.
                                        Deleting your certificate will automtically log out out, if you had logged in via certificate.
                                        <strong>The deletion action is not reversible</strong>.
                                    </span>
                                </div>
                                <div id="remove-certificate-status" class="status-message"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </section>
    </main>
</div>

<script defer>
    function toggleEditMode() {
        const editButton = document.getElementById("btn-edit-account");
        const editButtonText = editButton.querySelector('span');
        const editButtoninfo = document.querySelector('.btn-information');

        const editableFields = document.querySelectorAll("[data-editable=true]");
        const nonEditableFields = document.querySelectorAll("[data-editable=false]");
        // const navigationTabs = document.querySelectorAll("button[ data-bs-toggle=tab]");
        const submitButtonProfile = document.querySelectorAll("button[type=submit");
        // const fileUploadButtonProfile = document.querySelectorAll("input[type=file]");
        // const emailAccountStatusButton = document.getElementById("btnViewEmail");

        function enableEditMode() {
            editButtonText.textContent = "End Editing";
            editButton.dataset.enabled = "true";
            editButton.classList.add("active");
            editButtoninfo.textContent = "Click to exit Edit Mode (unsaved changes will be discarded)";

            editableFields.forEach((field) => {
                const badge = document.createElement("span");
                badge.classList.add("badge", "info");
                badge.textContent = "Editable";
                field.previousElementSibling.insertAdjacentElement("beforeEnd", badge);
                field.contentEditable = true;
            });

            nonEditableFields.forEach((field) => {
                const badge = document.createElement("span");
                badge.classList.add("badge", "warning");
                badge.textContent = "Read Only";
                field.previousElementSibling.insertAdjacentElement("beforeEnd", badge);
            });
        }

        function disableEditMode() {
            const updateStatusBadges = document.querySelectorAll("span.update-status");
            editButtonText.textContent = "Edit Account";
            editButton.dataset.enabled = "false";
            editButton.classList.remove("active");
            editButtoninfo.textContent = "Click the button to edit account details.";

            //Remove any status badges due to DB operations
            updateStatusBadges.forEach(badge => {
                if (badge) {
                    badge.remove();
                }
            })

            nonEditableFields.forEach((field) => {
                const indicatorBadge = field.parentElement.querySelector(".badge")
                if (indicatorBadge) {
                    indicatorBadge.remove();
                }
            });

            editableFields.forEach((field) => {
                const indicatorBadge = field.parentElement.querySelector(".badge")
                if (field.textContent != field.dataset.originalContent) {
                    field.textContent = field.dataset.originalContent
                }
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
                let buttonDiv = field.parentElement.getElementsByClassName("editableFieldButtons");

                if (buttonDiv.length == 0) {
                    const editableFieldButton = document.createElement("div")
                    editableFieldButton.setAttribute("class", "editableFieldButtons");
                    field.insertAdjacentElement("afterend", editableFieldButton);
                }

                buttonDiv = buttonDiv[0]; // Update buttonDiv with the node rather than htmlcollection
                let saveButton = buttonDiv.firstChild;
                let cancelButton = saveButton && saveButton.nextElementSibling;

                if (!saveButton) {
                    saveButton = createButton("Save", () => {
                        updateField(field);
                        cancelButton.style.display = "none";
                        saveButton.style.display = "none";
                    });
                    buttonDiv.appendChild(saveButton);
                }

                if (!cancelButton) {
                    cancelButton = createButton("Cancel", () => {
                        field.textContent = field.dataset.originalContent;
                        cancelButton.style.display = "none";
                        saveButton.style.display = "none";
                    });
                    buttonDiv.appendChild(cancelButton);
                }

                cancelButton.style.display = "inline-block";
                cancelButton.dataset.buttonVariant = "negative";
                saveButton.style.display = "inline-block";
                saveButton.style.marginInlineEnd = "0.2rem";
                saveButton.dataset.buttonVariant = "positive";
            }
        }

        function createButton(text, clickHandler) {
            const button = document.createElement("button");
            button.textContent = text;
            button.classList.add("button");
            button.addEventListener("click", clickHandler);
            return button;
        }

        function init() {
            if (!editButton) {
                return false;
            }
            editableFields.forEach((field) => {
                field.dataset.originalContent = field.textContent.trim();
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
                let fieldName = field.id.split("user-detail-")[1].toLowerCase();
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
                    field.dataset.originalContent = newValue.trim();
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
</script>

<!-- Change account status-->
<script defer>
    async function changeAccountStatus(action) {

        // Validation and Setup
        const validActions = ['lock', 'unlock', 'hide'];
        action = action.toLowerCase();

        if (!validActions.includes(action.toLowerCase())) {
            throw new Error(`Invalid action '${action}' provided. Valid actions are: ${validActions.join(', ')}.`);
        }

        const buttonIds = {
            lock: "btnLockAccount",
            unlock: "btnUnlockAccount",
            hide: "btnHideAccount",
        };

        const buttonId = buttonIds[action];
        const statusElementId = action === 'unlock' ? 'unlockAccountStatus' : 'genericAccountStatus';
        const button = document.getElementById(buttonId);
        const statusElement = document.getElementById(statusElementId);
        const actionFormatted = action.charAt(0).toUpperCase() + action.slice(1).toLowerCase();
        // As unlock is nested in an alert, need to reduce h2 to h4 for structure
        const messageTag = action === "unlock" ? "h4" : "h4";

        if (!button || !statusElement) {
            console.debug(`Action '${actionFormatted} Account' is not available.`)
            return;
        } else {
            console.debug(`Action '${actionFormatted} Account' is available.`);
        }

        // Set up Event Listener Asynchronously
        button.addEventListener('click', async (e) => {
            try {
                const response = await fetch(`<?php echo WEBROOT; ?>/change-account-status.php?action=${encodeURIComponent(action)}&userid=<?php echo $userid; ?>&user=<?php echo $username; ?>`);
                //Handle incorrect params and permission/authorisation errors 
                if (!response.ok) {
                    let errorMessage;
                    if (response.status === 403 || response.status === 401) {
                        errorMessage = '<?php echo LANG_INSUFFICIENT_PRIVILEGES; ?> <div class="information-section"><span>Reasons:</span><ul><li>' + response.statusText + '</li></ul></div>';
                    } else if (response.status === 400) {
                        errorMessage = '<?php echo LANG_BAD_REQUEST; ?> <div class="information-section"><span>Reasons:</span><ul><li>' + response.statusText + '</li></ul></div>';
                    } else {
                        errorMessage = 'An error occurred. Please contact support for more information. <div class="information-section"><span>Reason(s):</span><ul><li>Error Code:<code> ' + response.status + "</code></li><li>Error Message: <code>" + response.statusText + "</code></li></ul></div>";
                    }
                    throw new Error(errorMessage);
                }

                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                statusElement.className = '';
                statusElement.classList.add("alert", "success");
                statusElement.innerHTML = `<${messageTag}>${data.message}</${messageTag}><hr>`;
                if (data.account && data.status) {
                    statusElement.innerHTML += `<p>The account for <code>${data.account}</code> has been ${data.status}.</code></p>`;
                }

                button.parentElement.style.display = "none";
                console.info(`Account Status Changed: ${data.status}`);
            } catch (error) {
                statusElement.className = "";
                statusElement.classList.add("alert", "danger");
                statusElement.innerHTML = `<${messageTag}>An Error Occurred</${messageTag}><hr><p>${error.message}</p>`;
            }
        });
    }
</script>


<!-- Show/Hide Dialog Handling-->
<script>
    const dialogElems = document.getElementsByTagName("dialog");
    const showDialogBtns = document.querySelectorAll(".dialog-show");
    const closeBtns = document.querySelectorAll(".close-modal");

    if (showDialogBtns && dialogElems) {
        showDialogBtns.forEach((dialogBtn) => {
            dialogBtn.setAttribute("aria-hidden", "false");
            dialogBtn.hidden = false;

            let dialogBox = document.getElementById(dialogBtn.dataset.linkedDialog);
            dialogBtn.addEventListener("click", (e) => {
                dialogBox.showModal();
            })
        })

        closeBtns.forEach((closeBtn) => {
            let dialogBox = document.getElementById(closeBtn.dataset.linkedDialog);
            closeBtn.addEventListener("click", (e) => {
                dialogBox.close();
            })
        })
    }
</script>


<!--  Update Certificates and Certificate Login Status -->
<script>
    function updateCertificate() {
        const certLoginStatusDiv = document.getElementById('cert-login-status');
        const toggleCertificateButton = document.getElementById('toggle-certificate-login-state');
        let userid = <?php echo sanitise_user_input($user["userid"]); ?>;
        const deleteButton = document.getElementById('remove-certificate');
        const deleteStatusDiv = document.getElementById('remove-certificate-status');
        const enrolCertificate = document.getElementById("enrol-certificate");
        const enrolCertificateStatusDiv = document.getElementById('enrol-certificate-status');

        // Toggle Certificate Switch
        toggleCertificateButton.addEventListener('click', async () => {
            const isCertificateEnabled = toggleCertificateButton.getAttribute('aria-pressed') === 'true';
            try {
                const newStatus = isCertificateEnabled ? 0 : 1;
                await updateDatabase(userid, <?php echo $editable; ?>, 'enabled_by_user', newStatus, );

                if (newStatus === 0) {
                    toggleCertificateButton.removeAttribute('aria-pressed');
                    certLoginStatusDiv.innerHTML = "<div class='alert success alert-double'><p>Certificate login is <strong>disabled</strong></p></div>";
                    certLoginStatusDiv.setAttribute('role', 'alert');
                    console.info("Certificate Login Disabled");
                    <?php if ($_SESSION["login_method"] == "CLIENT_CERTIFICATE" && $user["userid"] == $_SESSION["userid"]) : ?>
                        console.info("Logged out");
                        window.location.href = "logout.php?reason=certificate-disabled";
                    <?php endif; ?>
                } else {
                    toggleCertificateButton.setAttribute('aria-pressed', 'true');
                    certLoginStatusDiv.innerHTML = "<div class='alert success alert-double'><p>Certificate login is <strong>enabled</strong></p></div>";
                    certLoginStatusDiv.setAttribute('role', 'alert');
                    console.info("Certificate Login Enabled");

                }
            } catch (error) {
                console.error('Error:', error.message);
            }
        });

        // Enrol Certificate
        if (enrolCertificate) {
            enrolCertificate.addEventListener("click", async e => {
                // Can only be done by user, no-one else (including admins)
                try {
                    enrol_new_certificate = await updateDatabase(userid, <?php echo $editable; ?>);
                    enrolCertificateStatusDiv.innerHTML = `<div class='alert success alert-double'><h4>Enrolment Successful</h4><p>${enrol_new_certificate}</p><hr><em>You will need to enable certificate login.</em></div>`;
                    enrolCertificate.parentNode.removeChild(enrolCertificate);
                    toggleCertificateButton.disabled = false;
                    toggleCertificateButton.setAttribute('aria-pressed', 'false');
                    certLoginStatusDiv.innerHTML = "<div class='alert success alert-double'>Certificate login is <strong>disabled</strong></div>";
                    if (deleteButton) {
                        deleteButton.parentNode.removeChild(deleteButton);
                    }
                    console.info("Certificate Enrolled");
                } catch (error) {
                    console.error('Error:', error.message);
                }
            });
        }

        // Delete enrolled certificate
        <?php if ($cert_info) : ?>
            deleteButton.addEventListener("click", async e => {
                try {
                    delete_certificate = await updateDatabase(userid, <?php echo $editable; ?>, null, null, true);
                    deleteStatusDiv.innerHTML = `<div class='alert success alert-double'><h4>Deletion Succesful</h4><p>Certificate has been <strong>deleted</strong></p><p>You must log in using your username and password.</p><hr><em>If you were logged in via certifiate authentication, then that session has been destroyed and you have been logged out.</em></div>`
                    toggleCertificateButton.setAttribute('aria-pressed', 'false');
                    toggleCertificateButton.disabled = true;
                    certLoginStatusDiv.innerHTML = "<div class='alert success alert-double'>Certificate login is <strong>disabled</strong></div>";
                    deleteButton.parentNode.removeChild(deleteButton);
                    <?php if ($_SESSION["login_method"] == "CLIENT_CERTIFICATE" && $user["userid"] == $_SESSION["userid"]) : ?>
                        window.location.href = "logout.php?reason=certificate-disabled";
                        console.info("Logged out");
                    <?php endif; ?>
                    console.info("Certificate Deleted");
                } catch (error) {
                    console.error('Certificate Delete Error', error.message);
                }
            });
        <?php endif; ?>
    }
</script>

<!-- Update Databse for certificate -->
<script>
    async function updateDatabase(userid, editable = false, fieldName = null, fieldValue = null, delete_certificate = null, ) {
        const enrolCertificateStatusDiv = document.getElementById('enrol-certificate-status');
        try {
            const response = await fetch('<?php echo WEBROOT; ?>/auth/check-certificate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `userid=${encodeURIComponent(userid)}&field=${encodeURIComponent(fieldName)}&value=${encodeURIComponent(fieldValue)}&deletecertificate=${encodeURIComponent(delete_certificate)}&editable=${encodeURIComponent(editable)}`,
            });

            if (!response.ok) {
                const errorData = await response.json();
                const errorReturned = errorData.error;
                enrolCertificateStatusDiv.innerHTML = `<div class="alert danger border-double"><h4>Enrolment Failed</h4><hr><p>${errorReturned}</p></div>`;
                throw new Error(`HTTP error. Status: ${response.status}`);
            } else {
                const result = await response.json();
                return result;
            }
        } catch (error) {
            console.error('Error:', error.message);
            throw error;
        }
    }
</script>

<script>
    if (window.location.href !== window.parent.location.href) {
        console.info("modal is running in an iframe");
    }

    const profileImage = document.getElementById("profileImage");
    const profilePictureMessages = document.getElementById("profilePictureMessages");
    const imgInfo = document.getElementById("img-info");
    const imgInfoSection = document.getElementById("profile-image-info");
    const uploadedImagesContainer = document.getElementById("uploaded-images-container");
    const inputImage = document.getElementById("inputProfilePicture");

    let cropper // define in higer scope

    const cropperOptions = {
        aspectRatio: 4 / 5,
        preview: "#img-preview", // Assuming you have an element with id #img-preview
        viewMode: 0,
        dragMode: "move",
        rotatable: false,
        scalable: true,
        zoomable: true,
    };

    function importImage() {
        const formUpdateProfilePictureDivs = document.querySelectorAll(
            "form div:not(#profilePictureMessages)"
        );

        imgInfoSection.style.display = "none";
        uploadedImagesContainer.style.display = "none";

        // Check CropperJS is available, otherwise error and exit
        if (!checkCropperAvailable(profileImage)) {
            inputImage.setAttribute("disabled", true);
            formUpdateProfilePictureDivs.forEach((divElement) => {
                divElement.style.display = "none";
            });
            return;
        }

        inputImage.addEventListener("change", handleImageSelection);


        function handleImageSelection(event) {
            const files = event.target.files;

            if (!files || !files.length) {
                return; // No file selected
            }

            const file = files[0];

            if (!isFileImage(file)) {
                displayErrorMessage(
                    "Profile Picture Upload Failed",
                    `${file.name} is not an image.`
                );
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;

                if (cropper) {
                    cropper.destroy();
                }

                // Create the image information table
                const imgInfoTable = createImageInfoTable(file);
                imgInfo.innerHTML = "";
                imgInfo.appendChild(imgInfoTable);

                imgInfoSection.style.display = "block";
                uploadedImagesContainer.style.display = "flex";

                // Create cropperJS object
                cropper = new Cropper(profileImage, cropperOptions);
            };
            reader.readAsDataURL(file);
            clearErrorMessage();

        }
    }

    function returnFileSize(number) {
        if (number < 1e3) {
            return `${number} bytes`;
        } else if (number >= 1e3 && number < 1e6) {
            return `${(number / 1e3).toFixed(1)} KB`;
        } else {
            return `${(number / 1e6).toFixed(1)} MB`;
        }
    }

    function isFileImage(file) {
        return file && file.type.split("/")[0] === "image";
    }

    function createImageInfoTable(file) {
        const table = document.createElement("table");
        const thead = document.createElement("thead");
        const headerRow = document.createElement("tr");
        headerRow.appendChild(document.createElement("th")).textContent = "Information";
        headerRow.appendChild(document.createElement("th")).textContent = "Value";
        thead.appendChild(headerRow);
        table.appendChild(thead);

        const tbody = document.createElement("tbody");
        const rows = [
            ["File Name", file.name],
            ["File Type", file.type],
            ["File Size", returnFileSize(file.size)],
        ];

        rows.forEach(([key, value]) => {
            const row = tbody.insertRow();
            row.insertCell(0).innerHTML = key;
            row.insertCell(1).innerHTML = value;
        });

        table.appendChild(tbody);
        return table;
    }

    function saveToImage() {
        if (!checkCropperAvailable(profileImage)) {
            return false;
        }

        if (!cropper) {
            displayErrorMessage("Profile Picture Upload Error", "No image selected.");
            return false;
        }

        cropper.getCroppedCanvas({
            fillColor: '#fff',
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
            minWidth: 200,
            minHeight: 200
        }).toBlob((blob) => {
            const formData = new FormData();
            formData.append('profilePicture', blob, 'test.png');
            formData.append('idnumber', "<?php echo $user["idnumber"]; ?>");

            fetch('<?php echo WEBROOT; ?>/upload-profile-picture.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        console.info(data.message);
                        console.info(data.imagePath);
                        displaySuccessMessage("Profile Picture Upload Succeeded", data.message);

                        // Reset buttons and update page
                        btnGetImage.setAttribute("disabled", true);
                        btnGetImage.style.display = "none";
                        btnResetForm.setAttribute("disabled", true);
                        btnResetForm.style.display = "none";
                        document.getElementsByClassName("img-account-profile")[0].src = data.imagePath;
                    } else {
                        console.error(data.error);
                        displayErrorMessage("Profile Picture Update Failed", data.error);
                    }
                })
                .catch(error => {
                    console.error('Profile Picture Upload failed', error);
                    displayErrorMessage("Profile Picture Upload Failed", error);
                });
        });
    }

    function setMessage(type, title, message) {
        profilePictureMessages.style.display = "block";
        profilePictureMessages.className = "alert";
        profilePictureMessages.classList.add(type);
        profilePictureMessages.innerHTML = `<h4>${title}</h4><hr><p>${message}</p>`;
    }

    function clearErrorMessage() {
        profilePictureMessages.style.display = "block";
        profilePictureMessages.className = ""; // Reset className
        profilePictureMessages.innerHTML = "";
    }

    function displayErrorMessage(title, message) {
        setMessage("danger", title, message);
    }

    function displaySuccessMessage(title, message) {
        setMessage("success", title, message);
    }

    function checkCropperAvailable(imageElement) {
        try {
            console.debug("Check CropperJS Loaded");
            let cropper = new Cropper(imageElement);
            cropper.destroy();
            console.debug("CropperJS Loaded Successfully");
            return true;
        } catch (e) {
            console.error("CropperJS is not initialized - the script could not be loaded.");
            displayErrorMessage(
                "Profile Picture Update Error",
                "The CropperJS Library could not be loaded. This may be due to network errors or something may be blocking the script from loading."
            );
            return false;
        }
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" integrity="sha512-JyCZjCOZoyeQZSd5+YEAcFgz2fowJ1F1hyJOXgtKu4llIa0KneLcidn5bwfutiehUTiOuK87A986BZJMko0eWQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- Runs when DOM has finished parsing -->
<script defer>
    toggleEditMode();
    <?php if (check_user_permission(PERMISSION_HIDE_USER)): ?>
        changeAccountStatus("hide");
    <?php endif; ?>
    <?php if (check_user_permission(PERMISSION_LOCK_USER)): ?>
        changeAccountStatus("lock");
    <?php endif; ?>
    <?php if (check_user_permission(PERMISSION_UNLOCK_USER)): ?>
        changeAccountStatus("UNlock");
    <?php endif; ?>
    <?php if (check_user_permission(PERMISSION_EDIT_OWN_PROFILE) || check_user_permission(PERMISSION_EDIT_ANY_PROFILE)) : ?>
        updateCertificate();
        importImage(); // Call importImage to initialize Cropper        
        const btnGetImage = document.getElementById('getImage');
        const imgProfileImage = document.getElementById('profileImage');

        const btnEditProfilePicture = document.getElementById('btn-edit-profile-picture');
        btnEditProfilePicture.disabled = false;

        btnGetImage.addEventListener('click', function(e) {
            e.preventDefault();
            saveToImage(imgProfileImage);
        });

        const btnResetForm = document.getElementById('resetForm');
        btnResetForm.addEventListener('click', (e) => {
            profilePictureMessages.style.display = "none";
            profilePictureMessages.className = "";
            profilePictureMessages.innerHTML = null;
            if (cropper) {
                cropper.reset();
                cropper.destroy();
            }
            profileImage.src = "";
            document.getElementById("img-info").innerHTML = "";
            document.getElementById("profile-image-info").style.display = "none";
            console.debug("Form Reset")
        });
    <?php endif; ?>
</script>




<?php require_once("footer.php"); ?>