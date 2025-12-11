<?php
session_start();
require_once "../inc/db.inc.php";
require_once "../inc/post_functions.inc.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Process post deletion
if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    
    // Verify the post exists and belongs to the user
    $check_stmt = $conn->prepare("SELECT post_id FROM post_info WHERE post_id = ? AND owner_id = ?");
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['message'] = "Post not found or you don't have permission to delete this post.";
        $_SESSION['message_type'] = "danger";
        header("Location: home_loggedin.php");
        exit();
    }
    $check_stmt->close();
    
    // Delete the post
    $delete_stmt = $conn->prepare("DELETE FROM post_info WHERE post_id = ? AND owner_id = ?");
    $delete_stmt->bind_param("ii", $post_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['message'] = "Post successfully deleted.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting post: " . $delete_stmt->error;
        $_SESSION['message_type'] = "danger";
    }
    $delete_stmt->close();
    $conn->close();
    
    // Redirect back to home page
    header("Location: home_loggedin.php");
    exit();
} else {
    // Invalid request - redirect with error message
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: home_loggedin.php");
    exit();
}
?>
