<?php
require "../inc/check_admin.inc.php";
require "../inc/db.inc.php";

// Get all users
$query = "SELECT * FROM user_info ORDER BY member_id DESC";
$users = $conn->query($query);

// Include header files
include "../inc/head.inc.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Management - Blooger</title>
</head>
<body>
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <main class="container py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1>User Management</h1>
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
            <div class="card-header bg-dark text-white">
                <h2 class="h5 mb-0">All Users</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Admin</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $user['member_id'] ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['is_admin'] ? 'success' : 'secondary' ?>">
                                                <?= $user['is_admin'] ? 'Yes' : 'No' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="../pages/profile.php?id=<?= $user['member_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye"></i>
                                                    <span class="visually-hidden">View user profile</span>
                                                </a>
                                                <a href="../process/toggle_admin.php?id=<?= $user['member_id'] ?>&status=<?= $user['is_admin'] ? '0' : '1' ?>" 
                                                   class="btn btn-sm btn-outline-<?= $user['is_admin'] ? 'warning' : 'success' ?>">
                                                    <?= $user['is_admin'] ? '<i class="bi bi-person"></i>' : '<i class="bi bi-person-fill-gear"></i>' ?>
                                                    <span class="visually-hidden">
                                                        <?= $user['is_admin'] ? 'Revoke admin access for ' : 'Grant admin access to ' ?><?= htmlspecialchars($user['username']) ?>
                                                    </span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>