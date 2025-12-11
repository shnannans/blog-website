<?php
session_start();
require_once "../inc/db.inc.php";
require_once "../inc/check_session.inc.php";
require_once "../inc/post_functions.inc.php";

$post_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$post_id) {
    echo "Invalid post ID.";
    exit;
}

// Fetch post and ensure user owns it
$result = getPostByIdAndOwner($post_id, $user_id);

if ($result->num_rows === 0) {
    echo "Post not found or you don't have permission to edit this post.";
    exit;
}

$post = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title) || empty($content)) {
        $error_message = "Title and Content cannot be empty.";
    } else {
        global $conn;

        // Update query - now explicitly sets updated_at
        $update_stmt = $conn->prepare("
            UPDATE post_info 
            SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE post_id = ? AND owner_id = ?
        ");
        $update_stmt->bind_param("ssii", $title, $content, $post_id, $user_id);

        if ($update_stmt->execute()) {
            // Refresh the post data to get the updated timestamp
            $updated_result = getPostByIdAndOwner($post_id, $user_id);
            $post = $updated_result->fetch_assoc();
            
            header("Location: home_loggedin.php?msg=Post+updated+successfully");
            exit;
        } else {
            $error_message = "Failed to update post: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "../inc/head.inc.php"; ?>
    <title>Edit Post</title>
</head>
<body>
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <div class="container mt-5">
        <h2>Edit Post</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Display last updated time -->
        <p class="text-muted">
            Last updated: <?= !empty($post['updated_at']) ? htmlspecialchars($post['updated_at']) : htmlspecialchars($post['created_at']) ?>
        </p>
        
        <form method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea name="content" class="form-control" rows="8" required><?= htmlspecialchars($post['content']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Post</button>
            <a href="home_loggedin.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    
    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>