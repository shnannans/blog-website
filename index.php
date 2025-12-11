<?php
session_start(); // Start session to track if user is logged in

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // If logged in, show logged-in homepage
    header("Location: pages/home_loggedin.php");
    exit(); // Stop further execution
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include "inc/head.inc.php";
    ?>
    
</head>
<body>
    <?php
        include "inc/nav.inc.php";
    ?>
    
    
    <main class="container-fluid d-flex flex-column justify-content-center align-items-center vh-100">
        <section id="home-page" class="row">
            <div class="text-content display-4 display-md-1 text-center text-md-start">
                <h1>Human <br> stories & ideas</h1>
                <p >Discover new cooking inspirations.</p>
                <a href="/pages/register.php" class="btn btn-dark btn-lg rounded-pill mt-3 px-5">Start reading</a>
            </div>
        </section>
    </main>

    


    <?php
        include "inc/footer.inc.php";
    ?>
</body>
</html>