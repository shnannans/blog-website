<?php
// Get unread notifications count
$unread_notifications = 0;
$notifications = [];

// Check if the notifications table exists
$table_check = $conn->query("SHOW TABLES LIKE 'user_notifications'");
$notifications_table_exists = ($table_check->num_rows > 0);

if ($notifications_table_exists) {
    // Get unread notifications count
    $notification_stmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM user_notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $notification_stmt->bind_param("i", $_SESSION['user_id']);
    $notification_stmt->execute();
    $result = $notification_stmt->get_result();
    $unread_notifications = $result->fetch_assoc()['unread_count'];
    $notification_stmt->close();
    
    // Get latest 5 notifications
    $latest_stmt = $conn->prepare("
        SELECT 
            n.notification_id,
            n.author_id,
            n.post_id,
            n.message,
            n.created_at,
            n.is_read,
            u.fname,
            u.lname,
            u.profile_pic,
            p.title as post_title
        FROM user_notifications n
        JOIN user_info u ON n.author_id = u.member_id
        JOIN post_info p ON n.post_id = p.post_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $latest_stmt->bind_param("i", $_SESSION['user_id']);
    $latest_stmt->execute();
    $notifications_result = $latest_stmt->get_result();
    
    while ($notification = $notifications_result->fetch_assoc()) {
        $notifications[] = $notification;
    }
    $latest_stmt->close();
}

// Count bookmarks
$bookmark_count = 0;
$bookmark_check = $conn->query("SHOW TABLES LIKE 'bookmarks'");
$bookmarks_table_exists = ($bookmark_check->num_rows > 0);

if ($bookmarks_table_exists) {
    $bookmark_stmt = $conn->prepare("
        SELECT COUNT(*) as bookmark_count 
        FROM bookmarks 
        WHERE user_id = ?
    ");
    $bookmark_stmt->bind_param("i", $_SESSION['user_id']);
    $bookmark_stmt->execute();
    $result = $bookmark_stmt->get_result();
    $bookmark_count = $result->fetch_assoc()['bookmark_count'];
    $bookmark_stmt->close();
}
?>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a id="logo" class="navbar-brand" href="../pages/home_loggedin.php">Blooger.</a>

            
        <!-- Search Bar (Visible on large screens) -->
        <div class="d-none d-lg-flex input-group rounded w-25">
            <input type="search" id="search-input" class="form-control rounded" placeholder="Search" aria-label="Search" aria-describedby="search-addon" />
            <a href="../pages/home_loggedin.php" class="input-group-text border-0 bg-dark" id="search-icon">
                <i class="fas fa-search text-light"></i>
            </a>
        </div>

        <!-- Hamburger Button for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#mobilemenu" aria-controls="mobilemenu" 
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible Menu -->
        <div class="collapse navbar-collapse justify-content-end text-center" id="mobilemenu">
            
            <!-- Search Bar (Visible in mobile menu) -->
            <div class="d-lg-none input-group rounded w-100 my-3">
                <input type="search" id="search-input-mobile" class="form-control rounded" placeholder="Search" aria-label="Search" aria-describedby="search-addon-mobile" />
                <a href="../pages/home_loggedin.php" class="input-group-text border-0 bg-light" id="search-icon-mobile">
                    <i class="fas fa-search text-dark"></i>
                </a>
            </div>

            <!-- Write Post Button -->
            <a class="nav-link text-light d-flex align-items-center px-2 py-2 cursor-pointer" data-bs-toggle="modal" data-bs-target="#createPostModal">
                <span class="material-symbols-outlined d-none d-lg-inline">contract_edit</span>
                <span class="d-inline d-lg-none">Write</span> <!-- Text when collapsed -->
            </a>
            
            <!-- Notifications Bell -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-light d-flex align-items-center px-2 py-2"
                    href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="position-relative">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unread_notifications > 9 ? '9+' : $unread_notifications ?>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
                
                <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationsDropdown" style="width: 300px; max-height: 400px; overflow-y: auto;">
                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                        <h6 class="mb-0">Notifications</h6>
                        <?php if ($unread_notifications > 0): ?>
                            <a href="../process/mark_notifications_read.php" class="text-primary small">Mark all as read</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($notifications)): ?>
                        <div class="p-3 text-center text-muted">
                            <p>No notifications yet</p>
                            <small>Subscribe to authors to receive notifications when they publish new posts.</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <a href="../pages/view_post.php?id=<?= $notification['post_id'] ?>&notification=<?= $notification['notification_id'] ?>" 
                               class="dropdown-item p-2 border-bottom <?= $notification['is_read'] ? '' : 'bg-light' ?>">
                                <div class="d-flex">
                                    <?php $profile_pic = !empty($notification['profile_pic']) ? $notification['profile_pic'] : "image/default_pfp.jpg"; ?>
                                    <img src="../<?= $profile_pic ?>" alt="Profile" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <div class="small text-muted">
                                            <?= timeAgo($notification['created_at']) ?>
                                        </div>
                                        <div class="mb-1">
                                            <strong><?= htmlspecialchars($notification['fname'] . ' ' . $notification['lname']) ?></strong> 
                                            published a new post
                                        </div>
                                        <div class="text-truncate small">
                                            <?= htmlspecialchars($notification['post_title']) ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        
                        <?php if (count($notifications) >= 5): ?>
                            <div class="p-2 text-center">
                                <a href="../pages/notifications.php" class="small text-primary">View all notifications</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-light d-flex align-items-center px-2 py-2"
                    href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php 
                    // Get profile pic path - default if not set
                    $profile_image = isset($_SESSION['profile_pic']) 
                                     ? "../" . htmlspecialchars($_SESSION['profile_pic']) 
                                     : "../image/default_pfp.jpg";
                    ?>
                    <div style="width: 30px; height: 30px; border-radius: 50%; overflow: hidden; margin-right: 5px;">
                        <img src="<?php echo $profile_image; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <span class="d-inline d-lg-none ms-2">Profile</span> <!-- Text when collapsed -->
                </a>
                
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="/pages/profile.php">My Profile</a></li>
                    <li>
                        <a class="dropdown-item d-flex justify-content-between align-items-center" href="/pages/library.php">
                            Library
                            <?php if ($bookmark_count > 0): ?>
                                <span class="badge bg-primary rounded-pill"><?= $bookmark_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a class="dropdown-item" href="/pages/membership.php">Membership</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                        <li><a class="dropdown-item" href="/pages/admin.php">Admin Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<?php
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
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("M j, Y", $timestamp);
    }
}
?>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="POST" action="../pages/create_post.php">
            <div class="modal-header">
              <h5 class="modal-title" id="createPostModalLabel">Create New Post</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <input type="text" name="title" class="form-control" placeholder="Post Title" required>
              </div>
              <div class="mb-3">
                <textarea name="content" class="form-control" rows="8" placeholder="Write your story here..." required></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Publish</button>
            </div>
          </form>
        </div>
    </div>
  </div>