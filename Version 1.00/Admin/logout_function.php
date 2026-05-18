<?php

// Set session timeout (24 hours = 86400 seconds)
$session_timeout = 86400; 

// Check if last activity is set
if (isset($_SESSION['LAST_ACTIVITY'])) {
    // If session expired, destroy session and logout
    if (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout) {
        session_unset(); // Unset session variables
        session_destroy(); // Destroy session
        echo "<script>window.alert('Session expired. Please log in again.');</script>";
        echo "<script>window.location='AdminLogin.php';</script>";
        exit();
    }
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();

?>