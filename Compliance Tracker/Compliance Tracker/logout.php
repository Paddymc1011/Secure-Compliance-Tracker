<?php
// Simple logout handler used by admin and other pages.
session_start();

// Clear session and redirect to admin login by default
session_unset();
session_destroy();

// Use adminlogin.php as the default landing page after logout
header('Location: adminlogin.php');
exit();
