<!DOCTYPE html>
<html lang="en">
<?php
require "../inc/check_session.inc.php";


// Get the search query if it exists
$query = isset($_GET['query']) ? $_GET['query'] : '';

// Check if the request is from an AJAX request or normal page load
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == 'true';

// Include header and navigation only if it's NOT an AJAX request
if (!$is_ajax) {
  include "../inc/head.inc.php";   // Include header for normal page load
  include "../inc/login_nav.inc.php"; // Include navigation for normal page load
}

require "../inc/post_functions.inc.php";

$user_posts = getUserPosts($_SESSION['user_id']);
$all_posts = getAllPostsWithUsers(); 

// If it's an AJAX request, handle the search and return posts only
if ($is_ajax) {
  if ($query) {
    // Call the function to search posts by title
    $posts = search_posts_by_title($query);
    if (empty($posts)) {
      echo "<p>No items match your search.</p>";
    } else {
      // Display the matching posts
      foreach ($posts as $post) {
        $isUserPost = $post['owner_id'] == $_SESSION['user_id'];
        $author = $isUserPost ? "by you" : "by " . htmlspecialchars($post['username']);
        $created_at = new DateTime($post['created_at']);
        $now = new DateTime();
        $interval = $now->diff($created_at);
        $time_display = ($interval->days <= 7) ? ($interval->days === 0 ? 'Today' : $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago') : $created_at->format('j F, Y');
        
        echo "<div class='post-title-wrapper mb-3' onclick=\"window.location.href='view_post.php?id={$post['post_id']}'\">";
        echo "<h4 class='post-title'>" . htmlspecialchars($post['title']) . "</h4>";
        echo "<small>{$author} &bull; {$time_display}</small>";
        echo "</div>";
      }
    }
  } else {
    // Return all posts if no search query
    foreach ($all_posts as $post) {
      $isUserPost = $post['owner_id'] == $_SESSION['user_id'];
      $author = $isUserPost ? "by you" : "by " . htmlspecialchars($post['username']);
      $created_at = new DateTime($post['created_at']);
      $now = new DateTime();
      $interval = $now->diff($created_at);
      $time_display = ($interval->days <= 7) ? ($interval->days === 0 ? 'Today' : $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago') : $created_at->format('j F, Y');
      
      echo "<div class='post-title-wrapper mb-3' onclick=\"window.location.href='view_post.php?id={$post['post_id']}'\">";
      echo "<h4 class='post-title'>" . htmlspecialchars($post['title']) . "</h4>";
      echo "<small>{$author} &bull; {$time_display}</small>";
      echo "</div>";
    }
  }
  exit; // End the request after processing
}
?>

<!-- Non-AJAX content (only rendered during normal page load) -->
<?php if (!$is_ajax): ?>
<body class="bg-light">
  <div class="container-fluid">
    <div class="row">
      <!-- Side Panel -->
      <aside class="col-md-3 bg-dark text-white p-4 sticky-sidebar">
        <h4>My Dashboard</h4>
        <button class="btn btn-success mb-3 w-100" data-bs-toggle="modal" data-bs-target="#createPostModal">+ Create Post</button>
        <h5>My Posts</h5>
        <ul class="list-unstyled">
          <?php while ($post = $user_posts->fetch_assoc()): ?>
            <li class="d-flex justify-content-between align-items-center mb-2">
              <span style="display: inline-block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle; padding-bottom: 0.5rem;"><?= htmlspecialchars($post['title']) ?></span>
              <div class="text-nowrap" style="padding-bottom: 0.5rem; text-align: right">
                <a href="edit_post.php?id=<?= $post['post_id'] ?>" class="btn btn-sm btn-outline-warning ms-2">Edit</a>
                <a href="#" class="btn btn-sm btn-outline-danger ms-2"
                  data-bs-toggle="modal" 
                  data-bs-target="#deletePostModal"
                  data-post-id="<?= $post['post_id'] ?>"
                  data-post-title="<?= htmlspecialchars($post['title']) ?>">
                  Delete
                </a>
              </div>
            </li>
          <?php endwhile; ?>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="col-md-9 p-4">
        <h1>Welcome, <?= htmlspecialchars($fname) ?> <?= htmlspecialchars($lname) ?>!</h2>
        <p class="text-center">You are logged in as <strong><?= htmlspecialchars($username) ?></strong>.</p>
        <p>Explore and share your ideas.</p>

        <?php if (isset($_GET['msg'])): ?>
          <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <h2 class="mt-4">Explore All Posts</h2>
        <div class="all-posts" id="posts-container">
          <?php
          // Check if search query exists, if not display all posts
          if ($query == "") {
            foreach ($all_posts as $post) {
              $isUserPost = $post['owner_id'] == $_SESSION['user_id'];
              $author = $isUserPost ? "by you" : "by " . htmlspecialchars($post['username']);
              $created_at = new DateTime($post['created_at']);
              $now = new DateTime();
              $interval = $now->diff($created_at);
              $time_display = ($interval->days <= 7) ? ($interval->days === 0 ? 'Today' : $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago') : $created_at->format('j F, Y');
              echo "<div class='post-title-wrapper mb-3' onclick=\"window.location.href='view_post.php?id={$post['post_id']}'\">";
              echo "<h3 class='post-title'>" . htmlspecialchars($post['title']) . "</h4>";
              echo "<small>{$author} &bull; {$time_display}</small>";
              echo "</div>";
            }
          }
          ?>
        </div>
      </main>
    </div>
  </div>

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

  <!-- Delete Post Modal -->
  <div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="deletePostModalLabel">Confirm Delete</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the post "<span id="deletePostTitle"></span>"?</p>
          <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <form action="../pages/delete_post.php" method="post">
            <input type="hidden" name="post_id" id="deletePostId">
            <button type="submit" class="btn btn-danger">Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include "../inc/footer.inc.php"; ?>

  <!-- Link to the external JS file -->
  <script src="../js/main.js"></script>
  
  <script>
    // Script to populate the delete modal with post info
    document.addEventListener('DOMContentLoaded', function() {
      const deletePostModal = document.getElementById('deletePostModal');
      if (deletePostModal) {
        deletePostModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const postId = button.getAttribute('data-post-id');
          const postTitle = button.getAttribute('data-post-title');
          
          document.getElementById('deletePostId').value = postId;
          document.getElementById('deletePostTitle').textContent = postTitle;
        });
      }
    });
  </script>
</body>
<?php endif; ?>
