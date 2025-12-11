<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";

// Check if specific notification ID is provided
$notification_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if the notifications table exists
$table_check = $conn->query("SHOW TABLES LIKE 'user_notifications'");
if ($table_check->num_rows === 0) {
    // Table doesn't exist, redirect back
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Mark single notification as read if ID is provided
if ($notification_id > 0) {
    $stmt = $conn->prepare("
        UPDATE user_notifications 
        SET is_read = 1 
        WHERE notification_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
} else {
    // Mark all notifications as read
    $stmt = $conn->prepare("
        UPDATE user_notifications 
        SET is_read = 1 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to previous page
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: ../pages/home_loggedin.php");
}
exit();
?>