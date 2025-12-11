<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";


// Get post ID from URL
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid post ID
if ($post_id <= 0) {
    header("Location: home_loggedin.php");
    exit();
}

// Process notification if there's a notification ID in the URL
if (isset($_GET['notification']) && is_numeric($_GET['notification'])) {
    $notification_id = intval($_GET['notification']);
    
    // Check if the notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'user_notifications'");
    if ($table_check->num_rows > 0) {
        // Mark notification as read
        $mark_stmt = $conn->prepare("
            UPDATE user_notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?
        ");
        $mark_stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
        $mark_stmt->execute();
        $mark_stmt->close();
    }
}

// Create bookmarks table if it doesn't exist
$bookmark_check = $conn->query("SHOW TABLES LIKE 'bookmarks'");
$bookmarks_table_exists = ($bookmark_check->num_rows > 0);

if (!$bookmarks_table_exists) {
    $create_table_sql = "
        CREATE TABLE bookmarks (
            bookmark_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            post_id INT UNSIGNED NOT NULL,
            bookmarked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (bookmark_id),
            UNIQUE KEY user_post (user_id, post_id),
            CONSTRAINT fk_bookmarks_user 
                FOREIGN KEY (user_id) REFERENCES user_info (member_id) ON DELETE CASCADE,
            CONSTRAINT fk_bookmarks_post 
                FOREIGN KEY (post_id) REFERENCES post_info (post_id) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin
    ";
    
    $conn->query($create_table_sql);
    $bookmarks_table_exists = true;
}

// Create likes table if it doesn't exist
$likes_check = $conn->query("SHOW TABLES LIKE 'post_likes'");
$likes_table_exists = ($likes_check->num_rows > 0);

if (!$likes_table_exists) {
    $create_likes_table_sql = "
        CREATE TABLE post_likes (
            like_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            post_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (like_id),
            UNIQUE KEY user_post (user_id, post_id),
            CONSTRAINT fk_likes_user 
                FOREIGN KEY (user_id) REFERENCES user_info (member_id) ON DELETE CASCADE,
            CONSTRAINT fk_likes_post 
                FOREIGN KEY (post_id) REFERENCES post_info (post_id) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin
    ";
    
    $conn->query($create_likes_table_sql);
    $likes_table_exists = true;
}

// Create comments table if it doesn't exist
$comments_check = $conn->query("SHOW TABLES LIKE 'post_comments'");
$comments_table_exists = ($comments_check->num_rows > 0);

if (!$comments_table_exists) {
    $create_comments_table_sql = "
        CREATE TABLE post_comments (
            comment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            comment_text TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (comment_id),
            CONSTRAINT fk_comments_user 
                FOREIGN KEY (user_id) REFERENCES user_info (member_id) ON DELETE CASCADE,
            CONSTRAINT fk_comments_post 
                FOREIGN KEY (post_id) REFERENCES post_info (post_id) ON DELETE CASCADE
        ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin
    ";
    
    $conn->query($create_comments_table_sql);
    $comments_table_exists = true;
}


// Handle bookmark/unbookmark action
$bookmarkMessage = "";
if (isset($_POST['bookmark_action'])) {
    $current_user_id = $_SESSION['user_id'];
    
    if ($_POST['bookmark_action'] == 'bookmark') {
        // Check if already bookmarked
        $check_stmt = $conn->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND post_id = ?");
        $check_stmt->bind_param("ii", $current_user_id, $post_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            // Not bookmarked yet, so add bookmark
            $bookmark_stmt = $conn->prepare("INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)");
            $bookmark_stmt->bind_param("ii", $current_user_id, $post_id);
            
            if ($bookmark_stmt->execute()) {
                $bookmarkMessage = "Post added to your reading list.";
            } else {
                $bookmarkMessage = "Error bookmarking post: " . $conn->error;
            }
            $bookmark_stmt->close();
        }
        $check_stmt->close();
    } elseif ($_POST['bookmark_action'] == 'unbookmark') {
        // Remove bookmark
        $unbookmark_stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
        $unbookmark_stmt->bind_param("ii", $current_user_id, $post_id);
        
        if ($unbookmark_stmt->execute()) {
            $bookmarkMessage = "Post removed from your reading list.";
        } else {
            $bookmarkMessage = "Error removing bookmark: " . $conn->error;
        }
        $unbookmark_stmt->close();
    }
}

// Handle like/unlike action
$likeMessage = "";
if (isset($_POST['like_action'])) {
    $current_user_id = $_SESSION['user_id'];
    
    if ($_POST['like_action'] == 'like') {
        // Check if already liked
        $check_stmt = $conn->prepare("SELECT like_id FROM post_likes WHERE user_id = ? AND post_id = ?");
        $check_stmt->bind_param("ii", $current_user_id, $post_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            // Not liked yet, so add like
            $like_stmt = $conn->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)");
            $like_stmt->bind_param("ii", $current_user_id, $post_id);
            
            if ($like_stmt->execute()) {
                $likeMessage = "Post liked successfully.";
            } else {
                $likeMessage = "Error liking post: " . $conn->error;
            }
            $like_stmt->close();
        }
        $check_stmt->close();
    } elseif ($_POST['like_action'] == 'unlike') {
        // Remove like
        $unlike_stmt = $conn->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?");
        $unlike_stmt->bind_param("ii", $current_user_id, $post_id);
        
        if ($unlike_stmt->execute()) {
            $likeMessage = "Post unliked successfully.";
        } else {
            $likeMessage = "Error unliking post: " . $conn->error;
        }
        $unlike_stmt->close();
    }
}

// Handle comment submission
$commentMessage = "";
if (isset($_POST['submit_comment']) && isset($_POST['comment_text']) && !empty($_POST['comment_text'])) {
    $comment_text = trim($_POST['comment_text']);
    $current_user_id = $_SESSION['user_id'];
    
    // Basic validation
    if (strlen($comment_text) > 0 && strlen($comment_text) <= 1000) {
        $comment_stmt = $conn->prepare("INSERT INTO post_comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
        $comment_stmt->bind_param("iis", $post_id, $current_user_id, $comment_text);
        
        if ($comment_stmt->execute()) {
            $commentMessage = "Comment posted successfully.";
            // Clear the form after successful submission
            $_POST['comment_text'] = "";
        } else {
            $commentMessage = "Error posting comment: " . $conn->error;
        }
        $comment_stmt->close();
    } else {
        $commentMessage = "Comment must be between 1 and 1000 characters.";
    }
}

// Handle comment edit
if (isset($_POST['edit_comment']) && isset($_POST['comment_id']) && isset($_POST['edited_comment_text'])) {
    $comment_id = intval($_POST['comment_id']);
    $edited_text = trim($_POST['edited_comment_text']);
    $current_user_id = $_SESSION['user_id'];
    
    // Basic validation
    if (strlen($edited_text) > 0 && strlen($edited_text) <= 1000) {
        // Verify this user owns the comment
        $verify_stmt = $conn->prepare("SELECT comment_id FROM post_comments WHERE comment_id = ? AND user_id = ?");
        $verify_stmt->bind_param("ii", $comment_id, $current_user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // User owns this comment, proceed with update
            $update_stmt = $conn->prepare("UPDATE post_comments SET comment_text = ? WHERE comment_id = ?");
            $update_stmt->bind_param("si", $edited_text, $comment_id);
            
            if ($update_stmt->execute()) {
                $commentMessage = "Comment updated successfully.";
            } else {
                $commentMessage = "Error updating comment: " . $conn->error;
            }
            $update_stmt->close();
        } else {
            $commentMessage = "You don't have permission to edit this comment.";
        }
        $verify_stmt->close();
    } else {
        $commentMessage = "Comment must be between 1 and 1000 characters.";
    }
}

// Handle comment delete
if (isset($_POST['delete_comment']) && isset($_POST['comment_id'])) {
    $comment_id = intval($_POST['comment_id']);
    $current_user_id = $_SESSION['user_id'];
    
    // Check if user is admin or owns the comment
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    
    if ($is_admin) {
        // Admin can delete any comment
        $delete_stmt = $conn->prepare("DELETE FROM post_comments WHERE comment_id = ?");
        $delete_stmt->bind_param("i", $comment_id);
    } else {
        // Regular users can only delete their own comments
        $delete_stmt = $conn->prepare("DELETE FROM post_comments WHERE comment_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $comment_id, $current_user_id);
    }
    
    if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
        $commentMessage = "Comment deleted successfully.";
    } else {
        $commentMessage = "Error deleting comment: You may not have permission or the comment doesn't exist.";
    }
    $delete_stmt->close();
}


// Handle follow/unfollow action
$followMessage = "";
if (isset($_POST['follow_action']) && isset($_POST['author_id'])) {
    $author_id = intval($_POST['author_id']);
    $current_user_id = $_SESSION['user_id'];
    
    // Don't allow users to follow themselves
    if ($author_id != $current_user_id) {
        if ($_POST['follow_action'] == 'follow') {
            // Check if already following
            $check_stmt = $conn->prepare("SELECT follow_id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $check_stmt->bind_param("ii", $current_user_id, $author_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Not following yet, so add follow relationship
                $follow_stmt = $conn->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
                $follow_stmt->bind_param("ii", $current_user_id, $author_id);
                
                if ($follow_stmt->execute()) {
                    $followMessage = "You are now following this author.";
                } else {
                    $followMessage = "Error following author: " . $conn->error;
                }
                $follow_stmt->close();
            }
            $check_stmt->close();
        } elseif ($_POST['follow_action'] == 'unfollow') {
            // Remove follow relationship
            $unfollow_stmt = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $unfollow_stmt->bind_param("ii", $current_user_id, $author_id);
            
            if ($unfollow_stmt->execute()) {
                $followMessage = "You have unfollowed this author.";
            } else {
                $followMessage = "Error unfollowing author: " . $conn->error;
            }
            $unfollow_stmt->close();
        }
    }
}

// Fetch post details with author information
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        u.username, 
        u.fname, 
        u.lname,
        u.member_id as author_id,
        CHAR_LENGTH(p.content) / 1000 as read_time
    FROM post_info p 
    JOIN user_info u ON p.owner_id = u.member_id 
    WHERE p.post_id = ?
");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect if post not found
if (!$post) {
    header("Location: home_loggedin.php");
    exit();
}

// Check if current user is following the author
$isFollowing = false;
if ($post['owner_id'] != $_SESSION['user_id']) {
    $follow_check = $conn->prepare("SELECT follow_id FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $follow_check->bind_param("ii", $_SESSION['user_id'], $post['owner_id']);
    $follow_check->execute();
    $isFollowing = ($follow_check->get_result()->num_rows > 0);
    $follow_check->close();
}

// Check if post is bookmarked
$isBookmarked = false;
if ($bookmarks_table_exists) {
    $bookmark_check = $conn->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $bookmark_check->bind_param("ii", $_SESSION['user_id'], $post_id);
    $bookmark_check->execute();
    $isBookmarked = ($bookmark_check->get_result()->num_rows > 0);
    $bookmark_check->close();
}

// Check if post is liked by current user
$isLiked = false;
if ($likes_table_exists) {
    $like_check = $conn->prepare("SELECT like_id FROM post_likes WHERE user_id = ? AND post_id = ?");
    $like_check->bind_param("ii", $_SESSION['user_id'], $post_id);
    $like_check->execute();
    $isLiked = ($like_check->get_result()->num_rows > 0);
    $like_check->close();
}

// Get total likes count
$likes_count = 0;
if ($likes_table_exists) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?");
    $count_stmt->bind_param("i", $post_id);
    $count_stmt->execute();
    $result = $count_stmt->get_result();
    $row = $result->fetch_assoc();
    $likes_count = $row['like_count'];
    $count_stmt->close();
}

// Get comments for this post
$comments = [];
if ($comments_table_exists) {
    $comments_stmt = $conn->prepare("
        SELECT 
            c.*, 
            u.username, 
            u.fname, 
            u.lname,
            u.member_id as commenter_id
        FROM post_comments c
        JOIN user_info u ON c.user_id = u.member_id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");
    $comments_stmt->bind_param("i", $post_id);
    $comments_stmt->execute();
    $comments_result = $comments_stmt->get_result();
    
    while ($comment = $comments_result->fetch_assoc()) {
        $comments[] = $comment;
    }
    
    $comments_stmt->close();
}

// Format the creation date
$created_date = new DateTime($post['created_at']);
$formatted_date = $created_date->format('F j, Y');
$updated_date = new DateTime($post['updated_at']);
$updated_date = $updated_date->format('F j, Y');

// Calculate read time - minimum 1 minute, assume average reading speed of 200-250 words per minute
$read_time = max(1, ceil($post['read_time']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($post['title']) ?></title>
    <?php include "../inc/head.inc.php"; ?>
    <style>
        .post-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0;
        }
        .post-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .post-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .post-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 20px;
            color: #6c757d;
            gap: 15px;
        }
        .post-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .post-author-name {
            font-weight: bold;
            color: #212529;
            text-decoration: none;
        }
        .post-author-name:hover {
            text-decoration: underline;
        }
        .post-stats {
            font-size: 0.9rem;
            color: #5c636a;
        }
        .post-content {
            padding: 30px;
            line-height: 1.8;
            font-size: 1.1rem;
        }
        .post-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
        }
        .follow-btn {
            padding: 3px 10px;
            font-size: 0.8rem;
        }
        .bookmark-btn {
            padding: 5px 15px;
        }
        .alert-float {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            max-width: 300px;
        }
        .post-divider {
            color: #6c757d;
            margin: 0 5px;
        }

        .post-action-bar {
    display: flex;
    align-items: center;
    padding: 10px 0;
}
.like-btn {
    transition: all 0.3s ease;
}
.like-btn.liked {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
}
.like-btn.liked:hover {
    background-color: #c82333;
    border-color: #bd2130;
}
.comments-section {
    margin-top: 40px;
}
.comment .card-body {
    padding: 15px;
}
.comment .card-subtitle {
    margin-bottom: 0.25rem;
}
.comment-text {
    white-space: pre-line;
    word-break: break-word;
}
.comment-actions {
    display: flex;
    gap: 10px;
}
.edit-comment-form {
    margin-top: 10px;
}
.comment-text {
    margin-bottom: 10px;
}
@media (max-width: 768px) {
    .comment .card-body {
        padding: 10px;
    }
    .comment .d-flex {
        flex-direction: column;
    }
    .comment .d-flex small {
        margin-top: 5px;
    }
}
    </style>
</head>
<body>
    <?php 
    include "../inc/login_nav.inc.php";
    ?>
    
    <?php if (!empty($followMessage)): ?>
        <div class="alert alert-info alert-dismissible fade show alert-float" role="alert">
            <?= htmlspecialchars($followMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($bookmarkMessage)): ?>
        <div class="alert alert-info alert-dismissible fade show alert-float" role="alert">
            <?= htmlspecialchars($bookmarkMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($likeMessage)): ?>
        <div class="alert alert-info alert-dismissible fade show alert-float" role="alert">
            <?= htmlspecialchars($likeMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
     </div>
    <?php endif; ?>
    
    <main class="container">
        <div class="post-container">
            <div class="post-header">
                <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
                
                <div class="post-meta">
                    <div class="post-author">
                        <a href="author_profile.php?id=<?= $post['author_id'] ?>" class="post-author-name">
                            <?= htmlspecialchars($post['fname'] . ' ' . $post['lname']) ?>
                        </a>
                        
                        <?php if ($post['owner_id'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="author_id" value="<?= $post['owner_id'] ?>">
                                <?php if ($isFollowing): ?>
                                    <input type="hidden" name="follow_action" value="unfollow">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm follow-btn">
                                        Following
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="follow_action" value="follow">
                                    <button type="submit" class="btn btn-primary btn-sm follow-btn">
                                        Follow
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-stats">
                        <span><?= $read_time ?> min read</span> • 
                        <span>Published <?= $formatted_date ?></span>
                        <?php if ($post['created_at'] != $post['updated_at']): ?>
                            • <span>Updated <?= $updated_date ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="post-content">
                <?= nl2br(htmlspecialchars($translatedContent ?? $post['content'])) ?>
            </div>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h2 class="h5 mb-0">Admin Actions</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">As an admin, you have additional actions available:</p>
                        <button type="button" class="btn btn-danger" 
                                data-bs-toggle="modal" data-bs-target="#adminDeletePostModal">
                            <i class="bi bi-trash"></i> Delete Post
                        </button>
                    </div>
                </div>

                <!-- Admin Delete Post Modal -->
                <div class="modal fade" id="adminDeletePostModal" tabindex="-1" aria-labelledby="adminDeletePostModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="h5 modal-title" id="adminDeletePostModalLabel">Confirm Delete</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this post?</p>
                                <p class="text-danger">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <form action="../process/delete_post.php" method="post">
                                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <!-- Like Section -->
<div class="post-action-bar border-top border-bottom py-3 my-4">
    <form method="POST" action="" class="d-inline">
        <?php if ($isLiked): ?>
            <input type="hidden" name="like_action" value="unlike">
            <button type="submit" class="btn btn-sm btn-danger like-btn liked">
                <i class="fas fa-heart"></i> Unlike
            </button>
        <?php else: ?>
            <input type="hidden" name="like_action" value="like">
            <button type="submit" class="btn btn-sm btn-outline-danger like-btn">
                <i class="far fa-heart"></i> Like
            </button>
        <?php endif; ?>
    </form>
    <span class="ms-2"><?= $likes_count ?> <?= $likes_count == 1 ? 'like' : 'likes' ?></span>
</div>

<!-- Comments Section -->
<div class="comments-section mt-5">
    <h3 class="h4 mb-4">Comments (<?= count($comments) ?>)</h3>
    
    <?php if (!empty($commentMessage)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($commentMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Comment Form -->
    <form method="POST" action="" class="mb-4">
        <div class="mb-3">
            <label for="comment_text" class="form-label">Add a comment</label>
            <textarea class="form-control" id="comment_text" name="comment_text" rows="3" required><?= htmlspecialchars($_POST['comment_text'] ?? '') ?></textarea>
        </div>
        <button type="submit" name="submit_comment" class="btn btn-primary">Post Comment</button>
    </form>
    
  <!-- Comments List -->
<div class="comments-list">
    <?php if (empty($comments)): ?>
        <p class="text-muted">No comments yet. Be the first to comment!</p>
    <?php else: ?>
        <?php foreach ($comments as $comment): ?>
            <div class="comment card mb-3" id="comment-<?= $comment['comment_id'] ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h3 class="h6 card-subtitle mb-2">
                            <a href="author_profile.php?id=<?= $comment['commenter_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($comment['fname'] . ' ' . $comment['lname']) ?>
                            </a>
                        </h3>
                        <small class="text-muted">
                        <?php 
                            $commentDate = new DateTime($comment['created_at']);
                            $commentDate->setTimezone(new DateTimeZone('Asia/Singapore'));
                            echo $commentDate->format('M j, Y, g:i a');
                        ?>
                        </small>
                    </div>
                    
                    <!-- Comment content (hidden when editing) -->
                    <div class="comment-content">
                        <p class="card-text mt-2 comment-text"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                        
                        <?php if ($comment['user_id'] == $_SESSION['user_id'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1)): ?>
                            <div class="comment-actions mt-2">
                                <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-comment-btn" 
                                            data-comment-id="<?= $comment['comment_id'] ?>">
                                        Edit
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-sm btn-outline-danger delete-comment-btn" 
                                        data-comment-id="<?= $comment['comment_id'] ?>"
                                        data-bs-toggle="modal" data-bs-target="#deleteCommentModal<?= $comment['comment_id'] ?>">
                                    Delete
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Edit form (hidden by default) -->
                    <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                        <div class="edit-comment-form" style="display: none;">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <textarea class="form-control" name="edited_comment_text" rows="3" required><?= htmlspecialchars($comment['comment_text']) ?></textarea>
                                </div>
                                <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                <button type="submit" name="edit_comment" class="btn btn-primary btn-sm">Save Changes</button>
                                <button type="button" class="btn btn-secondary btn-sm cancel-edit-btn">Cancel</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteCommentModal<?= $comment['comment_id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 class="h5 modal-title">Delete Comment</h3>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <form method="POST" action="">
                                        <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                        <button type="submit" name="delete_comment" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
            
            <section class="post-footer" aria-label="Change Language">
                <form method="POST" action="">
                    <?php if ($isBookmarked): ?>
                        <input type="hidden" name="bookmark_action" value="unbookmark">
                        <button type="submit" class="btn btn-outline-secondary bookmark-btn">
                            <i class="fas fa-bookmark"></i> Saved to Library
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="bookmark_action" value="bookmark">
                        <button type="submit" class="btn btn-outline-primary bookmark-btn">
                            <i class="far fa-bookmark"></i> Save to Library
                        </button>
                    <?php endif; ?>
                </form>
            </section>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit comment buttons
    document.querySelectorAll('.edit-comment-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const commentCard = document.getElementById('comment-' + commentId);
            
            // Hide comment content, show edit form
            commentCard.querySelector('.comment-content').style.display = 'none';
            commentCard.querySelector('.edit-comment-form').style.display = 'block';
        });
    });
    
    // Cancel edit buttons
    document.querySelectorAll('.cancel-edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const commentForm = this.closest('.edit-comment-form');
            const commentContent = commentForm.previousElementSibling;
            
            // Hide edit form, show comment content
            commentForm.style.display = 'none';
            commentContent.style.display = 'block';
        });
    });
});
</script>
    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>
