<?php
require "../inc/check_admin.inc.php";
require "../inc/db.inc.php";

// Check if user_id and status are provided
if (isset($_GET['id']) && isset($_GET['status'])) {
    $user_id = intval($_GET['id']);
    $admin_status = intval($_GET['status']); // 0 or 1
    
    // Don't allow admins to remove their own admin status
    if ($user_id === $_SESSION['user_id'] && $admin_status === 0) {
        $_SESSION['admin_message'] = "You cannot remove your own admin privileges.";
        $_SESSION['admin_message_type'] = "warning";
        header("Location: ../pages/user_management.php");
        exit();
    }
    
    // Update admin status
    $stmt = $conn->prepare("UPDATE user_info SET is_admin = ? WHERE member_id = ?");
    $stmt->bind_param("ii", $admin_status, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = "User admin status updated successfully.";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Error updating user admin status: " . $stmt->error;
        $_SESSION['admin_message_type'] = "danger";
    }
    
    $stmt->close();
} else {
    $_SESSION['admin_message'] = "Invalid request.";
    $_SESSION['admin_message_type'] = "danger";
}

// Redirect back to user management page
header("Location: ../pages/user_management.php");
exit();
?>