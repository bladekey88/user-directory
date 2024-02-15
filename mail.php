<?php
require_once(__DIR__ . "/config/auth.php");
$title = "Email Services";

//Global Permission and Role Check
if (!check_user_permission(PERMISSION_VIEW_USER)) {
    @require_once($_SERVER['DOCUMENT_ROOT'] . "/errordocs/403.php");
    exit();
}

// Set up
$error = [];
$message = [];
$email_username = $_SESSION["email"];
$temp_email_username = "ZZ_PENDING_" . $_SESSION["email"];

// Create COM Instance and connect to it
try {
    $obBaseApp = new COM("hMailServer.Application", NULL, CP_UTF8);
    $obBaseApp->Connect();
} catch (Exception $e) {
    $error["dcom_permission"]  = "An error occured. This problem is often caused by DCOM permissions not being set ($e)";
    echo "The email service is not reachable. This could be due to server or permissions issues.";
    error_log($e, 0);
    exit();
}

// Authentication returns an object successful otherwise returns null
// It does not return an exception
$login = $obBaseApp->Authenticate($email_username, HMAIL_ADMIN_PW);
if (!$login) {
    $fallback_login = $obBaseApp->Authenticate(HMAIL_ADMIN_USER, HMAIL_ADMIN_PW);
    if ($fallback_login) {
        define("HMAIL_ADMIN_LOGIN", true);
    } else {
        $error["auth_failed"] = "Authentication failed. This could be because the provided username/password are wrong, or the account does not exist, or the account is not active.";
        exit();
    }
}

// Check if user is authorised on the domain
try {
    $domain = $obBaseApp->Domains->ItemByName(HMAIL_DOMAIN);
} catch (Exception $e) {
    // Handle other exceptions
    $error["domain_permission_denied"] =  "Your email address does not have access to the domain: " . HMAIL_DOMAIN;
    error_log("An error occurred: " . $e->getMessage());
    exit();
}

// Check if account exists
try {
    $email_account = $domain->Accounts->ItemByAddress($email_username);
    // echo "<pre>";
    // print_r(com_print_typeinfo($email_account));
    // echo "</pre>";
    $account = array();
    $account["active"] = $email_account->Active;
    $account["max_size"] = $email_account->MaxSize;
    $account["current_size"] = round($email_account->Size, 3);
    $account["quota_used"] = $email_account->QuotaUsed;
    $account["address"] = $email_account->Address;
    $account["last_login"] = $email_account->LastLogonTime;
    $last_login_date = DateTime::createFromFormat("d/m/Y H:i:s", $account["last_login"]);
    $account["first_name"] = $email_account->PersonFirstName;
    $account["last_name"] = $email_account->PersonLastName;

    $quota = $account["quota_used"] > 100 ? "100" : $account["quota_used"];
    if ($quota >= 90) {
        $quota_level = "danger";
    } else if ($quota >= 66) {
        $quota_level = "warning";
    } else if ($quota >= 33) {
        $quota_level = "primary";
    } else {
        $quota_level = "success";
    }
    $quota_display = '<div class="progress" role="progressbar" aria-label="Progress bar showing current usage as a percentage of the quota" aria-valuenow="' .  $account["quota_used"] . '" aria-valuemin="0" style="height:2rem" aria-valuemax="' . $account["max_size"] . '">
    <div class="' . $quota_level . 'text-emphasis fw-bold progress-bar bg-' . $quota_level . '" style="width:' . $quota . '%;">' . $account["quota_used"] . '%</div>
    </div>';
} catch (com_exception $e) {
    $email_account = NULL;
}

//Check if account request exists
try {
    $email_request = $domain->Accounts->ItemByAddress($temp_email_username);
} catch (com_exception $e) {
    $email_request = NULL;
}

require_once(FILEROOT . "/header.php");
?>

<style>
    table {
        min-width: 40rem;
        overflow: hidden !important;
    }

    .styled-table th,
    .style-table td {
        /* padding-inline: 0; */
        margin: 0;
    }

    .styled-table tbody>tr:hover {
        color: inherit;
        background-color: inherit;
        cursor: auto;
    }
</style>
<main>
    <div class="container-xl px-4 mt-4">
        <nav class="nav nav-borders">
            <h4 class="m-0"><?php echo $_SESSION["name"]; ?></h4>
        </nav>
        <hr class="mt-0 mb-4">
        <div class="card">
            <div class="card-header text-bg-primary rounded-0">Email Account Details</div>
            <div class="card-body">
                <?php if ($email_account) :
                    echo "<h6>You have access as <mark class='fw-bolder'>$email_username</mark> to " . $domain->Name . " mail domain.</h6>"; ?>
                    <table class="table-responsive styled-table" id="email-account-table" name="email-account-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Last Login</th>
                                <th scope="col">Status</th>
                                <th scope="col">Current Size</th>
                                <th scope="col">Quota Enforced</th>
                                <th scope="col">Max Size</th>
                                <th scope="col">Quota Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $account["first_name"] . " " . $account["last_name"]; ?></td>
                                <td><?php echo $last_login_date->format("Y-m-d H:i:s"); ?></td>
                                <td>
                                    <?php if ($account["active"] == 1) : ?>
                                        <span class="text-success fw-bolder">Active</span>
                                    <?php else : ?>
                                        <span class="text-danger fw-bolder">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $account["current_size"] . " MB"; ?></td>
                                <td><?php echo $account["max_size"] > 0 ? "Y" : "N"; ?></td>
                                <td><?php echo $account["max_size"] > 0 ? $account["max_size"] . " MB" : "No Quota"; ?></td>
                                <td><?php echo $account["max_size"] > 0 ? $quota_display : "No Quota"; ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <p>
                        <a href="https://www.hogwarts.wiz/mail" target="_blank" rel="noopener noreferrer" ?>Click here to access email through webmail</a>
                    </p>
                <?php else : ?>
                    <div class="alert alert-info border-2 border-secondary rounded-0">
                        <h6 class="text-dark fw-bolder">
                            Email Account for <?php echo $email_username; ?> does not exist
                        </h6>
                        <p>
                            You can raise a request to create an email account by clicking the button below.
                            This will then be sent for approval, and if authorisation is granted, will be processed
                            within 24 hours. Your email account with then be available with the same credentials.
                            If you have access via HADS, then those credentials will be used instead.
                        </p>

                        <div class="bg-white p-3 border border-1 border-dark mb-1 shadow d-flex gap-3 justify-content-start align-items-center">
                            <?php if ($email_request) : ?>
                                <div class="alert-processing border-2 text-dark alert alert-warning flex-fill m-0">
                                    <h6 class="m-0 fw-bolder">
                                        <i class="h5 bi bi-info-circle me-2"></i>
                                        Account request exists - pending administrator approval
                                    </h6>
                                </div>
                            <?php else : ?>
                                <a class="shadow btn btn-primary border-dark border-1 mb-1 rounded-0" name="btnEmailCreate" id="btnEmailCreate" href="<?php echo WEBROOT; ?>/process-email-create-request.php" role="button">
                                    Request Email Account Creation
                                </a>
                                <div class="d-none alert-processing border-2 text-dark alert alert-warning flex-fill m-0">
                                    <h6 class="m-0">Processing...</h6>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</main>


<script>
    function createEmailAccountRequest() {
        const createEmailRequestButton = document.getElementById("btnEmailCreate");
        const alertProcess = document.querySelector(".alert-processing");
        if (!createEmailRequestButton) {
            return 0
        }

        createEmailRequestButton.addEventListener('click', async (e) => {
            e.preventDefault();

            try {
                const response = await fetch(`<?php echo WEBROOT; ?>/process-email-create-request.php?user=<?php echo urlencode($email_username); ?>`);
                alertProcess.classList.remove("d-none")
                if (response.ok) {
                    const responseData = await response.text();
                    let h6Element = alertProcess.querySelector('h6');
                    if (h6Element) {
                        h6Element.remove();
                    }
                    alertProcess.innerHTML = '';
                    alertProcess.classList.remove("alert-warning");
                    alertProcess.classList.remove("alert-danger");
                    alertProcess.classList.add("alert-success");
                    alertProcess.insertAdjacentHTML("afterbegin", "<h6 class='my-0'>Processing Complete - Request Created</h6>")
                } else {
                    // Handle server error
                    if (response.status === 403 || response.status === 401 || response.status === 400 || response.status === 302) {
                        const errorData = await response.json();
                        const errorReturned = errorData.error;
                        const errorListDisplay = errorData.error.map(error => `<li class='pb-1 ms-3'>${error}</li>`).join('');
                        let h6Element = alertProcess.querySelector('h6');
                        if (h6Element) {
                            h6Element.remove();
                        }
                        alertProcess.innerHTML = '';
                        alertProcess.insertAdjacentHTML("afterbegin", errorListDisplay);
                        alertProcess.insertAdjacentHTML("afterbegin", "<h6 class='fw-bolder'>An error occurred</h6>")
                        alertProcess.classList.remove("alert-warning");
                        alertProcess.classList.remove("alert-success");
                        alertProcess.classList.add("alert-danger");
                    } else {
                        console.error('Server error:', response.statusText);
                    }
                }
            } catch (error) {
                console.error('Fetch error:', error);
            }
        });
    }
    createEmailAccountRequest();
</script>



<?php require_once(FILEROOT . "/footer.php"); ?>