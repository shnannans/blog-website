<?php
// First check if user is logged in
require_once "check_session.inc.php";


// Then check if user is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    // Not an admin, redirect to home
    header("Location: ../pages/home_loggedin.php");
    exit();
}
?>