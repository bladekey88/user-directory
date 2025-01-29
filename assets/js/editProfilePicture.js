// if (window.location.href !== window.parent.location.href) {
//     console.info("modal is running in an iframe");
// }

const profileImage = document.getElementById("profileImage");
const profilePictureMessages = document.getElementById("profilePictureMessages");
const imgInfo = document.getElementById("img-info");
const imgInfoSection = document.getElementById("profile-image-info");
const uploadedImagesContainer = document.getElementById("uploaded-images-container");
const inputImage = document.getElementById("inputProfilePicture");
const formUpdateProfilePicture = document.getElementById('formUpdateProfilePicture');

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
        let idnumber = formUpdateProfilePicture.dataset.idnumberKey;
        console.log(idnumber);

        formData.append('profilePicture', blob, 'test.png');

        // formData.append('idnumber', "<?php echo $user["idnumber"]; ?>");

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
                    // TODO update original profile image;
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