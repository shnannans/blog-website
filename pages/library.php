<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";

// Check if bookmarks table exists
$table_check = $conn->query("SHOW TABLES LIKE 'bookmarks'");
$bookmarks_table_exists = ($table_check->num_rows > 0);

// Create bookmarks table if it doesn't exist
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

// Handle bookmark removal if requested
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $post_id = intval($_GET['remove']);
    $user_id = $_SESSION['user_id'];
    
    $remove_stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $remove_stmt->bind_param("ii", $user_id, $post_id);
    $remove_stmt->execute();
    $remove_stmt->close();
    
    // Redirect to remove the query parameter
    header("Location: library.php");
    exit();
}

// Get all bookmarked posts
$bookmarks = [];
if ($bookmarks_table_exists) {
    $bookmark_stmt = $conn->prepare("
        SELECT 
            b.bookmark_id,
            b.bookmarked_at,
            p.post_id,
            p.title,
            p.content,
            p.created_at,
            u.member_id as author_id,
            u.fname,
            u.lname,
            u.username,
            u.profile_pic,
            CHAR_LENGTH(p.content) / 1000 as read_time
        FROM bookmarks b
        JOIN post_info p ON b.post_id = p.post_id
        JOIN user_info u ON p.owner_id = u.member_id
        WHERE b.user_id = ?
        ORDER BY b.bookmarked_at DESC
    ");
    
    $bookmark_stmt->bind_param("i", $_SESSION['user_id']);
    $bookmark_stmt->execute();
    $result = $bookmark_stmt->get_result();
    
    while ($bookmark = $result->fetch_assoc()) {
        $bookmarks[] = $bookmark;
    }
    
    $bookmark_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Library</title>
    <?php include "../inc/head.inc.php"; ?>
    <style>
        .library-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .library-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bookmark-item {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e9ecef;
            position: relative;
        }
        .bookmark-item:last-child {
            border-bottom: none;
        }
        .bookmark-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #212529;
            text-decoration: none;
        }
        .bookmark-title:hover {
            text-decoration: underline;
        }
        .bookmark-meta {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .bookmark-author {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .bookmark-author-image {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
        .bookmark-author-name {
            color: #212529;
            text-decoration: none;
            font-weight: 500;
        }
        .bookmark-author-name:hover {
            text-decoration: underline;
        }
        .bookmark-stats {
            margin-left: auto;
        }
        .bookmark-excerpt {
            margin-top: 10px;
            color: #495057;
        }
        .bookmark-actions {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
        }
        .bookmark-date {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .bookmark-remove {
            color: #dc3545;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .bookmark-remove:hover {
            text-decoration: underline;
        }
        .empty-library {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
        .empty-library i {
            font-size: 3em;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <main class="container library-container">
        <div class="library-header">
            <h1>My Reading List</h1>
            <span class="text-muted"><?= count($bookmarks) ?> saved posts</span>
        </div>
        
        <?php if (empty($bookmarks)): ?>
            <div class="empty-library">
                <i class="fas fa-bookmark"></i>
                <h2 class="h4">Your reading list is empty</h2>
                <p>
                    Bookmark posts to save them for later reading.
                    <br>
                    <a href="../pages/home_loggedin.php">Browse content</a> to find posts to bookmark.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($bookmarks as $bookmark): 
                // Profile picture
                $profile_pic = !empty($bookmark['profile_pic']) ? $bookmark['profile_pic'] : "image/default_pfp.jpg";
                
                // Format dates
                $created_date = new DateTime($bookmark['created_at']);
                $formatted_date = $created_date->format('M j, Y');
                
                $bookmarked_date = new DateTime($bookmark['bookmarked_at']);
                $formatted_bookmark_date = $bookmarked_date->format('M j, Y');
                
                // Calculate read time
                $read_time = max(1, ceil($bookmark['read_time']));
                
                // Get excerpt (first 200 characters)
                $excerpt = substr(strip_tags($bookmark['content']), 0, 200);
                if (strlen($bookmark['content']) > 200) {
                    $excerpt .= '...';
                }
            ?>
                <div class="bookmark-item">
                    <a href="view_post.php?id=<?= $bookmark['post_id'] ?>" class="bookmark-title">
                        <?= htmlspecialchars($bookmark['title']) ?>
                    </a>
                    
                    <div class="bookmark-meta">
                        <div class="bookmark-author">
                            <img src="../<?= htmlspecialchars($profile_pic) ?>" alt="Author" class="bookmark-author-image">
                            <a href="author_profile.php?id=<?= $bookmark['author_id'] ?>" class="bookmark-author-name">
                                <?= htmlspecialchars($bookmark['fname'] . ' ' . $bookmark['lname']) ?>
                            </a>
                        </div>
                        
                        <div class="bookmark-stats">
                            <span><?= $read_time ?> min read</span> • 
                            <span>Published <?= $formatted_date ?></span>
                        </div>
                    </div>
                    
                    <div class="bookmark-excerpt">
                        <?= htmlspecialchars($excerpt) ?>
                    </div>
                    
                    <div class="bookmark-actions">
                        <span class="bookmark-date">
                            <i class="fas fa-bookmark"></i> Saved on <?= $formatted_bookmark_date ?>
                        </span>
                        
                        <a href="library.php?remove=<?= $bookmark['post_id'] ?>" class="bookmark-remove" 
                           onclick="return confirm('Remove this post from your reading list?')">
                            <i class="fas fa-times"></i> Remove
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>