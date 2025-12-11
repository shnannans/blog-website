<?php
require_once "db.inc.php";

// Fetch posts by user
function getUserPosts($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM post_info WHERE owner_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch a single post by post_id AND owner_id
function getPostByIdAndOwner($post_id, $owner_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM post_info WHERE post_id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $post_id, $owner_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch all posts with usernames
function getAllPostsWithUsers() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, u.username 
        FROM post_info p 
        JOIN user_info u ON p.owner_id = u.member_id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch posts by title (search functionality)
function search_posts_by_title($query) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT p.*, u.username 
        FROM post_info p 
        JOIN user_info u ON p.owner_id = u.member_id 
        WHERE LOWER(p.title) LIKE LOWER(?) 
        ORDER BY p.created_at DESC
    ");
    $search_query = '%' . $query . '%';  // Changed from '$query%' to '%$query%'
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    return $stmt->get_result();
}
?>
