<?php
require_once(__DIR__ . "/config/auth.php");
$title = "Home Page";
$datatable_needed = true;

if (!check_user_permission(PERMISSION_VIEW_ALL_USERS)) {
    require_once(FILEROOT . "/profile.php");
    exit();
} else {
    require_once(FILEROOT . "/components/header.php");
}

// Get all Users
$users = run_sql2(get_all_users());

// Get user role column and find any users who have a role of None.    
// Convert to uppercase to compare to constant        
$user_role_column = array_map("strtoupper", array_column($users, "role", "username"));
$unique_values_user_role_column =  array_unique($user_role_column);

// Get any locked users as well
$user_locked_column = array_column($users, "locked", "username");
// $unique_values_user_role_column =  array_unique($user_role_column);

?>
<div class="wrapper">
    <?php require("components/sidebar.php"); ?>
    <main class="content">
        <?php require("components/nav-header.php"); ?>

        <section id="user-details">
            <noscript>
                <section id="site-messages">
                    <div class="alert danger" role="status">
                        <img class="alert-icon" src="assets/img/icon-warning2.svg" alt="javascript disabled"
                            label="javascript disabled warning icon">
                        <span>Javascript disabled. This site will have reduced functionality.</span>
                    </div>
                </section>
            </noscript>
            <h1>User Management</h1>
            <nav class="page-sections-navigation">
                <ul>
                    <li><a href="#show-own-profile-section">My Profile</a></li>
                    <li><a href="#user-list-section">User List</a></li>
                    <?php if (check_user_permission(PERMISSION_ADD_USER)): ?>
                        <li><a href="#user-creation-section">Add User</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <section id="show-own-profile-section" class="page-section-no-grid">
                <h2>My Profile</h2>
                <p><a href="<?php echo WEBROOT; ?>/profile2.php">Click this link to view your profile page</a></p>
            </section>


            <section id="user-list-section" class="page-section-no-grid">
                <h2>User List</h2>
                <?php if (in_array(ROLE_NONE, $unique_values_user_role_column)): ?>
                    <?php $count_of_user_no_role = array_count_values($user_role_column)[ROLE_NONE]; ?>
                    <div class=" alert warning" role="status">
                        <img class="alert-icon" src="assets/img/icon-warning2.svg" aria-hidden="true" alt="unknown role icon" label="users with unknown role">
                        <span>
                            There are users with an unknown or undefined role (Count: <?php echo $count_of_user_no_role; ?>)
                        </span>
                        <hr>
                        <a href="#" class="link-table-search-users-no-role">Click here to view users with unknown role</a>
                        <noscript>(This functionality requires javascript)</noscript>
                    </div>
                <?php endif; ?>
                <?php if (in_array(1, $user_locked_column)): ?>
                    <?php $count_of_user_locked = array_count_values($user_locked_column)[1]; ?>
                    <div class="alert warning" role="status">
                        <img class="alert-icon" src="assets/img/icon-warning2.svg" aria-hidden="true" alt="account locked icon" label="users with locked accounts">
                        <span>
                            There are users with locked accounts (Count: <?php echo $count_of_user_locked; ?>)
                        </span>
                        <hr>
                        <a href="#" class="link-table-search-users-locked">Click here to view users with locked accounts</a>
                    </div>
                <?php endif; ?>
                <div class="table-controls no-js">
                    <div class="table-navigation">
                        <ul class="pagination-top"></ul>
                    </div>
                    <span class="table-size"></span>
                    <input id="table-search" class="table-search" placeholder="Search Table Items" aria-label="Table Text Search" />
                </div>
                <div class="table-wrapper" tabindex="0" role="region" aria-labelledby="tableCaption_01">
                    <table id="user-list">
                        <thead>
                            <tr>
                                <th scope="col" class="sort" data-sort="role" data-default-order="asc"></th>
                                <th scope="col" class="sort" data-sort="user-id" data-default-order="asc">User ID</th>
                                <th scope="col" class="sort asc" data-sort="username" data-default-order="asc">Username</th>
                                <th scope="col" class="sort" data-sort="name" data-default-order="asc">Full Name</th>
                                <th scope="col" class="sort" data-sort="commonname" data-default-order="asc">Title/Known As</th>
                                <th scope="col" class="sort" data-sort="house" data-default-order="asc">House</th>
                                <th scope="col" class="sort" data-sort="year" data-default-order="asc">Year</th>
                                <th scope="col" class="sort" data-sort="date-updated" data-default-order='asc'>Date Last Updated
                                </th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            <?php foreach ($users as $user): ?>
                                <?php
                                $role = $user["role"];
                                switch ($role) {
                                    case "Student":
                                        $icon = '<img src="assets/img/icon-user.svg" alt="Role: Student" title="Role: Student">';
                                        break;
                                    case "Staff":
                                        $icon = '<img src="assets/img/icon-staff.svg" alt="Role: Staff" title="Role: Staff">';
                                        break;
                                    case "Administrator":
                                        $icon = '<img src="assets/img/icon-admin.svg" alt="Role: Administrator" style="margin-left:0.3rem" title="Role: Administrator">';
                                        break;
                                    case "Senior Staff":
                                        $icon = '<img src="assets/img/icon-seniorstaff.svg" alt="Role: Senior Staff" style="margin-left:0.3rem" title="Role: Senior Staff">';
                                        break;
                                    case "Parent":
                                        $icon = '<img src="assets/img/icon-parent.svg" alt="Role: Parent" title="Role: Parent">';
                                        break;
                                    default:
                                        $icon = '<img src="assets/img/icon-unknown.svg" alt="Role: Unknown" title="Role: Unknown">';
                                        break;
                                }

                                $unknown_role = (!in_array(strtoupper($role), USER_ROLES)) ? 1 : 0;
                                $locked = $user["locked"];
                                if ($locked == 1) {
                                    $icon = "<img src='assets/img/icon-locked.svg' alt='Account Locked' title='Account Locked - Role: $role'>";
                                }
                                ?>
                                <tr <?php echo ($locked == 1) ? "class='locked'" : null ?>
                                    <?php echo ($unknown_role == 1) ? "class='unknown-role'" : null ?>>
                                    <td class="role"><?php echo $icon; ?></td>
                                    <td class="user-id"><?php echo $user["idnumber"]; ?></td>
                                    <td class="username"><a class="link-table" href="<?php echo WEBROOT; ?>/profile2.php?user=<?php echo $user["username"]; ?>&idnumber=<?php echo $user["idnumber"]; ?>"><?php echo $user["username"]; ?></a></td>
                                    <td class=" name"><?php echo $user["firstname"] . " " . $user["lastname"]; ?></td>
                                    <td class="commonname"><?php echo $user["commonname"]; ?></td>
                                    <td class="house"><?php echo $user["house"]; ?></td>
                                    <td class="year"><?php echo $user["year"]; ?></td>
                                    <td class="date-updated"><?php echo $user["last_updated"]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-filter-controls no-js">
                    <button type="button" class="button" name="Filter Student" class="filter-item" aria-pressed="false">
                        <img class="filter-icon" src="assets/img/icon-filter-off.svg" alt="Filter Students" aria-hidden="true">
                        Filter Students
                    </button>
                    <button type="button" class="button" name="Filter Staff" class="filter-item" aria-pressed="false">
                        <img class="filter-icon" src="assets/img/icon-filter-off.svg" alt="Filter Staff" aria-hidden="true">
                        Filter Staff
                    </button>
                    <button type="button" class="button" name="Filter Parent" class="filter-item" aria-pressed="false">
                        <img class="filter-icon" src="assets/img/icon-filter-off.svg" alt="Filter Parents" aria-hidden="true">
                        Filter Parents
                    </button>
                    </button>
                    <button type="button" class="button" name="Clear Filter" class="filter-item">
                        <img class="filter-icon" src="assets/img/icon-filter-clear.svg" alt="Clear Filter" aria-hidden="true">
                        Clear Filter
                    </button>
                </div>
            </section>
        </section>
        <?php if (check_user_permission(PERMISSION_ADD_USER)): ?>
            <section id="user-creation-section" class="page-section-no-grid">
                <h2>User Creation</h2>
                <div class="command-section">
                    <h3>Add New User</h3>
                    <hr>
                    <div class="command-option">
                        <a href="<?php echo WEBROOT; ?>/add-user2.php">
                            <button id="add-new-user" type="button" class="button">
                                <img class="filter-icon" src="assets/img/icon-add-user.svg" aria-hidden="true" alt="An icon image of a user with a plus symbol">
                                <span>Add User</span>
                            </button>
                        </a>
                        <span>
                            Click the button to add a new user. The user can be granted a user role lower than your own (except for admins)
                        </span>
                    </div>
                <?php endif; ?>
                </div>
            </section>
    </main>
</div>

<script src="vendor/list.js/list.min.js"></script>
<script src="assets/js/table.js"></script>
<?php require_once("footer.php"); ?>