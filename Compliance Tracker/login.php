<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include __DIR__ . '/dbcon.php';

//  authenticate user and redirect to employee.php on success
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['status'] = 'Please provide username and password.';
        header('Location: login.php');
        exit();
    }

    $sql = 'SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1';
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
            // authentication successful
            $_SESSION['user_id'] = (int)$row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'] ?? '';
            unset($_SESSION['status']);

            // Redirect admins to admin dashboard, others to employee dashboard
            $role = strtolower(trim((string)($_SESSION['role'] ?? '')));
            if ($role === 'admin') {
                header('Location: admin.php');
                exit();
            }

            header('Location: employee.php');
            exit();
        }
    }

    $_SESSION['status'] = 'Invalid username or password.';
    header('Location: login.php');
    exit();
}

?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="center-viewport" style="min-height:calc(100vh - 200px);">
    <div class="login-card">
        <h2>Secure Compliance Login</h2>

        <?php if (!empty($_GET['registered']) && !empty($_GET['uid'])): ?>
            <p class="status-message" style="color:green;">You have been successfully registered. Your user ID is <?php echo htmlspecialchars($_GET['uid']); ?>.</p>
        <?php endif; ?>

        <?php if (!empty($_SESSION['status'])): ?>
            <p class="status-message"><?php echo htmlspecialchars($_SESSION['status']); ?></p>
            <?php unset($_SESSION['status']); ?>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>