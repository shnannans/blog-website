<?php
require '../inc/db.inc.php';
include "../inc/head.inc.php";
include "../inc/nav.inc.php";

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

// Validate the token
$sql = "SELECT * FROM user_info WHERE reset_token = ? AND token_expiry >= NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid or expired token.");
}

$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = "UPDATE user_info SET password = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?";
        $updateStmt = $conn->prepare($update);
        $updateStmt->bind_param("ss", $hashedPassword, $token);
        if ($updateStmt->execute()) {
            header("Location: login.php?resetpw=success");
            exit();
        } else {
            $error = "Error resetting password.";
        }
    }
}
    
?>

<h2 class="text-center mt-5">Reset Your Password</h2>

<?php if (isset($error)) : ?>
    <div class="alert alert-danger text-center"><?= $error ?></div>
<?php endif; ?>

<div class="container d-flex justify-content-center mt-4">
    <form method="POST" class="w-50">
        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" name="password" id="password" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required />
        </div>
        <button type="submit" class="btn btn-dark w-100">Reset Password</button>
    </form>
</div>


<?php
    include "../inc/footer.inc.php";
?>