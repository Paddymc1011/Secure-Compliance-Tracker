<?php
session_start();
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

require_role('admin');

// Fetch all created quizzes
$quizzes = [];
try {
    $quizQuery = "SELECT quiz_id, title, questions, due_date, created_at FROM quiz ORDER BY created_at DESC";
    $result = $connection->query($quizQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $quizzes[] = $row;
        }
    }
} catch (Exception $e) {
    $quizzes = [];
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:20px;">
    <h2>Created Quizzes</h2>

    <?php if (!empty($quizzes)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Quiz ID</th>
                    <th>Title</th>
                    <th>Number of Questions</th>
                    <th>Due Date</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><?php echo (int)$quiz['quiz_id']; ?></td>
                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['questions']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['due_date']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['created_at']); ?></td>
                        <td>
                            <a href="delete_quiz.php?quiz_id=<?php echo (int)$quiz['quiz_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this quiz?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="container" style="margin-top:8px;">
    <section class="card" style="margin-top: 20px;">
        <h3>Created Quizzes</h3>
        <div class="table-responsive">
            <table class="table" role="table">
                <thead>
                    <tr>
                        <th scope="col">Quiz ID</th>
                        <th scope="col">Title</th>
                        <th scope="col">Due Date</th>
                        <th scope="col">Created At</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $quizzes = $connection->query("SELECT quiz_id, title, due_date, created_at FROM quiz ORDER BY created_at DESC");
                while ($quiz = $quizzes->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($quiz['quiz_id']) ?></td>
                        <td><?= e($quiz['title']) ?></td>
                        <td><?= e($quiz['due_date']) ?></td>
                        <td><?= e($quiz['created_at']) ?></td>
                        <td>
                            <form method="post" action="delete_quiz.php" onsubmit="return confirm('Are you sure you want to delete this quiz? This action cannot be undone.');" style="display:inline">
                                <input type="hidden" name="quiz_id" value="<?= (int)$quiz['quiz_id'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<a href="admin.php" class="btn btn-secondary btn-sm" style="display: inline-block; margin: 20px auto;">Back to Admin Dashboard</a>

<?php include __DIR__ . '/includes/footer.php'; ?>