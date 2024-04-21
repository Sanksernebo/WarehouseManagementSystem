<?php
session_start(); // Start the session
session_unset(); // Unset the session variables
session_destroy(); // Destroy the session
header("Location: login.php"); // Redirect to the login page
exit;
?>
