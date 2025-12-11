<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";

// Get author ID from URL
$author_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid author ID
if ($author_id <= 0) {
    header("Location: home_loggedin.php");
    exit();
}

// First, check if the user_follows table exists
$table_check = $conn->query("SHOW TABLES LIKE 'user_follows'");
$user_follows_exists = ($table_check->num_rows > 0);

// Also check if user_subscriptions exists
$sub_table_check = $conn->query("SHOW TABLES LIKE 'user_subscriptions'");
$user_subscriptions_exists = ($sub_table_check->num_rows > 0);

// Handle follow/unfollow action - only if table exists
$actionMessage = "";
if (isset($_POST['follow_action']) && $user_follows_exists) {
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
                    $actionMessage = "You are now following this author.";
                } else {
                    $actionMessage = "Error following author: " . $conn->error;
                }
                $follow_stmt->close();
            }
            $check_stmt->close();
        } elseif ($_POST['follow_action'] == 'unfollow') {
            // Remove follow relationship
            $unfollow_stmt = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $unfollow_stmt->bind_param("ii", $current_user_id, $author_id);
            
            if ($unfollow_stmt->execute()) {
                $actionMessage = "You have unfollowed this author.";
            } else {
                $actionMessage = "Error unfollowing author: " . $conn->error;
            }
            $unfollow_stmt->close();
        }
    }
}

// Handle subscribe/unsubscribe action - conditional on user_subscriptions existing
if (isset($_POST['subscribe_action'])) {
    $current_user_id = $_SESSION['user_id'];
    
    // Only proceed if we need to create the table or if it already exists
    if (!$user_subscriptions_exists) {
        // Create the table
        $create_table_sql = "
            CREATE TABLE user_subscriptions (
                subscription_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                subscriber_id INT UNSIGNED NOT NULL,
                author_id INT UNSIGNED NOT NULL,
                subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (subscription_id),
                UNIQUE KEY unique_subscription (subscriber_id, author_id),
                CONSTRAINT fk_subscriptions_subscriber
                    FOREIGN KEY (subscriber_id) REFERENCES user_info (member_id) ON DELETE CASCADE,
                CONSTRAINT fk_subscriptions_author
                    FOREIGN KEY (author_id) REFERENCES user_info (member_id) ON DELETE CASCADE
            ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin
        ";
        
        $table_created = $conn->query($create_table_sql);
        if (!$table_created) {
            $actionMessage = "Error creating subscription table: " . $conn->error;
        } else {
            $user_subscriptions_exists = true;
        }
    }
    
    // Only proceed with subscription actions if the table exists
    if ($user_subscriptions_exists) {
        // Don't allow users to subscribe to themselves
        if ($author_id != $current_user_id) {
            if ($_POST['subscribe_action'] == 'subscribe') {
                // Check if already subscribed
                $check_stmt = $conn->prepare("SELECT subscription_id FROM user_subscriptions WHERE subscriber_id = ? AND author_id = ?");
                $check_stmt->bind_param("ii", $current_user_id, $author_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows == 0) {
                    // Not subscribed yet, so add subscription
                    $subscribe_stmt = $conn->prepare("INSERT INTO user_subscriptions (subscriber_id, author_id) VALUES (?, ?)");
                    $subscribe_stmt->bind_param("ii", $current_user_id, $author_id);
                    
                    if ($subscribe_stmt->execute()) {
                        $actionMessage = "You are now subscribed to receive notifications from this author.";
                    } else {
                        $actionMessage = "Error subscribing: " . $conn->error;
                    }
                    $subscribe_stmt->close();
                }
                $check_stmt->close();
            } elseif ($_POST['subscribe_action'] == 'unsubscribe') {
                // Remove subscription
                $unsubscribe_stmt = $conn->prepare("DELETE FROM user_subscriptions WHERE subscriber_id = ? AND author_id = ?");
                $unsubscribe_stmt->bind_param("ii", $current_user_id, $author_id);
                
                if ($unsubscribe_stmt->execute()) {
                    $actionMessage = "You have unsubscribed from this author's notifications.";
                } else {
                    $actionMessage = "Error unsubscribing: " . $conn->error;
                }
                $unsubscribe_stmt->close();
            }
        }
    }
}

// Start with basic author information without complex queries
try {
    // Simple query to get author information
    $basic_stmt = $conn->prepare("
        SELECT 
            member_id, username, fname, lname, about_me, profile_pic
        FROM user_info
        WHERE member_id = ?
    ");
    $basic_stmt->bind_param("i", $author_id);
    $basic_stmt->execute();
    $author = $basic_stmt->get_result()->fetch_assoc();
    $basic_stmt->close();

    // If we have the basic author info, add additional stats conditionally
    if ($author) {
        // Add post count
        $post_count_stmt = $conn->prepare("SELECT COUNT(*) AS posts_count FROM post_info WHERE owner_id = ?");
        $post_count_stmt->bind_param("i", $author_id);
        $post_count_stmt->execute();
        $post_count_result = $post_count_stmt->get_result()->fetch_assoc();
        $author['posts_count'] = $post_count_result['posts_count'];
        $post_count_stmt->close();
        
        // Add follower and following counts if that table exists
        if ($user_follows_exists) {
            // Follower count
            $follower_count_stmt = $conn->prepare("SELECT COUNT(*) AS followers_count FROM user_follows WHERE following_id = ?");
            $follower_count_stmt->bind_param("i", $author_id);
            $follower_count_stmt->execute();
            $follower_count_result = $follower_count_stmt->get_result()->fetch_assoc();
            $author['followers_count'] = $follower_count_result['followers_count'];
            $follower_count_stmt->close();
            
            // Following count
            $following_count_stmt = $conn->prepare("SELECT COUNT(*) AS following_count FROM user_follows WHERE follower_id = ?");
            $following_count_stmt->bind_param("i", $author_id);
            $following_count_stmt->execute();
            $following_count_result = $following_count_stmt->get_result()->fetch_assoc();
            $author['following_count'] = $following_count_result['following_count'];
            $following_count_stmt->close();
            
            // Check if current user is following this author
            $is_following_stmt = $conn->prepare("SELECT follow_id FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $is_following_stmt->bind_param("ii", $_SESSION['user_id'], $author_id);
            $is_following_stmt->execute();
            $author['is_following'] = ($is_following_stmt->get_result()->num_rows > 0);
            $is_following_stmt->close();
        } else {
            // Default values if table doesn't exist
            $author['followers_count'] = 0;
            $author['following_count'] = 0;
            $author['is_following'] = false;
        }
        
        // Check if current user is subscribed if subscription table exists
        if ($user_subscriptions_exists) {
            $is_subscribed_stmt = $conn->prepare("SELECT subscription_id FROM user_subscriptions WHERE subscriber_id = ? AND author_id = ?");
            $is_subscribed_stmt->bind_param("ii", $_SESSION['user_id'], $author_id);
            $is_subscribed_stmt->execute();
            $author['is_subscribed'] = ($is_subscribed_stmt->get_result()->num_rows > 0);
            $is_subscribed_stmt->close();
        } else {
            $author['is_subscribed'] = false;
        }
    }
} catch (Exception $e) {
    echo "Error fetching author information: " . $e->getMessage();
    exit;
}

// Redirect if author not found
if (!$author) {
    header("Location: home_loggedin.php");
    exit();
}

// Get author's posts
try {
    $posts_stmt = $conn->prepare("
        SELECT 
            post_id, title, content, created_at,
            CHAR_LENGTH(content) / 1000 as read_time
        FROM post_info 
        WHERE owner_id = ? 
        ORDER BY created_at DESC
    ");
    $posts_stmt->bind_param("i", $author_id);
    $posts_stmt->execute();
    $posts = $posts_stmt->get_result();
    $posts_stmt->close();
} catch (Exception $e) {
    echo "Error fetching posts: " . $e->getMessage();
    exit;
}

// Default profile picture if none exists
$profile_pic = !empty($author['profile_pic']) ? $author['profile_pic'] : "image/default_pfp.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($author['fname'] . ' ' . $author['lname']) ?> - Author Profile</title>
    <?php include "../inc/head.inc.php"; ?>
    <style>
        .profile-header {
            background-color: #f9f9f9;
            padding: 40px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        .profile-name {
            font-size: 2rem;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .profile-stats {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            color: #6c757d;
            justify-content: center;
        }
        .profile-stat span {
            font-weight: bold;
            color: #212529;
        }
        .profile-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .profile-bio {
            margin: 20px 0;
            white-space: pre-wrap;
            max-width: 700px;
        }
        .posts-section {
            margin: 40px 0;
        }
        .posts-heading {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .post-item {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e9ecef;
        }
        .post-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #212529;
            text-decoration: none;
        }
        .post-title:hover {
            text-decoration: underline;
        }
        .post-meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .post-excerpt {
            margin-top: 10px;
            color: #6c757d;
        }
        .alert-float {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            max-width: 300px;
        }
        .badge {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <?php if (!empty($actionMessage)): ?>
        <div class="alert alert-info alert-dismissible fade show alert-float" role="alert">
            <?= htmlspecialchars($actionMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Profile Header -->
    <section class="profile-header">
        <div class="profile-container text-center">
            <img src="../<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture" class="profile-image">
            
            <h1 class="profile-name">
                <?= htmlspecialchars($author['fname'] . ' ' . $author['lname']) ?>
                <?php if (isset($author['is_following']) && $author['is_following']): ?>
                    <span class="badge bg-secondary">Following</span>
                <?php endif; ?>
                <?php if (isset($author['is_subscribed']) && $author['is_subscribed']): ?>
                    <span class="badge bg-success">Subscribed</span>
                <?php endif; ?>
            </h1>
            
            <p class="text-muted">@<?= htmlspecialchars($author['username']) ?></p>
            
            <div class="profile-stats">
                <div class="profile-stat">
                    <span><?= $author['posts_count'] ?? 0 ?></span> Posts
                </div>
                <?php if ($user_follows_exists): ?>
                <div class="profile-stat">
                    <a href="followers.php?user_id=<?= $author['member_id'] ?>&tab=followers" class="text-decoration-none text-muted">
                        <span><?= $author['followers_count'] ?></span> Followers
                    </a>
                </div>
                <div class="profile-stat">
                    <a href="followers.php?user_id=<?= $author['member_id'] ?>&tab=following" class="text-decoration-none text-muted">
                        <span><?= $author['following_count'] ?></span> Following
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($author['member_id'] != $_SESSION['user_id']): ?>
                <div class="profile-buttons">
                    <!-- Follow/Unfollow Button -->
                    <?php if ($user_follows_exists): ?>
                    <form method="POST" action="" class="d-inline">
                        <?php if (isset($author['is_following']) && $author['is_following']): ?>
                            <input type="hidden" name="follow_action" value="unfollow">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-user-minus"></i> Unfollow
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="follow_action" value="follow">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Follow
                            </button>
                        <?php endif; ?>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Subscribe/Unsubscribe Button -->
                    <form method="POST" action="" class="d-inline">
                        <?php if (isset($author['is_subscribed']) && $author['is_subscribed']): ?>
                            <input type="hidden" name="subscribe_action" value="unsubscribe">
                            <button type="submit" class="btn btn-outline-success">
                                <i class="fas fa-bell-slash"></i> Unsubscribe
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="subscribe_action" value="subscribe">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-bell"></i> Subscribe
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($author['about_me'])): ?>
                <div class="profile-bio text-center mx-auto">
                    <?= nl2br(htmlspecialchars($author['about_me'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Posts Section -->
    <section class="posts-section">
        <div class="profile-container">
            <div class="posts-heading">
                <h2>Posts</h2>
                <?php if ($posts->num_rows > 0): ?>
                    <span class="text-muted"><?= $posts->num_rows ?> articles</span>
                <?php endif; ?>
            </div>
            
            <?php if ($posts->num_rows > 0): ?>
                <?php while ($post = $posts->fetch_assoc()): 
                    // Calculate read time - minimum 1 minute
                    $read_time = max(1, ceil($post['read_time']));
                    
                    // Format the creation date
                    $created_date = new DateTime($post['created_at']);
                    $formatted_date = $created_date->format('M j, Y');
                    
                    // Get post excerpt (first 200 characters)
                    $excerpt = substr(strip_tags($post['content']), 0, 200) . (strlen($post['content']) > 200 ? '...' : '');
                ?>
                    <div class="post-item">
                        <a href="view_post.php?id=<?= $post['post_id'] ?>" class="post-title">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                        <div class="post-meta">
                            <span><?= $read_time ?> min read</span> • 
                            <span>Published <?= $formatted_date ?></span>
                        </div>
                        <p class="post-excerpt">
                            <?= htmlspecialchars($excerpt) ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-muted">This author hasn't published any posts yet.</p>
            <?php endif; ?>
        </div>
    </section>
    
    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>