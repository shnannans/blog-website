<?php
require '../vendor/autoload.php'; // Adjust if your structure is different
include "../inc/db.inc.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $userEmail = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit;
    }

    $sql = "SELECT * FROM user_info WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the user email is found in the database
    if ($result->num_rows > 0) {
        // Email exists, generate reset token
        $resetToken = bin2hex(random_bytes(16));
        $resetLink = "http://35.212.188.114/pages/reset_password.php?token=" . $resetToken;
        // $resetLink = "http://35.212.188.114/pages/reset_password.php?token=" . $resetToken; // Ming Yang's Google Instance IP Address

        // Save the $resetToken to your database with user's email + expiry timestamp
        // Adjust table/column names as needed
        $expiryTime = date("Y-m-d H:i:s", strtotime('+30 minutes')); // Set expiry time for 30 minutes
        $updateSql = "UPDATE user_info SET reset_token = ?, token_expiry = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sss", $resetToken, $expiryTime, $userEmail);
        $updateStmt->execute();


        $mail = new PHPMailer(true);


        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  // Outlook SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'inf1005group1@gmail.com'; 
            $mail->Password = 'wzfgaudtcclauocq';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('inf1005group1@gmail.com', 'Blooger');
            $mail->addAddress($userEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Instructions';
            $mail->Body = "
                <p>Hello,</p>
                <p>You requested a password reset. Click the link below to reset your password:</p>
                <p><a href='$resetLink'>Reset Your Password</a></p>
                <p>This link will expire in 30 minutes. If you did not request this, please ignore this email.</p>
            ";

            $mail->send();
            header("Location: ../pages/login.php?reset=success&email=" . urlencode($userEmail));
            exit();
        } catch (Exception $e) {
            echo "Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Email not found. Please check the email address.";
    }
} else {
    echo "Invalid request.";
}
?>