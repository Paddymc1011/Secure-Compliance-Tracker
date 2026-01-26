<?php
session_start();
include __DIR__ . '/dbcon.php';

// POST handler: authenticate admin user and redirect to admin.php on success
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['status'] = 'Please provide username and password.';
        header('Location: adminlogin.php');
        exit();
    }

    $sql = 'SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1';
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        $_SESSION['status'] = 'Database error.';
        header('Location: adminlogin.php');
        exit();
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hash = $row['password'];
        if (password_verify($password, $hash)) {
            // role check: only allow admin users
            $role = strtolower(trim((string)($row['role'] ?? '')));
            if ($role !== 'admin') {
                $_SESSION['status'] = 'Access denied: admin accounts only.';
                header('Location: adminlogin.php');
                exit();
            }

            // authentication successful and role is admin
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'] ?? '';
            unset($_SESSION['status']);
            header('Location: admin.php');
            exit();
        }
    }

    $_SESSION['status'] = 'Invalid username or password.';
    header('Location: adminlogin.php');
    exit();
}

?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="center-viewport" style="min-height:calc(100vh - 200px);">
    <div class="login-card">
        <h2>Admin Login</h2>

        <?php if (!empty($_GET['registered']) && !empty($_GET['uid'])): ?>
            <p class="status-message" style="color:green;">You have been successfully registered. Your user ID is <?php echo htmlspecialchars($_GET['uid']); ?>.</p>
        <?php endif; ?>

        <?php if (!empty($_SESSION['status'])): ?>
            <p class="status-message"><?php echo htmlspecialchars($_SESSION['status']); ?></p>
            <?php unset($_SESSION['status']); ?>
        <?php endif; ?>

        <form action="adminlogin.php" method="POST">
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
