<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once "../inc/db.inc.php"; // Correct path to inc directory
include "../inc/head.inc.php";

?>

<body>
    <?php
        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            include "../inc/login_nav.inc.php";  // Show logged-in navigation
        } else {
            include "../inc/nav.inc.php";  // Show default navigation
        }
    ?>
    
    <main class="membership-page-main container text-center mt-4" style="display: block; flex: unset; padding-top: 40px; padding-bottom: 40px; justify-content: unset;">
        <h1 class="fw-bold">Membership Plans</h1>
    </main>
    
    <section class="container my-2 mb-5" aria-label="Membership Options">
        <div class="d-flex justify-content-center gap-4 flex-wrap">
            <!-- Medium Member Plan -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="card shadow-sm text-center p-4">
                    <div class="mb-3 text-warning">&#11088;</div>
                    <h2 class="h4 fw-bold">Blooger Member</h2>
                    <p class="text-muted">$5/month or $60/year</p>
                    <?php 
                        if (isset($_SESSION['user_id'])) {
                            echo '<a href="payment.php?type=Blooger Member&price=60" class="btn btn-dark rounded-pill px-3 py-2">Upgrade Now</a>';
                        } else {
                            echo '<a href="login.php" class="btn btn-dark rounded-pill px-3 py-2">Upgrade Now</a>';
                        }
                    ?>

                    <hr>
                    <ul class="list-unstyled text-start mx-auto w-75 text-wrap" style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">
                        <li style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">&#10003; Read member-only stories</li>
                        <li style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">&#10003; Support writers you read most</li>
                        <li style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">&#10003; Listen to audio narrations</li>
                        <li style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">&#10003; Read offline with the Blooger app</li>

                    </ul>
                </div>
            </div>
            <!-- Friend of Blooger Plan -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="card shadow-sm text-center p-4">
                    <div class="mb-3 text-warning">&#129505;</div>
                    <h2 class="h4 fw-bold">Friend of Blooger</h2>
                    <p class="text-muted">$10/month or $120/year</p>

                    <?php 
                        if (isset($_SESSION['user_id'])) {
                            echo '<a href="payment.php?type=Friend of Blooger&price=120" class="btn btn-dark rounded-pill px-3 py-2">Upgrade Now</a>';
                        } else {
                            echo '<a href="login.php" class="btn btn-dark rounded-pill px-3 py-2">Upgrade Now</a>';
                        }
                    ?>

                    <hr>
                    <ul class="list-unstyled text-start mx-auto w-75 text-wrap"> 
                        <li style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">&#11088; All Medium member benefits</li>
                        <li style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">&#10003; Give 4x more to the writers you read</li>
                        <li style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">&#10003; Share member-only stories with anyone</li>
                        <li style="white-space: normal; word-break: break-word; overflow-wrap: anywhere;">&#10003; Earn money for your writing</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <?php
    include "../inc/footer.inc.php";
    ?>
</body>
