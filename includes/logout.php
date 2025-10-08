<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Clear any cookies (if we add them later)
if (isset($_COOKIE['cryptic_quest_user'])) {
    setcookie('cryptic_quest_user', '', time() - 3600, '/');
}

// Redirect to home page
header('Location: ../index.php');
exit();
?>