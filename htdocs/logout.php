<?php
session_start(); // Resume the session

// Destroy all session data
session_unset();  // Removes all session variables
session_destroy(); // Destroys the session itself

// Redirect back to login page
header("Location: login.php");
exit();
?>