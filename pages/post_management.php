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
    <title>Post Management - Blooger</title>
</head>
<body>
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <main class="container py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1>Post Management</h1>
                <a href="admin.php" class="btn btn-outline-dark">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="alert alert-<?= $_SESSION['admin_message_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['admin_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['admin_message'], $_SESSION['admin_message_type']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">All Posts</h2>
                <form class="d-flex" role="search">
                    <input class="form-control me-2" type="search" id="postSearch" placeholder="Search posts..." aria-label="Search">
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="postsTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Created</th>
                                <th>Updated</th>
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
                                        <td><?= date("M j, Y", strtotime($post['updated_at'])) ?></td>
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
                                                    <span class="visually-hidden">Delete post titled <?= htmlspecialchars($post['title']) ?></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">No posts found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Post Modal -->
    <div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="h5 modal-title" id="deletePostModalLabel">Confirm Delete</h3>
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

            // Simple search functionality for the posts table
            const searchInput = document.getElementById('postSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const table = document.getElementById('postsTable');
                    const rows = table.getElementsByTagName('tr');

                    // Start from index 1 to skip the header row
                    for (let i = 1; i < rows.length; i++) {
                        const title = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                        const author = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                        
                        if (title.includes(searchValue) || author.includes(searchValue)) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>