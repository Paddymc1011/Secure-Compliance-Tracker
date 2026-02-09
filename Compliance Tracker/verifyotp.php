<?php
session_start();

// Check if the user is coming from a valid login attempt
if (!isset($_SESSION['otp']) || !isset($_SESSION['email'])) {
    $_SESSION['status'] = "Unauthorized access.";
    header("Location: bankfront.php");
    exit();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $enteredOtp = trim($_POST['otp']);

    // Validate OTP format: must be a 6-digit number
    if (!preg_match('/^\d{6}$/', $enteredOtp)) {
        $_SESSION['status'] = "Invalid OTP format. Please enter a 6-digit number.";
        header("Location: verify_otp.php");
        exit();
    }

    // Verify the OTP
    if ($enteredOtp == $_SESSION['otp']) {
        unset($_SESSION['otp']); // Clear OTP after successful verification
        $_SESSION['status'] = "Logged in successfully";

        // Redirect to loggedin.php
        header("Location: loggedin.php");
        exit();
    } else {
        $_SESSION['status'] = "Invalid OTP. Please try again.";
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container" style="margin-top:18px;">
    <main class="card" style="max-width:620px;margin:0 auto;">
        <h2 class="center">Verify Identity</h2>
        <p class="muted center">Enter the 6-digit code sent to your email</p>

        <?php if (isset($_SESSION['status'])): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($_SESSION['status']); ?></p>
            <?php unset($_SESSION['status']); ?>
        <?php endif; ?>

        <form action="verifyotp.php" method="POST">
            <div class="form-group">
                <label for="otp">One-Time Password (OTP)</label>
                <input type="text" id="otp" name="otp" maxlength="6" class="form-control" placeholder="Enter 6-digit code" required>
            </div>
            <div class="center" style="margin-top:8px;">
                <button type="submit" class="btn btn-primary">Verify OTP</button>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
