<?php
require "../inc/check_admin.inc.php";
require "../inc/db.inc.php";
require "../inc/post_functions.inc.php";

// Get all posts with user information
$posts = getAllPostsWithUsers();

// Include header files
include "../inc/head.inc.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - Blooger</title>
</head>
<body>
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <main class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Admin Dashboard</h1>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    Welcome to the admin dashboard. As an admin, you can manage all posts and users.
                </div>
            </div>
        </div>

        <!-- Admin Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h2 class="h5 mb-0">Admin Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-3">
                            <a href="user_management.php" class="btn btn-outline-dark">
                                <i class="bi bi-people"></i> Manage Users
                            </a>
                            <a href="post_management.php" class="btn btn-outline-dark">
                                <i class="bi bi-file-earmark-text"></i> Manage Posts
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Posts (with delete capability) -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h3 class="h5 mb-0">Recent Posts</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($posts->num_rows > 0): ?>
                                        <?php while ($post = $posts->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $post['post_id'] ?></td>
                                                <td>
                                                    <a href="../pages/view_post.php?id=<?= $post['post_id'] ?>">
                                                        <?= htmlspecialchars($post['title']) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($post['username']) ?></td>
                                                <td><?= date("M j, Y", strtotime($post['created_at'])) ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="../pages/view_post.php?id=<?= $post['post_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-eye"></i>
                                                            <span class="visually-hidden">View post</span>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="modal" data-bs-target="#deletePostModal"
                                                                data-post-id="<?= $post['post_id'] ?>"
                                                                data-post-title="<?= htmlspecialchars($post['title']) ?>">
                                                            <i class="bi bi-trash"></i>
                                                            <span class="visually-hidden">Delete <?= htmlspecialchars($post['title']) ?> post</span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">No posts found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Post Modal -->
    <div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="h5 modal-title" id="deletePostModalLabel">Confirm Delete</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the post "<span id="deletePostTitle"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="../process/admin_delete_post.php" method="post">
                        <input type="hidden" name="post_id" id="deletePostId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include "../inc/footer.inc.php"; ?>

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
</html>