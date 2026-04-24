<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Set a page-specific body class so we can style the login background
$pageClass = 'login-bg';
// On the login page we don't want to show a Logout link in the header nav
$hideLogout = true;

include __DIR__ . '/dbcon.php';

// Authenticate user and then require MFA via SMS before completing login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['status'] = 'Please provide username and password.';
        header('Location: login.php');
        exit();
    }

    // We also need the phone_number for SMS-based MFA
    $sql = 'SELECT user_id, username, phone_number, password, role FROM users WHERE username = ? LIMIT 1';
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        $_SESSION['status'] = 'Database error.';
        header('Location: login.php');
        exit();
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hash = $row['password'];
        if (password_verify($password, $hash)) {
            // Password is correct; now require MFA via SMS before final login
            $phoneNumber = trim((string)($row['phone_number'] ?? ''));
            if ($phoneNumber === '') {
                $_SESSION['status'] = 'No phone number is set on your account. Please contact an administrator.';
                header('Location: login.php');
                exit();
            }

            // Store pending login details for OTP verification (do NOT log in fully yet)
            $_SESSION['pending_user'] = [
                'user_id' => (int)$row['user_id'],
                'username' => $row['username'],
                'role' => $row['role'] ?? '',
                'phone_number' => $phoneNumber,
            ];

            // Clear any previous OTP state
            unset($_SESSION['pending_otp'], $_SESSION['otp_expires_at'], $_SESSION['otp_sms_sent']);
            unset($_SESSION['status']);

            // Redirect to OTP verification page where the SMS will be sent
            header('Location: verifyotp.php');
            exit();
        }
    }

    $_SESSION['status'] = 'Invalid username or password.';
    header('Location: login.php');
    exit();
}

?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="center-viewport" style="min-height:calc(100vh - 200px); display:flex; align-items:center; justify-content:flex-start;">
    <div class="login-card" style="max-width:540px; width:100%; margin:0;">
        <h2>Secure Compliance Login</h2>

        <?php if (!empty($_GET['registered']) && !empty($_GET['uid'])): ?>
            <p class="status-message" style="color:green;">You have been successfully registered. Your user ID is <?php echo htmlspecialchars($_GET['uid']); ?>.</p>
        <?php elseif (!empty($_GET['pending'])): ?>
            <p class="status-message" style="color:orange;">Your registration request has been received and is waiting on admin approval.</p>
        <?php endif; ?>

        <?php if (!empty($_SESSION['status'])): ?>
            <p class="status-message"><?php echo htmlspecialchars($_SESSION['status']); ?></p>
            <?php unset($_SESSION['status']); ?>
        <?php endif; ?>

        <form action="login.php" method="POST" style="margin-top:16px;">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>