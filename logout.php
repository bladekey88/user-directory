<?php
require_once("assets/config/auth.php");

session_destroy();
header("HTTP/1.1 401 Unauthorized");
header("location: /directory/login.php");
exit();
