<?php
require_once(dirname(__FILE__, 2) . "/config/auth.php");

if (!isset($title)) {
    $title = "Hogwarts Directory";
} else {
    $title = $title . " | Hogwarts Directory";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preload"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap"
        as="style">
    <link href="https://fonts.googleapis.com/css2?family=Murecho:wght@100..900&display=swap" rel="preload"
        as="style">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Murecho:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header>
        <?php if ($_SESSION["login_method"] == "CLIENT_CERTIFICATE") : ?>
            <div class="alert" role="status" style="text-align: center; vertical-align: middle;">
                <span>
                    A valid client certificate was presented to this site for this account, enabling automatic login.
                    You can also disable certificate login for this account from your <a class="link-offset-1" href="<?php echo WEBROOT; ?>/profile.php#certificates">profile page.</a>
                </span>
            </div>
        <?php endif; ?>
    </header>