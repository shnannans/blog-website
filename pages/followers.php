<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";

// Determine which user to show (default to current user)
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];

// Determine which tab to show (following or followers)
$active_tab = isset($_GET['tab']) && $_GET['tab'] == 'followers' ? 'followers' : 'following';

// Get user information
$stmt = $conn->prepare("
    SELECT username, fname, lname, profile_pic, about_me,
    (SELECT COUNT(*) FROM user_follows WHERE follower_id = ?) AS following_count,
    (SELECT COUNT(*) FROM user_follows WHERE following_id = ?) AS followers_count
    FROM user_info WHERE member_id = ?
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect if user not found
if (!$user) {
    header("Location: home_loggedin.php");
    exit();
}

// Fetch users this person is following
$following_stmt = $conn->prepare("
    SELECT u.member_id, u.username, u.fname, u.lname, u.profile_pic,
    (SELECT COUNT(*) FROM user_follows WHERE following_id = u.member_id) AS followers_count,
    EXISTS(SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = u.member_id) AS is_following
    FROM user_follows f
    JOIN user_info u ON f.following_id = u.member_id
    WHERE f.follower_id = ?
    ORDER BY f.follow_date DESC
");
$following_stmt->bind_param("ii", $_SESSION['user_id'], $user_id);
$following_stmt->execute();
$following = $following_stmt->get_result();
$following_stmt->close();

// Fetch users following this person
$followers_stmt = $conn->prepare("
    SELECT u.member_id, u.username, u.fname, u.lname, u.profile_pic,
    (SELECT COUNT(*) FROM user_follows WHERE following_id = u.member_id) AS followers_count,
    EXISTS(SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = u.member_id) AS is_following
    FROM user_follows f
    JOIN user_info u ON f.follower_id = u.member_id
    WHERE f.following_id = ?
    ORDER BY f.follow_date DESC
");
$followers_stmt->bind_param("ii", $_SESSION['user_id'], $user_id);
$followers_stmt->execute();
$followers = $followers_stmt->get_result();
$followers_stmt->close();

// Handle follow/unfollow actions
if (isset($_POST['follow_action']) && isset($_POST['target_user_id'])) {
    $target_id = intval($_POST['target_user_id']);
    $current_user_id = $_SESSION['user_id'];
    
    // Don't allow users to follow themselves
    if ($target_id != $current_user_id) {
        if ($_POST['follow_action'] == 'follow') {
            // Add follow relationship
            $follow_stmt = $conn->prepare("INSERT IGNORE INTO user_follows (follower_id, following_id) VALUES (?, ?)");
            $follow_stmt->bind_param("ii", $current_user_id, $target_id);
            $follow_stmt->execute();
            $follow_stmt->close();
        } elseif ($_POST['follow_action'] == 'unfollow') {
            // Remove follow relationship
            $unfollow_stmt = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $unfollow_stmt->bind_param("ii", $current_user_id, $target_id);
            $unfollow_stmt->execute();
            $unfollow_stmt->close();
        }
        
        // Redirect to same page to refresh data
        header("Location: followers.php?user_id={$user_id}&tab={$active_tab}");
        exit();
    }
}

// Default profile picture if none exists
$profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : "image/default_pfp.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $active_tab == 'following' ? 'Following' : 'Followers' ?> - <?= htmlspecialchars($user['username']) ?></title>
    <?php include "../inc/head.inc.php"; ?>
    <style>
        .followers-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .user-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .user-info {
            flex-grow: 1;
        }
        .user-name {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        .user-username {
            color: #6c757d;
            margin-bottom: 10px;
        }
        .user-stats {
            display: flex;
            gap: 20px;
        }
        .user-stat {
            color: #6c757d;
        }
        .user-stat strong {
            color: #212529;
        }
        .nav-tabs {
            margin-bottom: 20px;
        }
        .user-list {
            margin-top: 20px;
        }
        .user-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .user-item:last-child {
            border-bottom: none;
        }
        .user-item-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }
        .user-item-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .user-item-info {
            flex-grow: 1;
        }
        .user-item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .user-item-username {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .follow-btn {
            min-width: 100px;
        }
        .empty-state {
            text-align: center;
            padding: 30px 0;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <div class="container followers-container">
        <!-- User Header -->
        <div class="user-header">
            <div class="user-avatar">
                <img src="../<?= htmlspecialchars($profile_pic) ?>" alt="User profile picture">
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></div>
                <div class="user-username">@<?= htmlspecialchars($user['username']) ?></div>
                <div class="user-stats">
                    <div class="user-stat">
                        <a href="followers.php?user_id=<?= $user_id ?>&tab=following">
                            <strong><?= $user['following_count'] ?></strong> Following
                        </a>
                    </div>
                    <div class="user-stat">
                        <a href="followers.php?user_id=<?= $user_id ?>&tab=followers">
                            <strong><?= $user['followers_count'] ?></strong> Followers
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab == 'following' ? 'active' : '' ?>" 
                   href="followers.php?user_id=<?= $user_id ?>&tab=following">
                    Following
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab == 'followers' ? 'active' : '' ?>" 
                   href="followers.php?user_id=<?= $user_id ?>&tab=followers">
                    Followers
                </a>
            </li>
        </ul>
        
        <!-- User List -->
        <div class="user-list">
            <?php
            // Determine which list to display
            $list = $active_tab == 'following' ? $following : $followers;
            
            if ($list->num_rows > 0):
                while ($person = $list->fetch_assoc()):
                    // Default profile picture if none exists
                    $person_pic = !empty($person['profile_pic']) ? $person['profile_pic'] : "image/default_pfp.jpg";
            ?>
                <div class="user-item">
                    <div class="user-item-avatar">
                        <a href="followers.php?user_id=<?= $person['member_id'] ?>">
                            <img src="../<?= htmlspecialchars($person_pic) ?>" alt="User profile picture">
                        </a>
                    </div>
                    <div class="user-item-info">
                        <div class="user-item-name">
                            <a href="followers.php?user_id=<?= $person['member_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($person['fname'] . ' ' . $person['lname']) ?>
                            </a>
                        </div>
                        <div class="user-item-username">
                            @<?= htmlspecialchars($person['username']) ?>
                            <?php if ($person['followers_count'] > 0): ?>
                                <span class="text-muted"><?= $person['followers_count'] ?> followers</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($person['member_id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="target_user_id" value="<?= $person['member_id'] ?>">
                            <?php if ($person['is_following']): ?>
                                <input type="hidden" name="follow_action" value="unfollow">
                                <button type="submit" class="btn btn-outline-secondary btn-sm follow-btn">
                                    <i class="fas fa-user-minus"></i> Unfollow
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="follow_action" value="follow">
                                <button type="submit" class="btn btn-primary btn-sm follow-btn">
                                    <i class="fas fa-user-plus"></i> Follow
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="empty-state">
                    <i class="fas <?= $active_tab == 'following' ? 'fa-user-plus' : 'fa-users' ?>"></i>
                    <p>
                        <?php if ($active_tab == 'following'): ?>
                            <?= $user_id == $_SESSION['user_id'] ? 'You are' : 'This user is' ?> not following anyone yet.
                        <?php else: ?>
                            <?= $user_id == $_SESSION['user_id'] ? 'You don\'t' : 'This user doesn\'t' ?> have any followers yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>