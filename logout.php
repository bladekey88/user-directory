<?php
@require_once(__DIR__ . "/config/functions.php");
@session_start();
@session_destroy();
redirect("/login.php");
