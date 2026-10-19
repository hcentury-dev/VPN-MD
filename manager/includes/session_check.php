<?php
	
require_once __DIR__ . '/session_boot.php';
if (!isset($_SESSION['name'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

	 
?>