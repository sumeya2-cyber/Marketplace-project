<?php
// logout.php
session_start();
session_unset(); // Clear variables
session_destroy(); // Destroy session
header("Location: ../../login.php"); // Redirect to your login page
exit;
?>