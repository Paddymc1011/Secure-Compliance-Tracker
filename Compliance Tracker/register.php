<?php
session_start();
include('dbcon.php');

// New registration: username, role, password, confirm password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $role = trim($_POST['role'] ?? 'employee');

    if (empty($username)) {
        $_SESSION['status'] = "Username is required.";
        header("Location: register.php");
        exit();
    }
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($password !== $confirmPassword) {
        $_SESSION['status'] = "Passwords do not match. Please try again.";
        header("Location: register.php");
        exit();
    }

    // Basic password policy: at least 8 chars and one capital letter
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password)) {
        $_SESSION['status'] = "Password must be at least 8 characters long and include at least one capital letter.";
        header("Location: register.php");
        exit();
    }

    // Check username uniqueness
    $checkSql = "SELECT id FROM users WHERE username = ? LIMIT 1";
    $checkStmt = $connection->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $res = $checkStmt->get_result();
        if ($res && $res->num_rows > 0) {
            $_SESSION['status'] = "Username is already taken.";
            header("Location: register.php");
            exit();
        }
    }

    // Generate a unique 6-digit numeric ID and ensure it's not already in users.id
    $userId = generateUniqueUserId($connection);

    // Hash the provided password using password_hash
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert into users table explicitly including `id` column
    $insertSql = "INSERT INTO users (id, username, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
    $insertStmt = $connection->prepare($insertSql);
    if ($insertStmt) {
        $insertStmt->bind_param("isss", $userId, $username, $hash, $role);
        if ($insertStmt->execute()) {
            // redirect to login with success info (uid only)
            header("Location: login.php?registered=1&uid=" . urlencode($userId));
            exit();
        } else {
            $_SESSION['status'] = "Registration failed: " . $insertStmt->error;
            header("Location: register.php");
            exit();
        }
    } else {
        die("Database query failed: " . $connection->error);
    }
}

function generateUniqueUserId($connection) {
    // 6-digit numeric ID
    do {
        $id = rand(100000, 999999);
        $sql = "SELECT id FROM users WHERE id = ? LIMIT 1";
        $stmt = $connection->prepare($sql);
        if (!$stmt) return $id; // if prepare fails, return the id and let the DB error surface
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } while ($result && $result->num_rows > 0);

    return $id;
}

//  users now provide their own password
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="center-viewport" style="min-height:60vh;">
    <div class="login-card" style="max-width:540px;">
        <h2>Register</h2>

        <?php if (!empty($_GET['created']) && !empty($_GET['uid'])): ?>
            <p class="status-message" style="color:green;">User successfully created. Your user ID is <?php echo htmlspecialchars($_GET['uid']); ?>.</p>
            <div style="margin-top:12px">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>

            <?php if (isset($_SESSION['status'])): ?>
                <p class="status-message"><?php echo htmlspecialchars($_SESSION['status']); ?></p>
                <?php unset($_SESSION['status']); ?>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="form-control">
                        <option value="employee">Employee</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" pattern="(?=.*[A-Z]).{8,}" title="At least 8 characters and one capital letter" required>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                </div>

                <div class="form-group" style="margin-bottom:8px;">
                    <label style="font-weight:normal;">
                        <input type="checkbox" id="showPasswords" style="margin-right:8px"> Show passwords while typing
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Register</button>
            </form>

            <div style="margin-top:12px">
                <a href="login.php" class="btn">Back to Login</a>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// Toggle visibility of password fields on the register page
;(function(){
    var toggle = document.getElementById('showPasswords');
    if (!toggle) return;
    var pw = document.getElementById('password');
    var cpw = document.getElementById('confirmPassword');
    toggle.addEventListener('change', function(){
        var t = this.checked ? 'text' : 'password';
        if (pw) pw.type = t;
        if (cpw) cpw.type = t;
    });
})();
</script>
