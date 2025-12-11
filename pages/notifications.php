<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";

// Check if the notifications table exists
$table_check = $conn->query("SHOW TABLES LIKE 'user_notifications'");
$notifications_table_exists = ($table_check->num_rows > 0);

// Get all notifications for the current user
$notifications = [];
if ($notifications_table_exists) {
    $stmt = $conn->prepare("
        SELECT 
            n.notification_id,
            n.author_id,
            n.post_id,
            n.message,
            n.created_at,
            n.is_read,
            u.fname,
            u.lname,
            u.username,
            u.profile_pic,
            p.title as post_title
        FROM user_notifications n
        JOIN user_info u ON n.author_id = u.member_id
        JOIN post_info p ON n.post_id = p.post_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($notification = $result->fetch_assoc()) {
        $notifications[] = $notification;
    }
    $stmt->close();
    
    // Mark all as read when visiting this page
    $update_stmt = $conn->prepare("
        UPDATE user_notifications 
        SET is_read = 1 
        WHERE user_id = ?
    ");
    $update_stmt->bind_param("i", $_SESSION['user_id']);
    $update_stmt->execute();
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Notifications</title>
    <?php include "../inc/head.inc.php"; ?>
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .notification-content {
            flex-grow: 1;
        }
        .notification-meta {
            display: flex;
            justify-content: space-between;
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notification-message {
            margin-bottom: 5px;
        }
        .notification-link {
            color: inherit;
            text-decoration: none;
        }
        .notification-link:hover {
            text-decoration: none;
        }
        .empty-notifications {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
        .empty-notifications i {
            font-size: 3em;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <div class="container notifications-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Notifications</h1>
            <?php if (!empty($notifications)): ?>
                <a href="../process/clear_notifications.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-trash"></i> Clear All
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-notifications">
                <i class="fas fa-bell-slash"></i>
                <h4>No notifications yet</h4>
                <p>
                    When authors you subscribe to publish new content, you'll see their updates here.
                    <br>
                    <a href="../pages/home_loggedin.php">Browse content</a> to find authors to follow.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <?php $profile_pic = !empty($notification['profile_pic']) ? $notification['profile_pic'] : "image/default_pfp.jpg"; ?>
                
                <a href="../pages/view_post.php?id=<?= $notification['post_id'] ?>" class="notification-link">
                    <div class="notification-item">
                        <img src="../<?= $profile_pic ?>" alt="Avatar" class="notification-avatar">
                        
                        <div class="notification-content">
                            <div class="notification-meta">
                                <span>@<?= htmlspecialchars($notification['username']) ?></span>
                                <span><?= formatDate($notification['created_at']) ?></span>
                            </div>
                            
                            <div class="notification-title">
                                <?= htmlspecialchars($notification['fname'] . ' ' . $notification['lname']) ?> published a new post
                            </div>
                            
                            <div class="notification-message">
                                "<?= htmlspecialchars($notification['post_title']) ?>"
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>

<?php
// Helper function to format date
function formatDate($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 86400) { // Less than 24 hours
        return timeAgo($datetime);
    } else {
        return date("M j, Y", $timestamp);
    }
}

// Helper function to format time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("M j, Y", $timestamp);
    }
}
?>