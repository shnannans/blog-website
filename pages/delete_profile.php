<?php
require "../inc/check_session.inc.php";

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// If the deletion form has not been submitted yet, display the confirmation form.
if (!isset($_POST['confirmation'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title>Confirm Account Deletion</title>
        <?php include "../inc/head.inc.php"; ?>
    </head>
    <body>
        <?php include "../inc/nav.inc.php"; ?>
        <main class="container mt-4">
            <h2>Warning: Account Deletion</h2>
            <p>
                Deleting your account is <strong>irreversible</strong>. To confirm, please type the word <strong>confirm</strong> in the box below and submit.
            </p>
            <form method="post" action="">
                <div class="mb-3">
                    <input type="text" name="confirmation" class="form-control" placeholder="Type 'confirm' to delete your account" required>
                </div>
                <button type="submit" class="btn btn-danger">Delete My Account</button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </form>
        </main>
        <?php include "../inc/footer.inc.php"; ?>
    </body>
    </html>
    <?php
    exit();
}

// Check if the user typed "confirm" (case-insensitive)
if (strtolower(trim($_POST['confirmation'])) !== 'confirm') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title>Account Deletion Error</title>
        <?php include "../inc/head.inc.php"; ?>
    </head>
    <body>
        <?php include "../inc/nav.inc.php"; ?>
        <main class="container mt-4">
            <h2>Incorrect Confirmation</h2>
            <p>You did not type the word <strong>confirm</strong> correctly. Your account has not been deleted.</p>
            <a href="profile.php" class="btn btn-secondary">Return to Profile</a>
        </main>
        <?php include "../inc/footer.inc.php"; ?>
    </body>
    </html>
    <?php
    exit();
}

// At this point, the user has confirmed deletion. Proceed with deleting the account.

// Read the database configuration.
$config = parse_ini_file('/var/www/private/db-config.ini');
if (!$config) {
    die("Failed to read database config file.");
}

// Create a new database connection.
$conn = new mysqli(
    $config['servername'],
    $config['username'],
    $config['password'],
    $config['dbname']  // Adjust this to your database name if needed.
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare a DELETE statement to remove the user's account from the user_info table.
$stmt = $conn->prepare("DELETE FROM user_info WHERE member_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $_SESSION['user_id']);

if (!$stmt->execute()) {
    echo "<h2>Error Deleting Account</h2>";
    echo "<p>" . htmlspecialchars("Error (" . $stmt->errno . "): " . $stmt->error) . "</p>";
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close();
$conn->close();

// Clear all session data and destroy the session.
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Account Deleted</title>
    <?php include "../inc/head.inc.php"; ?>
</head>
<body>
    <?php include "../inc/nav.inc.php"; ?>
    <main class="container mt-4">
        <h2>Farewell and Goodbye</h2>
        <p>Return to Blooger for your next blogging adventure!</p>
        <p>
            <a href="../pages/register.php" class="btn btn-primary">Register Again</a> |
            <a href="../index.php" class="btn btn-secondary">Return Home</a>
        </p>
    </main>
    <?php include "../inc/footer.inc.php"; ?>
</body>
</html>