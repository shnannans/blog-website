<?php
require "../inc/check_session.inc.php";
require "../inc/db.inc.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $owner_id = $_SESSION['user_id']; // Assuming 'user_id' stores member_id in session

    if (empty($title) || empty($content)) {
        echo "Title and Content cannot be empty.";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO post_info (title, content, owner_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $title, $content, $owner_id);

    if ($stmt->execute()) {
        $post_id = $conn->insert_id; // Get the ID of the newly created post
        
        // Check if the subscriptions table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'user_subscriptions'");
        $subscriptions_table_exists = ($table_check->num_rows > 0);
        
        if ($subscriptions_table_exists) {
            // Get all subscribers for this author
            $subscribers_stmt = $conn->prepare("
                SELECT subscriber_id FROM user_subscriptions WHERE author_id = ?
            ");
            $subscribers_stmt->bind_param("i", $owner_id);
            $subscribers_stmt->execute();
            $subscribers_result = $subscribers_stmt->get_result();
            
            if ($subscribers_result->num_rows > 0) {
                // Check if notifications table exists, create if not
                $notifications_check = $conn->query("SHOW TABLES LIKE 'user_notifications'");
                $notifications_table_exists = ($notifications_check->num_rows > 0);
                
                if (!$notifications_table_exists) {
                    // Create notifications table
                    $conn->query("
                        CREATE TABLE user_notifications (
                            notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                            user_id INT UNSIGNED NOT NULL,
                            author_id INT UNSIGNED NOT NULL,
                            post_id INT UNSIGNED NOT NULL,
                            notification_type VARCHAR(50) NOT NULL,
                            message TEXT,
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            is_read TINYINT(1) NOT NULL DEFAULT 0,
                            PRIMARY KEY (notification_id),
                            CONSTRAINT fk_notification_user
                                FOREIGN KEY (user_id) REFERENCES user_info (member_id) ON DELETE CASCADE,
                            CONSTRAINT fk_notification_author
                                FOREIGN KEY (author_id) REFERENCES user_info (member_id) ON DELETE CASCADE,
                            CONSTRAINT fk_notification_post
                                FOREIGN KEY (post_id) REFERENCES post_info (post_id) ON DELETE CASCADE
                        ) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin
                    ");
                    $notifications_table_exists = true;
                }
                
                if ($notifications_table_exists) {
                    // Get author name for notification
                    $author_name = $_SESSION['fname'] . ' ' . $_SESSION['lname'];
                    
                    // Create notification for each subscriber
                    $notification_stmt = $conn->prepare("
                        INSERT INTO user_notifications 
                        (user_id, author_id, post_id, notification_type, message) 
                        VALUES (?, ?, ?, 'new_post', ?)
                    ");
                    
                    $notification_type = "new_post";
                    $message = $author_name . " published a new post: " . $title;
                    
                    while ($subscriber = $subscribers_result->fetch_assoc()) {
                        $subscriber_id = $subscriber['subscriber_id'];
                        $notification_stmt->bind_param("iiis", $subscriber_id, $owner_id, $post_id, $message);
                        $notification_stmt->execute();
                    }
                    
                    $notification_stmt->close();
                }
            }
            
            $subscribers_stmt->close();
        }
        
        header("Location: home_loggedin.php?msg=Post+created+successfully");
    } else {
        echo "Error creating post: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>