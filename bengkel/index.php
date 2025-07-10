<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect to appropriate dashboard if logged in
if (isLoggedIn()) {
    redirectBasedOnRole();
    exit;
}

// Otherwise redirect to login page
header("Location: login.php");
exit;
?>