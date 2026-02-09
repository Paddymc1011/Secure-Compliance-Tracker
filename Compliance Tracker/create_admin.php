<?php
// script to create an admin user via a web form.
require_once __DIR__ . '/config.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Very basic validation
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';

    if (empty($username) || empty($email) || empty($password)) {
        $message = 'All fields are required.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $hash, $role);
        try {
            if ($stmt->execute()) {
                $message = 'User created. You can now sign in from the login page.';
            } else {
                $message = 'Error creating user: ' . $stmt->error;
            }
        } catch (Exception $ex) {
            $message = 'Error creating user: ' . e($ex->getMessage());
        }
        $stmt->close();
    }
}

?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container" style="margin-top:12px;">
    <main class="card" style="max-width:720px;margin:0 auto;">
        <h1>Create User (admin/employee)</h1>
        <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="admin">Admin</option>
                    <option value="employee">Employee</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Create</button>
        </form>
        <p class="muted small">After creating the first admin, remove or protect this file to avoid abuse.</p>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
