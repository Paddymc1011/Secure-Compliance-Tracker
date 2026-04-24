<?php
session_start();
// Use the main background on the created quizzes page
$pageClass = 'main-bg';

require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

require_role('admin');

// Fetch all quizzes from the database
$quizzes = [];
try {
    $quizQuery = "SELECT quiz_id, title, due_date, created_at FROM quiz ORDER BY created_at DESC";
    $result = $connection->query($quizQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $quizzes[] = $row;
        }
        // Debugging: Log the number of quizzes fetched
        error_log("Number of quizzes fetched: " . count($quizzes));
    } else {
        error_log("Error executing quiz query: " . $connection->error);
    }
} catch (Exception $e) {
    error_log("Exception occurred while fetching quizzes: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:20px;">
    <h2>All Quizzes</h2>

    <?php if (!empty($quizzes)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Quiz ID</th>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><?php echo (int)$quiz['quiz_id']; ?></td>
                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['due_date']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['created_at']); ?></td>
                        <td>
                            <form method="post" action="delete_quiz.php" onsubmit="return confirm('Are you sure you want to delete this quiz? This action cannot be undone.');" style="display:inline">
                                <input type="hidden" name="quiz_id" value="<?php echo (int)$quiz['quiz_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No quizzes found.</p>
    <?php endif; ?>
</div>

<a href="admin.php" class="btn btn-secondary btn-sm" style="display: inline-block; margin: 20px auto;">Back to Admin Dashboard</a>

<?php include __DIR__ . '/includes/footer.php'; ?>