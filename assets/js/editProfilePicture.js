"use strict";

if (window.location.href !== window.parent.location.href) {
    console.info("modal is running in an iframe");
}

// Constants
const profileImage = document.getElementById("profileImage"); // Uploaded Profile Image
const profilePictureMessages = document.getElementById("profilePictureMessages"); // Div used for messages
const imgInfo = document.getElementById("img-info"); // Div used to hold the table showing image data
const imgInfoSection = document.getElementById("profile-image-info"); // The div that holds all the information
const uploadedImagesContainer = document.getElementById("uploaded-images-container"); // The container for the uploaded image and its preview
const inputImage = document.getElementById("inputProfilePicture"); // The actual input=file for the image
const formUpdateProfilePicture = document.getElementById('formUpdateProfilePicture');
const formUpdateProfilePictureDivs = document.querySelectorAll("form div:not(#profilePictureMessages)");

// Derived from Data Attributes
const idNumber = formUpdateProfilePicture.dataset.idnumberKey;
const fetchURI = formUpdateProfilePicture.dataset.fetchUri;

// Cropper setup
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

/*
 * Imports an image from the user's file system and displays it for cropping.
 *
 * This function handles image selection, validation, and display,
 * initializing the CropperJS library for image cropping.  It also displays
 * image information in a table.  Error handling is included for missing
 * CropperJS, invalid file types, and general upload issues.
 */
function importImage() {
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

    /*
     * Handles the image selection event.
     *
     * @param {Event} event The change event triggered by the file input.
     */
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
        reader.onload = function (e) {
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
/*
 * Returns a human-readable file size string.
 *
 * Converts a file size in bytes to KB or MB as appropriate.
 *
 * @param {number} number The file size in bytes.
 * @returns {string} A formatted file size string (e.g., "10.5 KB", "2.1 MB").
 */
function returnFileSize(number) {
    if (number < 1e3) {
        return `${number} bytes`;
    } else if (number >= 1e3 && number < 1e6) {
        return `${(number / 1e3).toFixed(1)} KB`;
    } else {
        return `${(number / 1e6).toFixed(1)} MB`;
    }
}

/*
 * Checks if a file is an image.
 *
 * Determines if a given file object represents an image based on its MIME type.
 *
 * @param {File} file The file object to check.
 * @returns {boolean} True if the file is an image, false otherwise.
 */
function isFileImage(file) {
    return file && file.type.split("/")[0] === "image";
}

/*
 * Creates an HTML table displaying file information.
 *
 * Generates an HTML table containing the file name, type, and size.
 *
 * @param {File} file The file object for which to create the table.
 * @returns {HTMLTableElement} The HTML table element.
 */
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
/*
 * Saves the cropped image to the server.
 *
 * Retrieves the cropped image from CropperJS as a blob, creates a FormData object,
 * and sends it to the server via a POST request. Handles server responses and
 * updates the UI accordingly.
 *
 * @returns {boolean} True if the save operation is initiated, false otherwise
 *                   (e.g., if CropperJS is not available or no image is selected).
 */
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
        formData.append('idnumber', idNumber);
        fetch(fetchURI, {
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

/*
 * Sets a message in the profile picture messages area.
 *
 * Displays an alert message with the given type (e.g., "success", "danger"),
 * title, and message content.
 *
 * @param {string} type  The type of message ("success", "danger", etc.).
 * @param {string} title The title of the message.
 * @param {string} message The message content.
 */
function setMessage(type, title, message) {
    profilePictureMessages.style.display = "block";
    profilePictureMessages.className = "alert";
    profilePictureMessages.classList.add(type);
    profilePictureMessages.innerHTML = `<h4>${title}</h4><hr><p>${message}</p>`;
}

/*
 * Clears the error message in the profile picture messages area.
 * Resets the message area by clearing its content and removing any alert classes.
 */
function clearErrorMessage() {
    profilePictureMessages.style.display = "block";
    profilePictureMessages.className = ""; // Reset className
    profilePictureMessages.innerHTML = "";
}

/*
 * Displays an error message.
 *
 * Calls `setMessage` with the "danger" type to display an error message.
 *
 * @param {string} title The title of the error message.
 * @param {string} message The error message content.
 */
function displayErrorMessage(title, message) {
    setMessage("danger", title, message);
}

/*
 * Displays a success message.
 *
 * Calls `setMessage` with the "success" type to display a success message.
 *
 * @param {string} title The title of the success message.
 * @param {string} message The success message content.
 */
function displaySuccessMessage(title, message) {
    setMessage("success", title, message);
}

/*
 * Checks if CropperJS is available.
 *
 * Attempts to create and destroy a Cropper instance to determine if the
 * library is loaded. Displays an error message if CropperJS is not available.
 *
 * @param {HTMLImageElement} imageElement The image element to check against.
 * @returns {boolean} True if CropperJS is available, false otherwise.
 */
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