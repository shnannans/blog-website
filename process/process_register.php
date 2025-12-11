<?php
include "../inc/head.inc.php";
include "../inc/nav.inc.php";
require "../inc/db.inc.php";
require "../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$errorMsg = "";
$success = true;

function sanitize_input($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = sanitize_input($_POST["fname"]);
    $lname = sanitize_input($_POST["lname"]);
    $email = sanitize_input($_POST["email"]);
    $password = $_POST["pwd"];
    $confirm_password = $_POST["pwd_confirm"];
    $username = sanitize_input($_POST["username"]);

    if (empty($lname) || empty($email) || empty($password) || empty($username)) {
        $errorMsg .= "All fields are required.";
        $success = false;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg .= "Invalid email format.";
        $success = false;
    }

    if ($password !== $confirm_password) {
        $errorMsg .= "Passwords do not match.";
        $success = false;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($success) {
        $stmt = $conn->prepare("SELECT email, username FROM user_info WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($existing_email, $existing_username);
            while ($stmt->fetch()) {
                if ($existing_email === $email) {
                    $errorMsg .= "This email is already registered.";
                }
                if ($existing_username === $username) {
                    $errorMsg .= "This username is already taken.";
                }
            }
            $success = false;
        }
        $stmt->close();
    }

    if ($success) {
        $verifyToken = bin2hex(random_bytes(16));
        $verifyLink = "http://35.212.188.114/pages/verify_email.php?token=$verifyToken";
        // $verifyLink = "http://35.212.188.114/pages/verify_email.php?token=$verifyToken"; //Ming Yang's Google Instance IP Address
    
        $mail = new PHPMailer(true);
        try {

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'inf1005group1@gmail.com';
            $mail->Password = 'wzfgaudtcclauocq';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
    
            $mail->setFrom('inf1005group1@gmail.com', 'Blooger');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email';
            $mail->Body = "
                <html>
                <body>
                    <p>Hello,</p>
                    <p>You've register an account with Blooger. Click the link below to verify your account:</p>
                    <p><a href='$verifyLink'>$verifyLink</a></p>
                    <p>This link will expire in 30 minutes. If you did not request this, please ignore this email.</p>
                </body>
                </html>
            ";
            $mail->AltBody = "Hello,\n\nYou've register an account with Blooger. Visit the link below to verify your account:\n$verifyLink\n\nIf you didn’t request this, you can ignore this email.";

    
            $mail->send(); // Only insert into DB if email is sent
    
            $stmt = $conn->prepare("INSERT INTO user_info (fname, lname, email, password, username, is_verified, verify_token) VALUES (?, ?, ?, ?, ?, 0, ?)");
            $stmt->bind_param("ssssss", $fname, $lname, $email, $hashed_password, $username, $verifyToken);
            
            if ($stmt->execute()) {
                header("Location: ../pages/login.php?registered=success");
                exit();
            } else {
                $errorMsg .= "Database error: " . $stmt->error;
                $success = false;
            }
            $stmt->close();
    
        } catch (Exception $e) {
            $errorMsg .= "Mailer Error: {$mail->ErrorInfo}";
            $success = false;
        }
    }    

    if (!$success) {
        header("Location: ../pages/register.php?error=" . urlencode($errorMsg));
        exit();
    }

    $conn->close();
}

include "../inc/footer.inc.php";
?>