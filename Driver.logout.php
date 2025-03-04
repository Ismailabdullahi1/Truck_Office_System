<?php
session_start();

// Destroy all session data to log out the driver
session_unset();
session_destroy();

// Redirect to the login page
header("Location: Driver_Login.php");
exit();
?>
