<nav class="main-header" aria-label="breadcrumb">
    <ul class="breadcrumb-list">
        <li class="breadcrumb-item"><a class="breadcrumb-link" href="#">Home</a></li>
        <li class="breadcrumb-item class="><a class=" breadcrumb-link" href="index2.php">Users</a></li>
        <li class="breadcrumb-item"><a class="breadcrumb-link active" href="#">User List</a></li>
    </ul>
    <ul class="user-info-list" aria-label="user-info">
        <li aria-label="House">H: <?php echo $_SESSION["house"]; ?></li>
        <li aria-label="Year">Y: <?php echo $_SESSION["year"]; ?></li>
        <li aria-label="Role">R: <?php echo $_SESSION["role"]; ?></li>
        <li aria-label="View My Profile">
            <a class="user-nav-link" href="<?php echo WEBROOT; ?>/profile.php"><?php echo $_SESSION["name"]; ?></a>
        </li>
        <li aria-label="Logout">
            <a class="user-nav-link" href="<?php echo WEBROOT; ?>/logout.php">Logout</a>
        </li>
    </ul>
</nav>
<header>
    <?php if ($_SESSION["login_method"] == "CLIENT_CERTIFICATE") : ?>
        <div class="alert alert-narrower" role="status" style="text-align: center; vertical-align: middle;">
            <span>
                A valid client certificate was presented to this site for this account, enabling automatic login.
                You can also disable certificate login for this account from your <a class="link-offset-1" href="<?php echo WEBROOT; ?>/profile.php#certificates">profile page.</a>
            </span>
        </div>
    <?php endif; ?>
</header>