<?php
// verifyotp.php - SMS-based MFA verification using Twilio

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Use the login background styling
$pageClass = 'login-bg';

require_once __DIR__ . '/config.php';

// Normalize Irish mobile numbers into E.164 format for Twilio (e.g. 0871234567 -> +353871234567)
function normalize_irish_phone($raw)
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return '';
    }

    // If it already looks like an international number, leave it
    if ($raw[0] === '+') {
        return $raw;
    }

    // Strip all non-digits
    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === '') {
        return '';
    }

    // If it already starts with country code 353, just prefix '+'
    if (strpos($digits, '353') === 0) {
        return '+' . $digits;
    }

    // If it starts with a leading 0 (e.g. 087...), drop the 0 and prefix +353
    if ($digits[0] === '0') {
        return '+353' . substr($digits, 1);
    }

    // Fallback: assume local-style number without 0, prefix +353
    return '+353' . $digits;
}

// Load Composer autoloader for Twilio if available
$twilioAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($twilioAutoload)) {
    require_once $twilioAutoload;
}

use Twilio\Rest\Client;

// Ensure there is a pending login awaiting MFA
if (empty($_SESSION['pending_user']) || !is_array($_SESSION['pending_user'])) {
    $_SESSION['status'] = 'Your session has expired. Please log in again.';
    header('Location: login.php');
    exit();
}

$pendingUser = $_SESSION['pending_user'];
$phoneNumber = normalize_irish_phone($pendingUser['phone_number'] ?? '');

// Generate an OTP if we don't already have one for this session
if (empty($_SESSION['pending_otp'])) {
    try {
        $otp = random_int(100000, 999999);
    } catch (Exception $e) {
        $otp = rand(100000, 999999);
    }
    $_SESSION['pending_otp'] = (string)$otp;
    $_SESSION['otp_expires_at'] = time() + 300; // 5 minutes
} else {
    $otp = $_SESSION['pending_otp'];
}

// Send the OTP via SMS once per OTP
if (empty($_SESSION['otp_sms_sent'])) {
    // Only attempt to send if Twilio is properly configured
    if (
        class_exists(Client::class) &&
        !empty(TWILIO_ACCOUNT_SID) &&
        !empty(TWILIO_AUTH_TOKEN) &&
        !empty(TWILIO_FROM_NUMBER)
    ) {
        try {
            $client = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
            $client->messages->create(
                $phoneNumber,
                [
                    'from' => TWILIO_FROM_NUMBER,
                    'body' => 'Your Secure Compliance login code is ' . $otp,
                ]
            );
            $_SESSION['otp_sms_sent'] = time();
        } catch (Throwable $e) {
            // If SMS fails, abort login and send user back to login page
            $_SESSION['status'] = 'Failed to send verification code. Please try again later.';
            unset($_SESSION['pending_user'], $_SESSION['pending_otp'], $_SESSION['otp_expires_at'], $_SESSION['otp_sms_sent']);
            header('Location: login.php');
            exit();
        }
    } else {
        // Twilio not configured; do not silently bypass MFA
        $_SESSION['status'] = 'Verification service is not configured. Please contact an administrator.';
        unset($_SESSION['pending_user'], $_SESSION['pending_otp'], $_SESSION['otp_expires_at'], $_SESSION['otp_sms_sent']);
        header('Location: login.php');
        exit();
    }
}

$error = '';

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $enteredOtp = trim($_POST['otp']);

    // Validate OTP format: must be a 6-digit number
    if (!preg_match('/^\d{6}$/', $enteredOtp)) {
        $error = 'Invalid code format. Please enter a 6-digit number.';
    } else {
        // Check expiry
        if (!empty($_SESSION['otp_expires_at']) && time() > $_SESSION['otp_expires_at']) {
            $_SESSION['status'] = 'Your verification code has expired. Please log in again.';
            unset($_SESSION['pending_user'], $_SESSION['pending_otp'], $_SESSION['otp_expires_at'], $_SESSION['otp_sms_sent']);
            header('Location: login.php');
            exit();
        }

        if ($enteredOtp === (string)$_SESSION['pending_otp']) {
            // OTP correct: complete login
            $userId = (int)($pendingUser['user_id'] ?? 0);
            $username = $pendingUser['username'] ?? '';
            $role = $pendingUser['role'] ?? '';

            unset($_SESSION['pending_user'], $_SESSION['pending_otp'], $_SESSION['otp_expires_at'], $_SESSION['otp_sms_sent']);

            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['status'] = 'Logged in successfully.';

            $roleNormalized = strtolower(trim((string)$role));
            if ($roleNormalized === 'admin') {
                header('Location: admin.php');
                exit();
            }

            header('Location: employee.php');
            exit();
        } else {
            $error = 'Invalid code. Please try again.';
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container" style="margin-top:18px;">
    <main class="card" style="max-width:620px;margin:0 auto;">
        <h2 class="center">Verify Identity</h2>
        <p class="muted center">Enter the 6-digit code sent to your mobile phone</p>

        <?php if (!empty($_SESSION['status'])): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($_SESSION['status']); ?></p>
            <?php unset($_SESSION['status']); ?>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
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
