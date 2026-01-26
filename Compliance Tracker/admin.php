<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_role('admin');

// Fetch users (show id, username, password hash, role, created_at)
$users = [];
try {
    // Use fully-qualified table name in case the DB is named Securecompliancetracker
    $sql = "SELECT id, username, password, role, created_at FROM Securecompliancetracker.users ORDER BY id DESC";
    $result = $connection->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} catch (Exception $e) {
    $users = [];
}


?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin — Compliance Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="topbar">
        <div class="container">
            <h2>Admin Dashboard</h2>
            <div class="right">
                <span>Welcome, <?= e($_SESSION['username']) ?></span>
                <a class="btn small" href="logout.php">Sign out</a>
            </div>
        </div>
    </header>
    <main class="container">
        <section class="card">
            <h3>Users</h3>
            <!-- Export removed per request -->
            <div class="padded-table-container">
            <div class="table-responsive">
            <table class="table padded-table" role="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">Password (hash)</th>
                        <th scope="col">Role</th>
                        <th scope="col">Created</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['id']) ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><code title="<?= e($u['password']) ?>"><?= e($u['password']) ?></code></td>
                        <td><?= e($u['role']) ?></td>
                        <td><?= e($u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            </div>
            <div class="admin-actions" style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap">
                <button id="create-quiz-btn" type="button" class="btn btn-primary" onclick="window.location.href='quiz.php'">Create Quiz</button>
                <a href="assign_quiz.php" class="btn btn-primary">Assign Quiz</a>
                <a href="quiz.php" class="btn btn-primary">Open Quiz Page</a>
            </div>
        </section>

        <!-- Quick Actions removed per request -->
    </main>
</body>
</html>
