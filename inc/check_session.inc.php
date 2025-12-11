<?php
session_start();
include "db.inc.php"; // Ensure DB connection

// Redirect if user is not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../pages/login.php");
    exit();
}

// Fetch user details and store in session
$stmt = $conn->prepare("SELECT fname, lname, email, username, profile_pic, is_admin FROM user_info WHERE member_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$stmt->bind_result($fname, $lname, $email, $username, $profile_pic, $is_admin);
$stmt->fetch();
$stmt->close();


// Set session variables
$_SESSION['fname'] = $fname;
$_SESSION['lname'] = $lname;
$_SESSION['email'] = $email;
$_SESSION['username'] = $username;
$_SESSION['is_admin'] = $is_admin;

// Set profile_pic (use default if not set)
if (empty($profile_pic)) {
    $_SESSION['profile_pic'] = "image/default_pfp.jpg";
} else {
    $_SESSION['profile_pic'] = $profile_pic;
}
?>