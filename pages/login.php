<!DOCTYPE html>
<html lang="en">

<?php
session_start();
include "../inc/db.inc.php"; // Ensure correct DB connection
include "../inc/head.inc.php";
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $successMessage = "Registration successful! Please Verify your email before login.";
}

$verifiedSuccess = isset($_GET['verified']) && $_GET['verified'] === 'success';
$resetSuccess = isset($_GET['reset']) && $_GET['reset'] === 'success';
$resetEmail = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
$pwResetSuccess = isset($_GET['resetpw']) && $_GET['resetpw'] === 'success';

?>

<body>
    <?php
    include "../inc/nav.inc.php";
    ?>

    <!-- Display Error Message Popup if there's an error -->
    <?php if (isset($_SESSION["error"])): ?>
        <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1055; margin-top: 70px;">
            <div class="toast align-items-center text-white bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= $_SESSION["error"]; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION["error"]); // Clear the error after displaying ?>
    <?php endif; ?>
    
    <!-- Show popup message based on user action-->
    <?php
        $showToast = false;
        $toastMessage = '';

        if ($resetSuccess) {
            $showToast = true;
            $toastMessage = "Reset email has been sent to <strong>{$resetEmail}</strong>. Please check your inbox.";
        } elseif ($pwResetSuccess) {
            $showToast = true;
            $toastMessage = "Your password has been reset!";
        }
        elseif ($verifiedSuccess) {
            $showToast = true;
            $toastMessage = "Your account has been verified!";
        }
    ?>
    <?php if ($showToast): ?>
        <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1055; margin-top: 70px;">
            <div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= $toastMessage ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <main aria-label="Login Page">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card bg-black text-white" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center">

                            <div class="mb-md-4 mt-md-4 pb-0">
                                
                                <?php if (!empty($successMessage)): ?>
                                    <div class="alert alert-success text-center">
                                        <?php echo htmlspecialchars($successMessage); ?>
                                    </div>
                                <?php endif; ?>

                                <h1 class="fw-bold mb-4">Login Now</h1>

                                <form action="../process/process_login.php" method="post" id="loginForm">
      
                                    <div class="mb-4">
                                        <input required type="email" id="email" name="email" class="form-control" placeholder="Enter email">
                                    </div>

                                    <div class="mb-3">
                                        <input required type="password" id="pwd" name="pwd" class="form-control" placeholder="Enter password">
                                    </div>
                                    

                                    <div class="d-flex justify-content-around align-items-center mb-4">
                                        <!-- Checkbox -->
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="rememberMe" checked>
                                            <label class="form-check-label" for="rememberMe"> Remember me </label>
                                        </div>
                                        <a href="#" class="small link-hover-animate forget-link" data-bs-toggle="modal" data-bs-target="#ModalForgetPW">Forget password?</a>
                                    </div>
                                    
                                    <div class="mb-0">
                                        <button type="submit" class="btn btn-dark">Submit</button>
                                    </div>
                                
                                </form>
                            </div>

                            <div>
                                <p class="mb-0">Don't have an account? <a href="register.php" class="text-white-50 fw-bold small">Sign Up</a></p>

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


    <!-- Modal -->
    <div class="modal top fade" id="ModalForgetPW" tabindex="-1" aria-labelledby="ModalForgetPW"
        aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content text-center">
                <div class="modal-header h5 text-white bg-dark justify-content-center">
                    Password Reset
                </div>
                <div class="modal-body px-5">
                    <p class="py-2">
                        Enter your email address and we'll send you an email with instructions to reset your password.
                    </p>
                    <form action="../process/send_reset_email.php" method="post">
                        <div class="mb-3">
                            <input type="email" id="typeEmail" name="email" class="form-control" placeholder="Enter Email Here" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 btn-fixed-height">Reset password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    
</body>
