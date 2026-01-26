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

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create Admin — Compliance Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="center-page">
    <main class="card">
        <h1>Create User (admin/employee)</h1>
        <?php if ($message): ?><div class="info"><?= e($message) ?></div><?php endif; ?>
        <form method="post">
            <label>Username
                <input type="text" name="username" required>
            </label>
            <label>Email
                <input type="email" name="email" required>
            </label>
            <label>Password
                <input type="password" name="password" required>
            </label>
            <label>Role
                <select name="role">
                    <option value="admin">Admin</option>
                    <option value="employee">Employee</option>
                </select>
            </label>
            <button class="btn" type="submit">Create</button>
        </form>
        <p class="muted small">After creating the first admin, remove or protect this file to avoid abuse.</p>
    </main>
</body>
</html>
