<!DOCTYPE html>
<html lang="en">
<?php
    require "../inc/check_session.inc.php"; // Includes session check & user fetch logic
    include "../inc/head.inc.php";
    include "../inc/login_nav.inc.php";
    $membershipType = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : "Friend of Medium";
    $membershipPrice = isset($_GET['price']) ? htmlspecialchars($_GET['price']) : "120";
?>


<body>
    
    <main class="container my-5" style="padding-bottom: 0px !important;">
        <h1 class="h2 text-center fw-bold">Payment</h1>
        <p class="text-center text-muted">
            You are signed up with <strong><?=htmlspecialchars($email)?></strong>. 
            <a href="#" style="color: #0047AB;" data-bs-toggle="modal" data-bs-target="#logoutModal">Not you?</a>
        </p>

        <div class="card p-4 shadow-sm mx-auto" style="max-width: 600px;">
            <h2 class="h5 fw-bold"><?php echo $membershipType; ?> (annual)</h2>
            <p class="text-muted">Billed Today</p>
            <h3 class="fw-bold">$<?php echo $membershipPrice; ?></h3>

            <hr>

            <h3 class="h6 fw-bold mt-3">Credit/Debit Card</h3>
            <form>
                <div class="mb-3">
                    <label class="form-label">Card Number</label>
                    <input type="text" class="form-control" placeholder="5555 4444 4444 4444" required>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Expiration</label>
                        <input type="text" class="form-control" placeholder="MM/YY" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Security Code</label>
                        <input type="text" class="form-control" placeholder="123" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark w-100 mt-3">Pay</button>
            </form>

            <hr>

            <h3 class="h6 fw-bold text-center mt-3">Express Checkout</h3>
        
            <a href="https://www.paypal.com/paypalme/" target="_blank" class="btn btn-outline-dark w-100 d-block text-center">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" width="70">
            </a>
        </div>

        <p class="text-center text-muted mt-5 text-sm">
            <small>
                By starting a Medium membership, you agree to our <a href="#" data-bs-toggle="modal" data-bs-target="#TandS">Membership Terms of Service</a>.
                Your payment method will be charged a recurring $<?php echo $membershipPrice; ?> SGD yearly fee unless you cancel.
                No refunds for memberships canceled between billing cycles.
            </small>
        </p>
    </main>
    <?php
    include "../inc/footer.inc.php";
    ?>
</body>

<!-- Terms and Services Modal -->
<div class="modal fade" id="TandS" tabindex="-1" aria-labelledby="TandSLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="TandSLabel">Membership Terms of Service</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-start">
        <p>Welcome to our membership program. By signing up, you agree to the following terms:</p>
        <h6>1. Billing and Renewal</h6>
        <p>
        You will be charged SGD $<?= htmlspecialchars($membershipPrice) ?> yearly. Your membership will automatically renew each year unless canceled before the renewal date.
        By providing your payment information, you authorise us to charge your selected payment method annually.
        </p>
        <h6>2. Cancellation</h6>
        <p>
        You may cancel your membership at any time from your account settings. Cancellations take effect at the end of the current billing cycle.
        No prorated refunds will be provided.
        </p>
        <h6>3. Changes to Terms</h6>
        <p>
        We reserve the right to modify these Terms at any time. Updates will be posted here and reflected by a new effective date.
        </p>
        <h6>4. Contact Us</h6>
        <p>
        For questions or concerns about your membership, contact us at <a href="mailto:inf1005group1@outlook.com">inf1005group1@outlook.com</a>.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>
