<?php
require_once(__DIR__ . "/config/auth.php");
$title = "Home Page";
$datatable_needed = true;

if (!check_user_permission(PERMISSION_VIEW_ALL_USERS)) {
    require_once(FILEROOT . "/profile.php");
    exit();
} else {
    require_once(FILEROOT . "/header.php");
}

// Get all Users
$users = run_sql2(get_all_users());
?>

<main>
    <div class="container px-4 mt-4">
        <nav class="d-flex align-items-center justify-content-between">
            <h4 class="m-0">User List</h4>
            <?php if (check_user_permission(PERMISSION_ADD_USER)) : ?>
                <a id="btnaddnewuser" role="button" class="btn btn-sm btn-outline-primary border-2 rounded-0 mb-1" href="/directory/add-user.php">
                    <i class="bi bi-user-add"></i>
                    Add New User
                </a>
            <?php endif; ?>
        </nav>
        <hr class="mt-0 mb-4">
        <?php
        if ($users) : ?>
            <table class="styled-table" id="users" name="users" style="width:100%" ;>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Known As</th>
                        <th>House</th>
                        <th>Year</th>
                        <th>Last Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr data-user="<?php echo $user["username"]; ?>" role="link" <?php if ($user["locked"]) : ?> class="locked" <?php endif; ?>>
                            <td><?php echo $user["idnumber"]; ?></td>
                            <td><?php echo $user["username"]; ?></td>
                            <td><?php echo $user["firstname"] . " " . $user["lastname"]; ?></td>
                            <td><?php echo $user["commonname"]; ?> </td>
                            <td><?php echo $user["house"]; ?></td>
                            <td><?php echo $user["year"]; ?></td>
                            <td><?php echo $user["last_updated"]; ?></td>
                            <td class="pe-auto">
                                <a href="<?php echo WEBROOT; ?>/profile.php?user=<?php echo $user["username"]; ?>">
                                    <span class="badge text-bg-info">View User</span>
                                </a>
                                <?php if ($user["locked"]) : ?>
                                    <span class="locked bg-light text-danger border border-1 border-warning p-1 rounded-0 small">Locked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>


<script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-1.13.8/fh-3.4.0/r-2.5.0/datatables.min.js"></script>
<script>
    $(".container").css("display", "none");
    $(window).on("load", function() {
        new DataTable('#users', {
            stateSave: true,
            responsive: false,
            order: [
                [7, 'desc'],
                [1, 'asc']
            ],
            columnDefs: [{
                searchable: true,
                orderable: true,
                targets: -1
            }],
            fixedHeader: true,
        });

        //Remove style as document has loaded
        $(".container").css("display", "block")

        const userTable = document.querySelector("#users tbody");
        userTable.addEventListener('click', e => {
            let clickedElement = e.target;
            while (clickedElement && clickedElement.tagName !== 'SPAN' && clickedElement.tagName !== 'TR') {
                clickedElement = clickedElement.parentNode;
            }

            if (clickedElement && clickedElement.tagName === 'TR') {
                const url = "<?php echo WEBROOT; ?>/profile.php?user=" + clickedElement.dataset.user;
                window.location.href = url;
            }
        })
    })
</script>
<?php require_once("footer.php"); ?>