<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";

// Check if the notifications table exists
$table_check = $conn->query("SHOW TABLES LIKE 'user_notifications'");
if ($table_check->num_rows === 0) {
    // Table doesn't exist, redirect back
    header("Location: ../pages/notifications.php");
    exit();
}

// Delete all notifications for this user
$stmt = $conn->prepare("
    DELETE FROM user_notifications 
    WHERE user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

// Redirect back to notifications page
header("Location: ../pages/notifications.php");
exit();
?>