<?php
$title = "Add New User";

require_once("header.php");
?>

<style>
    form .form-label.helper:has(+ [required]):after {

        content: " * ";
        font-weight: bold;
        font-size: 1rem;
        color: darkred;
    }

    input:focus:required:invalid {
        box-shadow: none;
    }

    select:invalid,
    input:invalid {
        box-shadow: 0 0 5px 1px #eb7878;
    }

    select:valid:required,
    input:valid:required {
        box-shadow: 0 0 5px 1px darkgreen;
    }
</style>
<main>
    <div class="container-xl mt-4 px-4">
        <nav class="nav nav-borders">
            <h4 class="m-0">Add New User</h4>
        </nav>
        <hr class="mt-0 mb-4">
        <div class="card mb-2">
            <div id="new-user-details" class="card-body ms-4">
                <noscript>
                    <span class="fw-lighter text-danger">* indcates required field</span>
                </noscript>
                <form action="process-user.php" method="post" class="form" id="add-user" name="add-user" novalidate>
                    <fieldset class="d-flex gap-5 mb-2 p-3 border  rounded-1 align-items-center flex-wrap">
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="firstname">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" required maxlength="20">
                        </div>
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="lastname">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" required maxlength="20">
                        </div>
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="middlename">Middle Name</label>
                            <input type="text" class="form-control" id="middlename" name="middlename" minlength="2" maxlength="20">
                        </div>
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="commonname">Common Name (Preferred to be known as)</label>
                            <input type="text" class="form-control" id="commonname" name="commonname" minlength="2" maxlength="20">
                        </div>
                    </fieldset>
                    <fieldset class="d-flex gap-5 mb-2 p-3 border  rounded-1 align-items-center flex-wrap">
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="username">Username</label>
                            <input type="text" pattern="[a-z]\.[a-z]+[0-9]{0,2}" class="form-control" id="username" name="username" required minlength="3" maxlength="20" autocomplete="username">
                        </div>
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required minlength="3">
                        </div>
                    </fieldset>
                    <fieldset class="d-flex gap-5 my-2 p-3 border  rounded-1 align-items-center flex-wrap">
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password" minlength="6">
                        </div>
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="confirm-password">Confirm Password</label>
                            <input type="password" id="confirm-password" name="confirm-password" class="form-control" required autocomplete="new-password" minlength="6">
                        </div>
                    </fieldset>
                    <fieldset class="d-flex gap-5 mb-2 p-3 border rounded-1 align-items-center flex-wrap">
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="house">House</label>
                            <select name="house" class="form-select" id="house" aria-label="Select School House" required>
                                <option selected disabled value="">Select a School House</option>
                                <?php foreach (SCHOOL_HOUSES as $house) : ?>
                                    <option required value="<?php echo $house; ?>">
                                        <?php echo ucwords(strtolower(($house))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-element flex-grow-1">
                            <label class="form-label helper" for="year">Year</label>
                            <select name="year" class="form-select" id="year" aria-label="Select School Year" required>
                                <option selected disabled value="">Select a School Year</option>
                                <?php foreach (SCHOOL_YEARS as $year) : ?>
                                    <option required value="<?php echo $year; ?>">
                                        <?php echo ucwords(strtolower(($year))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </fieldset>
                    <div id="form-errors">
                        <ul class="form-errorlist"></ul>
                    </div>
                    <div class="my-3 d-flex align-items-center justify-content-center gap-3 flex-wrap">
                        <input type="submit" class="btn btn-outline-primary rounded-0 btn-lg" id="submitbutton" value="Add User">
                        <input type="reset" class="btn btn-outline-light btn-lg rounded-0 text-secondary border border-dark" value="Reset All">
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    function labelMandatoryFields() {
        const mandatoryFormFieldsLabels = document.querySelectorAll("form .form-label:has(+ [required])");

        mandatoryFormFieldsLabels.forEach((field) => {
            const badge = document.createElement("span");
            badge.classList.add("badge", "text-bg-warning", "indicator", "mx-1");
            badge.textContent = "Required";
            field.insertAdjacentElement("afterend", badge);
            field.classList.remove("helper"); //Remove this for presentation purposes (used for fallback if JS disabled)
        });
    }

    function formValidation() {
        const addUserForm = document.getElementById('add-user');
        const firstNameField = document.getElementById("firstname");
        const lastNameField = document.getElementById("lastname");
        const usernameField = document.getElementById("username");
        const passwordField = document.getElementById("password");
        const confirmPasswordField = document.getElementById("confirm-password");
        const emailField = document.getElementById("email");
        const yearField = document.getElementById("year");
        const houseField = document.getElementById("house");
        const submitButton = document.getElementById("submitbutton")
        const formErrors = document.getElementById("form-errors");
        const formErrorList = document.querySelector(".form-errorlist");

        //Default Section
        firstNameField.addEventListener('blur', e => {
            if (firstNameField.validity.valid && !usernameField.value && lastNameField.validity.valid) {
                //suggest username
                usernameField.value = firstNameField.value[0].toLowerCase() + "." + lastNameField.value.toLowerCase();
            }
        });
        lastNameField.addEventListener('blur', e => {
            if (lastNameField.validity.valid && !usernameField.value && firstNameField.validity.valid) {
                //suggest username                
                usernameField.value = firstNameField.value[0].toLowerCase() + "." + lastNameField.value.toLowerCase();
            }
        });
        usernameField.addEventListener('blur', e => {
            // Perform validation here
            if (usernameField.validity.patternMismatch) {
                usernameField.setCustomValidity("Username should be in the format 'a.bcd...' (with an optional number if pattern exists)")
            } else {
                usernameField.setCustomValidity("")
                //Add value to email address by default
                if (!emailField.value) {
                    emailField.value = usernameField.value + "@hogwarts.wiz";
                }
            }
            // Add more validation rules as needed
        });

        addUserForm.addEventListener("submit", async e => {
            e.preventDefault();

            //Handle non-HTML errors
            formErrorList.innerHTML = '';
            let hasError = false;
            let selectedYearValue = yearField.options[yearField.selectedIndex].text;
            let selectedHouseValue = houseField.options[houseField.selectedIndex].text
            let h6Element = formErrors.querySelector('h6');
            if (h6Element) {
                h6Element.remove();
            }
            //Password mismatch
            if (passwordField.value != confirmPasswordField.value) {
                formErrorList.insertAdjacentHTML("afterbegin", "<li class='ms-3'>The passwords entered do not match</li>");
                hasError = true;
            }
            //Invalid Email Domain
            if (emailField.value.split("@")[1] != "hogwarts.wiz") {
                formErrorList.insertAdjacentHTML("afterbegin", "<li class='ms-3'>The email domain is not valid (must be <span class='fw-bolder'>@hogwarts.wiz</span>)</li>");
                hasError = true;
            }
            //Year or House Logic
            if (yearField.value == "STAFF" && houseField.value != "HOGWARTS") {
                formErrorList.insertAdjacentHTML("afterbegin", `<li class='ms-3'>'House' must be set to <span class='fw-bolder'>Hogwarts</span> when 'Year' is <span class='fw-bolder'>${selectedYearValue}</span></li>`);
                hasError = true;
            } else if (yearField.value == "NONE" && houseField.value != "NONE") {
                formErrorList.insertAdjacentHTML("afterbegin", `<li class='ms-3'>'House' must be set to <span class='fw-bolder'>None</span> when 'Year' is <span class='fw-bolder'>${selectedYearValue}</span></li>`);
                hasError = true;
            } else if (yearField.value.includes("YEAR") && houseField.value == "NONE") {
                formErrorList.insertAdjacentHTML("afterbegin", `<li class='ms-3'>'House' must not be set to <span class='fw-bolder'>None</span> when 'Year' is <span class='fw-bolder'>${selectedYearValue}</span></li>`);
                hasError = true;
            }

            if (hasError) {
                formErrors.insertAdjacentHTML("afterbegin", "<h6 class='fw-bolder rounded-0 m-2 p-1'>The form contains errors</h6>")
                formErrors.className = "alert alert-danger m-1 p-0 rounded-0 border border-danger";
            } else {
                //Submit the form via FetchAPI
                try {
                    const formData = new FormData(addUserForm);
                    const response = await fetch('process-user.php', {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.ok) {
                        formErrors.className = '';
                        formErrors.className = "alert alert-warning m-1 p-0 rounded-0 border border-warning";
                        formErrors.insertAdjacentHTML("afterbegin", "<h6 class='fw-bolder rounded-0 m-2 p-1'>Please wait - Processing...</h6>")
                        const responseData = await response.text();
                        let h6Element = formErrors.querySelector('h6');
                        if (h6Element) {
                            h6Element.remove();
                        }
                        formErrors.className = '';
                        formErrors.className = "alert alert-success m-1 p-0 rounded-0 border border-success";
                        formErrors.insertAdjacentHTML("afterbegin", "<h6 class='fw-bolder rounded-0 m-2 p-1'>Processing Complete - User Created</h6>")
                        formErrorList.insertAdjacentHTML("afterbegin", `<li class='pb-1 ms-3'><a href="/directory/profile.php?user=${usernameField.value}">View User</a></li>`);
                    } else {
                        // Handle server error
                        if (response.status === 400) {
                            const errorData = await response.json();
                            const errorReturned = errorData.error;
                            const errorListDisplay = errorData.error.map(error => `<li class='pb-1 ms-3'>${error}</li>`).join('');
                            formErrorList.innerHTML = '';
                            let h6Element = formErrors.querySelector('h6');
                            if (h6Element) {
                                h6Element.remove();
                            }
                            formErrorList.insertAdjacentHTML("afterbegin", errorListDisplay);
                            formErrors.insertAdjacentHTML("afterbegin", "<h6 class='fw-bolder rounded-0 m-2 p-1'>The form contains errors</h6>")
                            formErrors.className = "alert alert-danger m-1 p-0 rounded-0 border border-danger";
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

    labelMandatoryFields();
    formValidation();
</script>


<?php require_once("footer.php"); ?>