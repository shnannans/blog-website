<?php
require '../inc/db.inc.php'; 

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $sql = "SELECT * FROM user_info WHERE verify_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Mark user as verified
        $update = "UPDATE user_info SET is_verified = 1, verify_token = NULL WHERE verify_token = ?";
        $updateStmt = $conn->prepare($update);
        $updateStmt->bind_param("s", $token);
        if ($updateStmt->execute()) {
            // Redirect to login with success
            header("Location: login.php?verified=success");
            exit();
        } else {
            // Redirect with error
            header("Location: register.php?verified=fail");
            exit();
        }
    } else {
        // Invalid token
        header("Location: register.php?verified=invalid");
        exit();
    }
} else {
    header("Location: register.php?verified=missing");
    exit();
}

$conn->close();
?>
