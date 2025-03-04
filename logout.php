<?php
session_start();

// Destroy all session data to log out the admin
session_unset();
session_destroy();

// Redirect to the login page
header("Location:Admin_Login.php ");
exit();
?>
