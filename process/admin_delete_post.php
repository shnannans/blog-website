<?php
session_start();
require_once "../inc/db.inc.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Verify admin status
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$user_id = $_SESSION['user_id'];

// Check if post_id is provided
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    
    // First verify the post exists
    $check_stmt = $conn->prepare("SELECT post_id, title FROM post_info WHERE post_id = ?");
    $check_stmt->bind_param("i", $post_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['admin_message'] = "Post not found.";
        $_SESSION['admin_message_type'] = "danger";
        header("Location: ../pages/admin.php");
        exit();
    }
    
    $post = $result->fetch_assoc();
    $post_title = htmlspecialchars($post['title']);
    $check_stmt->close();
    
    // If this is the confirmation form submission, delete the post
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        // Prepare statement to delete the post (admins can delete any post)
        $stmt = $conn->prepare("DELETE FROM post_info WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        
        // Execute and check result
        if ($stmt->execute()) {
            // Success - redirect with success message
            $_SESSION['admin_message'] = "Post successfully deleted.";
            $_SESSION['admin_message_type'] = "success";
        } else {
            // Error - redirect with error message
            $_SESSION['admin_message'] = "Error deleting post: " . $stmt->error;
            $_SESSION['admin_message_type'] = "danger";
        }
        $stmt->close();
        $conn->close();
        
        // Redirect back to referring page
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "../pages/admin.php";
        header("Location: $referer");
        exit();
    }
    
    // Otherwise, show the confirmation page
    include "../inc/head.inc.php";
    include "../inc/login_nav.inc.php";
    ?>
   <!DOCTYPE html>
   <html lang="en"> 
    <!-- Delete Confirmation Page -->
    <main class="container mt-5">
        <h1 class="visually-hidden">Confirm Delete Post</h1>
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h2 class="mb-0">Confirm Delete</h2>
                    </div>
                    <div class="card-body">
                        <p>Are you sure you want to delete the post "<strong><?= $post_title ?></strong>"?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            You are deleting this post as an administrator.
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="post_id" value="<?= $post_id ?>">
                            <input type="hidden" name="confirm_delete" value="yes">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                <a href="../pages/admin.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include "../inc/footer.inc.php"; ?>
    </body>
    </html>

<?php
    exit();
} else {
    // Invalid request - redirect with error message
    $_SESSION['admin_message'] = "Invalid request.";
    $_SESSION['admin_message_type'] = "danger";
    header("Location: ../pages/admin.php");
    exit();
}
?>
