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

    <?php if (empty($quizzes)): ?>
        <p>No quizzes have been created yet.</p>
    <?php else: ?>
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
    <a href="admin.php" class="btn btn-secondary mt-3">Back to Admin Dashboard</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>