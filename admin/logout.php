<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wipe all session data and send the user back to the login screen
session_unset();
session_destroy();

header('Location: login.php');
exit;
