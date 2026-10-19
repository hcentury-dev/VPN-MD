<?php
require_once __DIR__ . '/includes/session_boot.php';
unset($_SESSION["admin_name"]);
session_destroy();

header( "Location:index.php");
exit;
?>