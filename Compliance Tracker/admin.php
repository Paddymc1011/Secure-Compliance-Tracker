<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_role('admin');

// Fetch users (show id, username, password hash, role, created_at)
$users = [];
try {
    // Update the SQL query to use the simplified table name 'users'
    $sql = "SELECT id, username, password, role, created_at FROM users ORDER BY id DESC";
    $result = $connection->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} catch (Exception $e) {
    $users = [];
}


?>
<?php include __DIR__ . '/includes/header.php'; ?>

    <div class="container" style="margin-top:8px;">
        <?php if (!empty($_GET['deleted'])): ?>
            <div class="alert alert-success" style="margin-bottom:12px;">User deleted successfully.</div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div class="alert alert-error" style="margin-bottom:12px;">Error: <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <?php if (!empty($_GET['quiz_created'])): ?>
            <div class="alert alert-success" style="margin-bottom:12px;">
                Quiz created successfully.
                <?php if (!empty($_GET['quiz_id'])): ?>
                    <a href="assign_quiz.php?quiz_id=<?php echo (int)$_GET['quiz_id']; ?>" class="btn" style="margin-left:8px;">Assign now</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        ?>

        <section class="card">
            <h3>Users</h3>
            <div class="padded-table-container">
                <div class="table-responsive">
                    <table class="table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Username</th>
                                <th scope="col">Password (hash)</th>
                                <th scope="col">Role</th>
                                <th scope="col">Created</th>
                                <th scope="col">Actions</th>
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
                                <td>
                                    <div class="table-actions">
                                        <form method="post" action="admin_delete_user.php" onsubmit="return confirm('Are you sure you want to delete user <?= addslashes(e($u['username'])) ?>? This action cannot be undone.');" style="display:inline">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-actions" style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap">
                <a href="quiz.php" class="btn btn-primary">Create Quiz</a>
                <a href="assign_quiz.php" class="btn btn-primary">Assign Quiz</a>
                <button class="btn btn-primary" onclick="location.href='created_quizzes.php'">Created Quizzes</button>
            </div>
        </section>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
