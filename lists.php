<?php
require_once(__DIR__ . "/config/auth.php");
$title = "Email Services";

//Global Permission and Role Check
if (!check_user_permission(PERMISSION_VIEW_USER)) {
    @require_once(dirname(FILEROOT, 2) . "/htdocs/errordocs/403.php");
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
$login = null;
if (!$login) {
    $fallback_login = $obBaseApp->Authenticate(HMAIL_ADMIN_USER, HMAIL_ADMIN_PW);
    if ($fallback_login) {
        define("HMAIL_ADMIN_LOGIN", true);
    } else {
        $error["auth_failed"] = "Authentication to email server failed. This could be because the provided username/password are wrong, or the account does not exist, or the account is not active.";
        print_r($error["auth_failed"]);
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
    $account = array();
    $account["address"] = $email_account->Address;
    $distribution_list_count = $domain->DistributionLists->Count;
} catch (com_exception $e) {
    $email_account = NULL;
}




// com_print_typeinfo($domain->DistributionLists);

require_once(FILEROOT . "/header.php");
?>

<style>
    table {
        min-width: 40rem;
        overflow: hidden !important;
    }

    .styled-table th,
    .styled-table td {
        /* padding-inline: 0; */
        margin: 0;
    }

    .styled-table tbody>tr:hover {
        color: inherit;
        background-color: inherit;
        cursor: auto;
    }

    table>tbody>tr>td:not(:last-of-type),
    table>tbody>tr>th:not(:last-of-type) {
        /* border-left: black solid 1px; */
        border-right: #cdcdcd solid 1px;
        text-align: left;
    }

    table thead th {
        border-bottom: #cdcdcd solid 1px;
    }

    table td:empty {
        background-color: #dedede;
        border-block: #cdcdcd 1px solid;
        border-right: none;
        cursor: not-allowed;
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
                <?php if ($email_account && $distribution_list_count > 0) :
                    echo "<h6>You have access as <mark class='fw-bolder'>$email_username</mark> to " . $domain->Name . " mail domain.</h6>"; ?>
                    <table class="table-responsive styled-table" id="email-account-table" name="email-account-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th class="text-start">List Address</th>
                                <th class="text-start">List Active</th>
                                <th class="text-start">Member Count</th>
                                <th class="text-start">Members</th>
                                <th class="text-start">List Mode</th>
                                <th class="text-start">List Designated Sender (if applicable)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < $distribution_list_count; $i++) : ?>
                                <tr>
                                    <?php $dlist = $domain->DistributionLists->Item($i); ?>
                                    <td><?php echo $dlist->Address; ?></td>
                                    <td><?php echo $dlist->Recipients->Count; ?></td>
                                    <?php
                                    $dlist_recipients = $dlist->Recipients;
                                    if ($dlist_recipients) {
                                        echo "<td><ul>";
                                        for ($j = 0; $j < $dlist_recipients->Count; $j++) {
                                            echo "<li>" . $dlist_recipients->Item($j)->RecipientAddress . "</li>";
                                        }
                                        echo "</ul></td>";
                                    }
                                    ?>
                                    <td><?php echo $dlist->Active ? "Y" : "N"; ?></td>
                                    <?php
                                    if ($dlist->Mode == 0) {
                                        $mode = "public";
                                    } elseif ($dlist->Mode == 1) {
                                        $mode = "members";
                                    } elseif ($dlist->Mode == 2) {
                                        $mode = "announcement";
                                    } else {
                                        $mode = "unknown";
                                    }
                                    ?>
                                    <td><?php echo ucfirst($mode); ?>
                                    <td><?php echo $dlist->RequireSenderAddress; ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once(FILEROOT . "/footer.php"); ?>