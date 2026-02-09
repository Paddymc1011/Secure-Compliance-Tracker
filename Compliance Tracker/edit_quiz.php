<?php
session_start();
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

require_role('admin');

// Fetch quiz details
$quiz = null;
if (isset($_GET['quiz_id'])) {
    $quiz_id = (int)$_GET['quiz_id'];
    try {
        $stmt = $connection->prepare("SELECT * FROM create_quiz WHERE quiz_id = ?");
        $stmt->bind_param('i', $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $quiz = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        $error = 'Failed to fetch quiz details: ' . $e->getMessage();
    }
}

if (!$quiz) {
    die('Quiz not found.');
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:20px;">
    <h2>Edit Quiz</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="update_quiz.php">
        <input type="hidden" name="quiz_id" value="<?php echo (int)$quiz['quiz_id']; ?>">

        <div class="form-group">
            <label for="questions">Questions</label>
            <textarea name="questions" id="questions" class="form-control" rows="10" required><?php echo htmlspecialchars($quiz['questions']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="date" name="due_date" id="due_date" class="form-control" value="<?php echo htmlspecialchars($quiz['due_date']); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Quiz</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>