<?php
require_once(__DIR__ . "/config/auth.php");

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> -->
    <link rel="stylesheet preload prefetch" as="style" crossorigin href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <?php if (isset($datatable_needed) && $datatable_needed) : ?>
        <link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-1.13.8/fh-3.4.0/r-2.5.0/datatables.min.css" rel="stylesheet">
    <?php endif; ?>

    <!-- Custom Css to override any bootstrap we don't want -->
    <link rel=" stylesheet" href="<?php echo WEBROOT . "/"; ?>assets/css/table.css">

    <style>
        * {
            font-family: 'DM Sans', sans-serif;
        }

        .nav-link .bi {
            font-size: 1.5rem;
            display: block;
            text-align: center;
            margin: 0;
        }

        .logo {
            max-width: 20%;
            padding-right: 1rem;
        }

        body {
            background-color: #f2f6fc;
        }

        .nav-borders .nav-link.active {
            color: #0061f2;
            border-bottom-color: #0061f2;
        }

        .nav-borders .nav-link {
            color: #69707a;
            border-bottom-width: 0.125rem;
            border-bottom-style: solid;
            border-bottom-color: transparent;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            padding-left: 0;
            padding-right: 0;
            margin-left: 1rem;
            margin-right: 1rem;
        }

        ul.nav a:hover:not(.nohover) {
            background-color: rgb(108, 26, 201);
        }

        main {
            margin-bottom: 1rem;
        }
    </style>

</head>

<body>
    <header>
        <nav>
            <div class="px-2 text-bg-dark border-bottom">
                <div class="container">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
                        <a href="/directory" class="d-flex align-items-center my-2 my-lg-0 me-lg-auto text-white text-decoration-none">
                            <img class="img-fluid logo" src="<?php echo WEBROOT; ?>/assets/img/logo_large.png" alt="Hogwarts Logo">
                            <span class=" h5">Hogwarts Directory</span>
                        </a>

                        <ul class="nav col-12 col-lg-auto my-2 justify-content-center my-md-0 text-small">
                            <li>
                                <a href="/directory" class="nav-link text-white m-0">
                                    <i class="bi bi-house-door"></i>
                                    <p>Home</p>
                                </a>
                            </li>
                            <li>
                                <a href="#" class=" disabled nav-link text-white">
                                    <i class="bi bi-grid text-secondary"></i>
                                    <p class="text-secondary">Systems</p>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo WEBROOT; ?>/profile.php" class="nav-link text-white">
                                    <i class="bi bi-person-circle"></i>
                                    <p>
                                        <?php echo $_SESSION["name"]; ?>
                                    </p>
                                </a>
                            </li>
                            <li>
                                <!-- <button type="button" role="link" class="ms-2 mt-3 rounded-0 btn btn-sm btn-outline-danger"> -->
                                <!-- <a role="button" href="/directory/logout.php" class="ms-2 mt-3 rounded-0 btn btn-sm btn-outline-danger nav-link text-white nohover"> -->
                                <a href="<?php echo WEBROOT; ?>/logout.php" class="btn btn-danger nohover border-2 rounded-0 mt-4 ms-3">
                                    Logout
                                </a>
                                <!-- </button> -->
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php if ($_SESSION["login_method"] == "CLIENT_CERTIFICATE") : ?>
                <div class="alert alert-info  border-0 border-secondary border-bottom  border-2 rounded-0 h6 p-0 px-5 py-1" role="alert">
                    <div class="container">
                        <p class="mb-0">
                            A valid client certificate was presented to this site for this account, enabling automatic login.
                            You can also disable certificate login for this account from your <a class="link-offset-1" href="profile.php#certificates">profile page.</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </header>