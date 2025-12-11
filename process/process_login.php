<?php
session_start();
include "../inc/db.inc.php"; // Ensure correct DB connection

function sanitize_email($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_email($_POST["email"]);
    $password = $_POST["pwd"]; // No modifications on password

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["error"] = "Invalid email format.";
        header("Location: ../pages/login.php");
        exit();
    }

    // Prepare SQL query to check user credentials
    $stmt = $conn->prepare("SELECT member_id, password, is_verified FROM user_info WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // If user exists, verify password
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($member_id, $hashed_password, $is_verified);
        $stmt->fetch();

        if (!$is_verified) {
            $_SESSION["error"] = "Please verify your email before logging in.";
            header("Location: ../pages/login.php");
            exit();
        }

        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $member_id;
            
            // Redirect to homepage
            header("Location: ../pages/home_loggedin.php");
            exit();
        } 
    } 

    // Invalid email or password
    $_SESSION["error"] = "Invalid email or password.";
    header("Location: ../pages/login.php");
    exit();

    $stmt->close();
    $conn->close();
}
?>
