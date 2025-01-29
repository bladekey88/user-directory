<?php
// Set page title and import the header
require_once(dirname(__DIR__, 1) . "/config/auth.php");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crop test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.css" integrity="sha512-087vysR/jM0N5cp13Vlp+ZF9wx6tKbvJLwPO8Iit6J7R+n7uIMMjg37dEgexOshDmDITHYY5useeSmfD1MYiQA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .container {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            gap: 1rem;
        }

        .container>div {
            border: 2px solid black;
        }

        img {
            display: block;
            max-width: 100%;
        }

        .img-container {
            min-height: 10rem;
            max-width: 20rem;
        }

        #img-preview {
            display: block;
            width: 200px;
            height: 250px;
            /* min-width: 0px 
            min-height: 0px 
            max-width: none; */
            overflow: hidden;
        }
    </style>

</head>

<body>
    <input type="file" name="profilePicture" id="profilePicture">
    <button id="getImage" role="button">Update Profile Picture</button>
    <div class="container">
        <div class="img-container">
            <img src="" id="profileImage" />
        </div>
        <div id="img-preview">
            <img id="profileImagePreview" />
        </div>
    </div>

</body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" integrity="sha512-JyCZjCOZoyeQZSd5+YEAcFgz2fowJ1F1hyJOXgtKu4llIa0KneLcidn5bwfutiehUTiOuK87A986BZJMko0eWQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- <script>
    
    var cropper;
    importImage();


    const btnGetImage = document.getElementById('getImage');
    btnGetImage.addEventListener("click", function(ev) {
        saveToImage();
    });

    function importImage() {

        const inputImage = document.getElementById('profilePicture');
        const profileImage = document.getElementById("profileImage")
        inputImage.addEventListener("change", function() {

            // Get file information
            var files = this.files;
            var file;
            var URL = window.URL

            if (files && files.length) {
                file = files[0];

                // Do basic check to confirm if valid image
                if (!isFileImage(file)) {
                    console.error(`${file.name} is not an image`);
                    return;
                }

                let uploadedImageType = file.type;
                let uploadedImageName = file.name;
                let uploadedImageURL;

                if (uploadedImageURL) {
                    URL.revokeObjectURL(uploadedImageURL);
                }

                profileImage.src = uploadedImageURL = URL.createObjectURL(file);
                if (cropper) {
                    cropper.destroy();
                    console.log("cropper destroyed");
                }

                var options = {
                    aspectRatio: 4 / 9,
                    preview: '#img-preview',
                    viewMode: 0,
                    dragMode: 'move',
                    rotatable: false,
                    scalable: true,
                    zoomable: true,
                }

                cropper = new Cropper(profileImage, options);
                inputImage.value = null;

            }

        });
    }

    function isFileImage(file) {
        return file && file['type'].split('/')[0] === 'image';
    }

    function saveToImage() {
        cropper.getCroppedCanvas({
            fillColor: '#fff',
            imageSmoothingEnabled: false,
            imageSmoothingQuality: 'high',
        }).toBlob((blob) => {
            const formData = new FormData();
            formData.append('profilePicture', blob, "test.png");
            formData.append('idnumber', '<?php echo $_SESSION["idnumber"]; ?>');
            console.log(formData);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo WEBROOT; ?>/upload-profile-picture.php');

            xhr.onload = () => {
                const response = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && !response.error) {
                    console.info(response.message);
                    console.info(response.imagePath);
                } else {
                    console.error(response.error);
                }
            };
            xhr.onerror = () => {
                console.log('Upload failed: Network error');
            };
            xhr.send(formData);

        })
    };
</script> -->

<script>
    if (window.location.href !== window.parent.location.href) {
        console.info("modal is running in an iframe")
    }


    let cropper; // Declare cropper globally for easier access

    function importImage() {
        const inputImage = document.getElementById('profilePicture');
        const profileImage = document.getElementById('profileImage');

        inputImage.addEventListener('change', function(event) {
            const files = event.target.files;

            if (!files || !files.length) {
                return; // No file selected
            }

            const file = files[0];

            if (!isFileImage(file)) {
                console.error(`${file.name} is not an image`);
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;

                if (cropper) {
                    cropper.destroy();
                    console.log('Cropper destroyed');
                }

                const options = {
                    aspectRatio: 4 / 5,
                    preview: '#img-preview', // Assuming you have an element with id #img-preview
                    viewMode: 0,
                    dragMode: 'move',
                    rotatable: false,
                    scalable: true,
                    zoomable: true,
                };

                cropper = new Cropper(profileImage, options);
                // inputImage.value = null; // Clear input field after selection
            };
            reader.readAsDataURL(file);
        });
    }

    function isFileImage(file) {
        return file && file.type.split('/')[0] === 'image';
    }

    function saveToImage() {
        if (!cropper) {
            console.error('No image selected or cropper is not initialized');
            return;
        }

        cropper.getCroppedCanvas({
            fillColor: '#fff',
            imageSmoothingEnabled: false,
            imageSmoothingQuality: 'high',
        }).toBlob((blob) => {
            const formData = new FormData();
            formData.append('profilePicture', blob, 'test.png');
            formData.append('idnumber', '<?php echo $_SESSION["idnumber"]; ?>');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo WEBROOT; ?>/upload-profile-picture.php');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (!response.error) {
                            console.info(response.message);
                            console.info(response.imagePath);
                        } else {
                            console.error(response.error);
                        }
                    } catch (error) {
                        console.error('Error parsing server response:', error);
                    }
                } else {
                    console.error('Upload failed:', xhr.statusText);
                }
            };

            xhr.onerror = function() {
                console.error('Upload failed: Network error');
            };

            xhr.send(formData);
        });
    }

    importImage(); // Call importImage to initialize Cropper

    const btnGetImage = document.getElementById('getImage');
    btnGetImage.addEventListener('click', function() {
        saveToImage();
    });
</script>



</html>