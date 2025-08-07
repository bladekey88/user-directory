<?php
require_once(__DIR__ . "/config/auth.php");


//Global Permission and Role Check
if (!check_user_permission(PERMISSION_ADD_USER)) {
    @header("HTTP/1.1 403 Forbidden");
    @require_once(dirname(__DIR__, 2) . "/htdocs/errordocs/403.php");
    redirect("/index2.php", "403 Forbidden");
    exit();
}
$current_user_roleid = run_sql2(get_user_role($_SESSION["userid"]))[0]["role_id"];

// Bring in header and begin page load
$title =  "Add New User";
@require_once(FILEROOT . "/components/header.php");

?>

<div class="wrapper">
    <?php require("components/sidebar.php"); ?>
    <main class="content">
        <?php require("components/nav-header.php"); ?>
        <section id="add-user-section">
            <noscript>
                <section id="site-messages">
                    <div class="alert danger alert-double" role="status">
                        <img class="alert-icon" src="assets/img/icon-warning2.svg" alt="javascript disabled"
                            label="javascript disabled warning icon">
                        <span>Javascript disabled. This site will have reduced functionality.</span>
                    </div>
                </section>
            </noscript>
            <h1>User Creation</h1>

            <section id="add-user-form-section" class="page-section-no-grid">
                <h2>Create New Directory User</h2>
                <p class="information-section"> Users can be created using this form. User accounts created through this method will be activated immediately.
                    The email address provided will not be created until the user logs in to Directory, and raises a request.</p>
                <form action="<?php echo WEBROOT; ?>/process-user.php" method="post" class="form" id="add-user" name="add-user" novalidate>
                    <fieldset>
                        <legend>
                            Personal Details</h3>
                        </legend>
                        <div>
                            <label for="firstname">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" required maxlength="30" autocomplete="given-name" placeholder="Thomas">
                            <span class="helper-message">First Name is a required field.</span>
                        </div>
                        <div>
                            <label for="lastname">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" required maxlength="30" autocomplete="family-name" placeholder="Smith">
                            <span class="helper-message">Last Name is a required field.</span>
                        </div>
                        <div>
                            <label for="middlename">Middle Name</label>
                            <input type="text" class="form-control" id="middlename" name="middlename" minlength="2" maxlength="30" autocomplete="additional-name" placeholder="Enter middle name(s) (if appropriate)">
                        </div>
                        <div>
                            <label for="commonname">Common Name (Preferred to be known as)</label>
                            <input type="text" class="form-control" id="commonname" name="commonname" minlength="2" maxlength="30" autocomplete="nickname" placeholder="Tom">
                            <span class="alert danger helper-message">Common Name field is not valid: It must contain at least two characters.</span>

                        </div>
                        <div>
                            <label for="sexgender">Sex</label>
                            <select name="sexgender" class="form-select" id="sexgender" aria-label="Select User Sex" required>
                                <option selected disabled value="select">Select a Value</option>
                                <?php foreach (USER_SEX_GENDER as $key => $value) : ?>
                                    <option required value="<?php echo $value; ?>">
                                        <?php echo ucwords(strtolower(($key))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>Account Details</legend>
                        <div>
                            <label for="username">Username</label>
                            <p class="information-section" id="username-format">Username must be in the format of first initial, followed by a dot, followed by the surname. If the username exists, you must append a number on the end.</p>
                            <input type="text" aria-describedby="username-format" pattern="[a-z]\.[a-z]+[0-9]{0,2}" class="form-control" id="username" name="username" required minlength="3" maxlength="20" autocomplete="username" placeholder="t.smith">
                            <span class="alert danger helper-message">Username field is not valid. It must match the pattern: <strong>initial.lastname{number}</strong> (number is only included if username exists)</span>

                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required minlength="3" autocomplete="email" placeholder="t.smith@hogwarts.wiz">
                        </div>
                        <div>
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password" minlength="6" placeholder="Enter password">
                        </div>
                        <div>
                            <label for="confirm-password">Confirm Password</label>
                            <input type="password" id="confirm-password" name="confirm-password" class="form-control" required autocomplete="new-password" minlength="6" placeholder="Confirm password">
                        </div>
                        <div>
                            <label for="role">Role</label>
                            <select name="role" id="role" aria-label="Selct a system role" required>
                                <option selected disabled>Select a Role</option>
                                <?php
                                foreach (USER_ROLES as $role) : ?>
                                    <?php
                                    $output = (get_role_details($role)["by_name"][0]["role_id"]);
                                    if ($output >= $current_user_roleid && !check_user_role(ROLE_ADMIN)) {
                                        continue;
                                    }
                                    ?>
                                    <option required value="<?php echo $role; ?>">
                                        <?php echo ucwords(strtolower(($role))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>School Details</legend>
                        <div>
                            <label for="house">House</label>
                            <select name="house" class="form-select" id="house" aria-label="Select School House" required>
                                <option selected disabled value="select">Select a School House</option>
                                <?php foreach (SCHOOL_HOUSES as $house) : ?>
                                    <option required value="<?php echo $house; ?>">
                                        <?php echo ucwords(strtolower(($house))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="house-value" aria-hidden="true"></div>
                        </div>
                        <div>
                            <label for="year">Year</label>
                            <select name="year" class="form-select" id="year" aria-label="Select School Year" required>
                                <option selected disabled value="select">Select a School Year</option>
                                <?php foreach (SCHOOL_YEARS as $year) : ?>
                                    <option required value="<?php echo $year; ?>">
                                        <?php echo ucwords(strtolower(($year))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="year-value" aria-hidden="true"></div>
                        </div>
                        <div>
                            <label for="toggle-prefect">Is a Prefect</label>
                            <div>
                                <input id="toggle-prefect" name="prefect" class="toggle" type="checkbox" aria-checked="false">
                                <span class="toggle-switch">
                                    <img src="assets/img/toggle_tick.svg" alt="checkmark icon" aria-hidden="true" focusable="false" class="toggle-icons checkmark">
                                    <img src="assets/img/toggle_cross.svg" alt="cross icon" aria-hidden="true" focusable="false" class="toggle-icons cross">
                                </span>
                            </div>
                        </div>
                        <div>
                            <label for="toggle-quidditch">Is a Quidditch Player</label>
                            <div>
                                <input id="toggle-quidditch" name="quidditch" class="toggle" type="checkbox" aria-checked="false">
                                <span class="toggle-switch">
                                    <img src="assets/img/toggle_tick.svg" alt="checkmark icon" aria-hidden="true" focusable="false" class="toggle-icons checkmark">
                                    <img src="assets/img/toggle_cross.svg" alt="cross icon" aria-hidden="true" focusable="false" class="toggle-icons cross">
                                </span>
                            </div>
                        </div>
                    </fieldset>
                    <div id="form-errors">
                        <ul class="form-errorlist"></ul>
                    </div>
                    <div class="form-controls">
                        <button type="submit" class="button" id="submitbutton" value="Add User">
                            <img class="filter-icon" src="assets/img/icon-add-user.svg" aria-hidden="true" alt="Submit the form - An icon image of a user with a plus symbol">
                            <span>Submit Form</span>
                        </button>
                        <button type="reset" class="button" value="Reset All">
                            <img class="filter-icon" src="assets/img/icon-clear.svg" aria-hidden="true" alt="Reset the Form icon">
                            <span>Clear Form</span>
                        </button>
                    </div>
                </form>


            </section>
        </section>
    </main>
</div>


<script>
    function formValidation() {
        const addUserForm = document.getElementById('add-user');
        const firstNameField = document.getElementById("firstname");
        const lastNameField = document.getElementById("lastname");
        const usernameField = document.getElementById("username");
        const passwordField = document.getElementById("password");
        const confirmPasswordField = document.getElementById("confirm-password");
        const emailField = document.getElementById("email");
        const yearField = document.getElementById("year");
        const yearValueField = document.getElementById("year-value");
        const houseField = document.getElementById("house");
        const houseValueField = document.getElementById("house-value");
        const prefectField = document.getElementById("toggle-prefect");
        const quidditchField = document.getElementById("toggle-quidditch");
        const submitButton = document.getElementById("submitbutton")
        const formErrors = document.getElementById("form-errors");
        const formErrorList = document.querySelector(".form-errorlist");

        //Default Section
        firstNameField.addEventListener('change', e => {
            if (firstNameField.validity.valid && !usernameField.value && lastNameField.validity.valid) {
                //suggest username
                usernameField.value = firstNameField.value[0].toLowerCase() + "." + lastNameField.value.toLowerCase();
            }
        });
        lastNameField.addEventListener('change', e => {
            if (lastNameField.validity.valid && !usernameField.value && firstNameField.validity.valid) {
                //suggest username                
                usernameField.value = firstNameField.value[0].toLowerCase() + "." + lastNameField.value.toLowerCase();
            }
        });
        usernameField.addEventListener('change', e => {
            // Perform validation here
            if (usernameField.validity.patternMismatch) {
                usernameField.setCustomValidity("Username should be in the format 'a.bcd...' (with an optional number if pattern exists)")
            } else {
                usernameField.setCustomValidity("")
                //Add value to email address by default
                if (!emailField.value && usernameField.validity.valid) {
                    emailField.value = usernameField.value + "@hogwarts.wiz";
                }
            }
            // Add more validation rules as needed
        });

        role.addEventListener('change', e => {
            if (e.target.value != "STUDENT") {
                quidditchField.checked = false;
                quidditchField.disabled = true;
                quidditchField.setAttribute("aria-checked", false);
                prefectField.checked = false;
                prefectField.disabled = true;
                prefectField.setAttribute("aria-checked", false);
                if (e.target.value == "PARENT") {
                    yearField.value = "NONE";
                    yearField.hidden = true;
                    yearValueField.innerHTML = yearField.value;
                    houseField.value = "NONE";
                    houseField.hidden = true;
                    houseValueField.innerHTML = houseField.value;
                } else if (e.target.value.includes("STAFF")) {
                    yearField.value = "STAFF";
                    yearField.hidden = true;
                    yearValueField.innerHTML = yearField.value;
                    houseField.value = "HOGWARTS";
                    houseField.hidden = true;
                    houseValueField.innerHTML = houseField.value;
                }
            } else {
                quidditchField.disabled = false;
                prefectField.disabled = false;
                yearField.value = "select";
                yearField.hidden = false;
                yearValueField.innerHTML = null;
                houseField.value = "select";
                houseField.hidden = false;
                houseValueField.innerHTML = null;
            }
        })

        addUserForm.addEventListener("submit", async e => {
            e.preventDefault();

            //Handle non-HTML errors
            formErrorList.innerHTML = '';
            let hasError = false;
            let selectedYearValue = yearField.options[yearField.selectedIndex].text;
            let selectedHouseValue = houseField.options[houseField.selectedIndex].text
            let h2Element = formErrors.querySelector('h2');
            let hrElement = formErrors.querySelector('hr');
            if (h2Element) {
                h2Element.remove();
                hrElement.remove();
            }
            //Password mismatch
            if (passwordField.value != confirmPasswordField.value) {
                formErrorList.insertAdjacentHTML("afterbegin", "<span>The passwords entered do not match</span>");
                hasError = true;
            }
            //Invalid Email Domain
            if (emailField.value.split("@")[1] != "hogwarts.wiz") {
                formErrorList.insertAdjacentHTML("afterbegin", "<span>The email domain is not valid (must be <strong>@hogwarts.wiz</span>)</span>");
                hasError = true;
            }
            //Year or House Logic
            if (yearField.value == "STAFF" && houseField.value != "HOGWARTS") {
                formErrorList.insertAdjacentHTML("afterbegin", `<span>'House' must be set to <strong>Hogwarts</strong> when 'Year' is <strong>${selectedYearValue}</strong></span>`);
                hasError = true;
            } else if (yearField.value.includes("YEAR") && houseField.value == "NONE") {
                formErrorList.insertAdjacentHTML("afterbegin", `<span>'House' must not be set to <strong>None</strong> when 'Year' is <strong>${selectedYearValue}</strong></span>`);
                hasError = true;
            }

            if (hasError) {
                formErrors.insertAdjacentHTML("afterbegin", "<h2>The form could not be submitted due to the following error(s)</h2><hr>")
                formErrors.className = "alert danger";
            } else {
                //Submit the form via FetchAPI
                try {
                    const formData = new FormData(addUserForm);
                    const response = await fetch('<?php echo WEBROOT; ?>/process-user.php', {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.ok) {
                        formErrors.className = '';
                        formErrors.className = "alert warning";
                        formErrors.insertAdjacentHTML("afterbegin", "<h2>Please wait - Processing...</h2>")
                        const responseData = await response.text();
                        let h2Element = formErrors.querySelector('h2');
                        if (h2Element) {
                            h2Element.remove();
                        }
                        formErrors.className = '';
                        formErrors.className = "alert success";
                        formErrors.insertAdjacentHTML("afterbegin", "<h2>Processing Complete - User Created</h2><hr>")
                        formErrorList.insertAdjacentHTML("afterbegin", `<span><a href="<?php echo WEBROOT; ?>/profile2.php?user=${usernameField.value}">View User</a></span>`);
                    } else {
                        // Handle server error
                        if (response.status === 400) {
                            const errorData = await response.json();
                            const errorReturned = errorData.error;
                            const errorListDisplay = errorData.error.map(error => `<li >${error}</li>`).join('');
                            // errorReturned.forEach(error => {
                            // errorListDisplay += `<li>${error}</li>`;
                            // });
                            formErrorList.innerHTML = '';
                            let h2Element = formErrors.querySelector('h2');
                            let hrElement = formErrors.querySelector('hr');
                            if (h2Element) {
                                h2Element.remove();
                                hrElement.remove();
                            }
                            formErrorList.insertAdjacentHTML("afterbegin", errorListDisplay);
                            formErrors.insertAdjacentHTML("afterbegin", "<h2>The form could not be submitted due to the following error(s)</h2><hr>")
                            formErrors.className = "alert danger";
                        } else {
                            console.error('Server error:', response.statusText);
                        }
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                }

                // addUserForm.submit();
            }
        });
    }


    formValidation()
</script>


<?php require_once("footer.php"); ?>