<!DOCTYPE html>
<html lang="en">

<?php
include "../inc/head.inc.php";
if (isset($_GET['error'])) {
    $errorMessage = urldecode($_GET['error']);
}
?>

<body>
    <?php
    include "../inc/nav.inc.php";
    ?>

<main>
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card bg-black text-white" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center">

                            <div class="mb-md-5 mt-md-4 pb-1">

                                <?php if (!empty($errorMessage)): ?>
                                    <div class="alert alert-danger text-center">
                                        <?php echo htmlspecialchars($errorMessage); ?>
                                    </div>
                                <?php endif; ?>

                                <h1 class="fw-bold mb-4">Sign Up</h1>

                                <form action="/process/process_register.php" method="post">

                                    <div class="mb-4">
                                        <input required type="text" id="username" name="username" class="form-control" placeholder="Enter Username">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-4 pb-2">

                                            <div data-mdb-input-init class="form-outline">
                                                <input type="text" id="fname" name="fname" class="form-control" placeholder="First Name">
                                                
                                            </div>

                                            </div>
                                            <div class="col-md-6 mb-4 pb-2">

                                            <div data-mdb-input-init class="form-outline">
                                                <input type="text" id="lname" name="lname" class="form-control" placeholder="Last Name">
                                            </div>

                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <input required type="email" id="email" name="email" class="form-control" placeholder="Enter email">
                                    </div>

                    

                                    <div class="mb-4">
                                        <input required type="password" id="pwd" name="pwd" class="form-control" placeholder="Enter password">
                                    </div>

                                    <div class="mb-4">
                                        <input required type="password" id="pwd_confirm" name="pwd_confirm" class="form-control" placeholder="Confirm password">
                                    </div>

                                    <div class="mb-4 form-check">
                                        <input required type="checkbox" name="agree" id="agree" class="form-check-input">
                                        <label class="form-check-label" for="agree">
                                            Agree to terms and conditions.
                                        </label>
                                    </div>

                                    <div class="mb-0">
                                        <button type="submit" class="btn btn-dark">Submit</button>
                                    </div>
                                
                                </form>
                            </div>

                        <div>
                            <p class="mb-0">Have an account? <a href="login.php" class="text-white-50 fw-bold small-font-size">Login</a></p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
    include "../inc/footer.inc.php";
?>
    
</body>



