<?php
require_once(__DIR__ . "/config/auth.php");
$title = "HVLE Services";

//Global Permission and Role Check
if (!check_user_permission(PERMISSION_VIEW_USER)) {
    @require_once(dirname(FILEROOT, 2) . "/htdocs/errordocs/403.php");
    exit();
}

// Set up
$error = [];
$message = [];
$vle_username = $_SESSION["username"];
$idnumber = $_SESSION["idnumber"];
$vle_account = false;

$get_user_vle_info_query = run_sql(get_user_vle_info($vle_username, $idnumber), MOODLE_DB);
$get_user_vle_cohort_query = run_sql(get_user_vle_cohort($vle_username, $idnumber), MOODLE_DB);
$get_user_vle_enrolment_query = run_sql(get_user_vle_enrolments($vle_username, $idnumber), MOODLE_DB);


if ((mysqli_num_rows($get_user_vle_info_query) +
    mysqli_num_rows($get_user_vle_cohort_query) +
    mysqli_num_rows($get_user_vle_enrolment_query)
) > 0) {
    $vle_account = true;
    $vle_info =  mysqli_fetch_assoc($get_user_vle_info_query);
}

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

    #vle-cohort-table tr :not(th:last-of-type),
    #vle-cohort-table tr :not(td:last-of-type),
    #vle-enrolments-table :not(th:last-of-type),
    #vle-enrolments-table :not(td:last-of-type) {
        /* border-left: black solid 1px; */
        border-right: #cdcdcd solid 1px;
        text-align: left;
    }

    #vle-enrolments-table thead th {
        border-bottom: #cdcdcd solid 1px;
    }

    table#vle-enrolments-table td:empty {
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
            <div class="card-header text-bg-primary rounded-0">HogwartsVLE Account Details</div>
            <div class="card-body">
                <?php if ($vle_account) :
                    echo "<h6>You have access as <mark class='fw-bolder'>$vle_username</mark> to HogwartsVLE</h6>"; ?>
                    <hr>
                    <section name="vle-info" id="vle-info">
                        <h5>VLE Information</h5>
                        <table class="table-responsive styled-table mt-3" id="vle-account-table" name="vle-account-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th scope="col">Username</th>
                                    <th scope="col">Authorisation Source</th>
                                    <th scope="col">Email Confirmed</th>
                                    <th scope="col">Policy Agreed</th>
                                    <th scope="col">Suspended</th>
                                    <th scope="col">ID</th>
                                    <th scope="col">First Name</th>
                                    <th scope="col">Middle Name</th>
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
                                    <td><?php echo strtoupper($vle_info["auth"]); ?></td>
                                    <td><?php echo ucfirst($vle_info["emailconfirmed"]); ?></td>
                                    <td><?php echo ucfirst($vle_info["policyagreed"]); ?></td>

                                    <?php
                                    $class = ($vle_info["suspended"] == "true");
                                    $alertClass = $class ? 'alert alert-danger border-danger border-2 fw-bolder rounded-0' : '';
                                    ?>
                                    <td class="<?php echo $alertClass; ?>">
                                        <?php echo ucfirst($vle_info["suspended"]); ?>
                                    </td>
                                    <td><?php echo $vle_info["idnumber"]; ?></td>
                                    <td><?php echo $vle_info["firstname"]; ?></td>
                                    <td><?php echo $vle_info["middlename"]; ?></td>
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
                        <h5>Cohorts</h5>
                        <table class=" table-responsive styled-table mt-3" id="vle-cohort-table" name="vle-cohort-table" style="width:100%;">
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
                                while ($row = mysqli_fetch_assoc($get_user_vle_cohort_query)) : ?>
                                    <tr>
                                        <td><?php echo $row["cohort_name"]; ?></td>
                                        <td><?php echo $row["description"]; ?></td>
                                        <td><?php echo $row["cohort_type"]; ?></td>
                                        <td><?php echo $row["cohort_scope"]; ?></td>
                                        <td><?php echo (!empty($row["category_name"])) ? $row["category_name"] : "N/A"; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </section>
                    <section name="enrolments" id="enrolments">
                        <h5>Roles and Enrolments</h5>
                        <?php
                        $showCategoryColumns = false; // Flag to determine if the category columns should be shown
                        $showCourseColumns = false; // Flag to determine if the course columns should be shown
                        $showParentColumns = false; // Flag to determine if the parent-related columns should be shown

                        // Iterate through the dataset to check if any row has a non-empty categoryname
                        while ($row = mysqli_fetch_assoc($get_user_vle_enrolment_query)) {
                            if (!empty($row["categoryname"])) {
                                $showCategoryColumns = true;
                                break;
                            }
                        }
                        mysqli_data_seek($get_user_vle_enrolment_query, 0); // Reset the result pointer
                        while ($row = mysqli_fetch_assoc($get_user_vle_enrolment_query)) {
                            if (!empty($row["coursename"])) {
                                $showCourseColumns = true;
                                break;
                            }
                        }
                        mysqli_data_seek($get_user_vle_enrolment_query, 0); // Reset the result pointer
                        // Iterate through the dataset to check if any row has a non-empty categoryname
                        while ($row = mysqli_fetch_assoc($get_user_vle_enrolment_query)) {
                            if (!empty($row["parent_child_username"])) {
                                $showParentColumns = true;
                                break;
                            }
                        }
                        mysqli_data_seek($get_user_vle_enrolment_query, 0); // Reset the result pointer
                        ?>


                        <table class="table-responsive styled-table mt-3" id="vle-enrolments-table" name="vle-enrolments-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th colspan="2"></th>
                                    <?php if ($showCourseColumns) : ?>
                                        <th colspan="3">Enrolment Details for Roles in Course Contexts</th>
                                    <?php endif; ?>
                                    <?php if ($showCategoryColumns) : ?>
                                        <th colspan="2">Enrolment Details for Roles in Category Contexts</th>
                                    <?php endif; ?>
                                    <?php if ($showParentColumns) : ?>
                                        <th colspan="2">Dependent Details for Roles in Parent Contexts</th>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <th>Enrolled as</th>
                                    <th>Context</th>
                                    <?php if ($showCourseColumns) : ?>
                                        <th>Course</th>
                                        <th>Course Category</th>
                                        <th>Enrolment Method</th>
                                    <?php endif; ?>
                                    <?php if ($showCategoryColumns) : ?>
                                        <th>Category</th>
                                        <th>Courses in Category</th>
                                    <?php endif; ?>
                                    <?php if ($showParentColumns) : ?>
                                        <th>Dependent Username</th>
                                        <th>Dependent Fullname</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($get_user_vle_enrolment_query)) : ?>
                                    <tr>
                                        <td><?php echo $row["role"]; ?></td>
                                        <td><?php echo $row["context"]; ?></td>
                                        <?php if ($showCourseColumns) : ?>
                                            <td><?php echo $row["coursename"]; ?></td>
                                            <td><?php echo $row["course_categoryname"]; ?></td>
                                            <td><?php echo $row["component"] ? ucfirst(preg_split("/_/", $row["component"])[1]) : "Manual"; ?></td>
                                        <?php endif; ?>
                                        <?php if ($showCategoryColumns) : ?>
                                            <td><?php echo $row["categoryname"]; ?></td>
                                            <td><?php echo $row["category_coursename"]; ?></td>
                                            <td><?php echo $row["component"] ? ucfirst(preg_split("/_/", $row["component"])[1]) : "Manual"; ?></td>
                                        <?php endif; ?>
                                        <?php if ($showParentColumns) : ?>
                                            <td><?php echo $row["parent_child_username"]; ?></td>
                                            <td><?php echo $row["child_name"]; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </section>
                <?php else : ?>
                    <div class="alert alert-info border-2 border-secondary rounded-0">
                        <h6 class="text-dark fw-bolder">
                            VLE Account for <?php echo $vle_username; ?> does not exist
                        </h6>
                        <p>
                            You can raise a request to create an VLE account by clicking the button below.
                            This will then be sent for approval, and if authorisation is granted, will be processed
                            within 24 hours. Your VLE account with then be available with the same credentials.
                            If you have access via HADS, then those credentials will be used instead.
                        </p>

                        <div class="bg-white p-3 border border-1 border-dark mb-1">
                            <div class="text-center h6 fw-bold text-black">Account will be automatically created at login - no request needed</div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</main>


<?php require_once(FILEROOT . "/footer.php"); ?>