<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

require_role('admin');

$error = '';
$success = '';

/* ================= FETCH QUIZZES ================= */
$quizzes = [];
$result = $connection->query(
    "SELECT quiz.quiz_id, quiz.title, quiz.due_date, question.question_text, options.option_text, options.is_correct
     FROM quiz
     LEFT JOIN question ON quiz.quiz_id = question.quiz_id
     LEFT JOIN options ON question.question_id = options.question_id
     ORDER BY quiz.created_at DESC"
);
while ($row = $result->fetch_assoc()) {
    $quizzes[] = $row;
}

/* ================= FETCH EMPLOYEES ================= */
$users = [];
$result = $connection->query(
    "SELECT user_id, username
     FROM users
     WHERE role = 'employee'
     ORDER BY username"
);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

/* ================= ASSIGN QUIZ ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_quiz'])) {
    $quiz_id = (int)$_POST['quiz_id'];
    $user_id = (int)$_POST['user_id'];

    if ($quiz_id > 0 && $user_id > 0) {
        try {
            // Assign quiz by storing the quiz_id and user_id in a log table or tracking system
            $stmt = $connection->prepare("INSERT INTO quiz_assignments (quiz_id, user_id, assigned_at) VALUES (?, ?, NOW())");
            $stmt->bind_param('ii', $quiz_id, $user_id);
            $stmt->execute();
            $stmt->close();

            $success = 'Quiz assigned successfully!';
        } catch (Exception $e) {
            $error = 'Failed to assign quiz: ' . $e->getMessage();
        }
    } else {
        $error = 'Please select a valid quiz and user.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:20px;">
<h2>Assign Quiz</h2>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="category">Select Category:</label>
        <select name="category" id="category" class="form-control" required>
            <option value="">-- Select a Category --</option>
            <option value="malware">Malware & Ransomware Attacks</option>
            <option value="phishing">Phishing Attacks</option>
            <option value="emerging">Emerging Threats</option>
            <option value="iot">IoT Attacks</option>
        </select>
    </div>
    <div class="form-group">
        <label for="quiz_id">Select Quiz:</label>
        <select name="quiz_id" id="quiz_id" class="form-control" required>
            <option value="">-- Select a Quiz --</option>
            <?php foreach ($quizzes as $quiz): ?>
                <option value="<?= $quiz['quiz_id'] ?>">
                    <?= htmlspecialchars($quiz['title']) ?> (Due: <?= htmlspecialchars($quiz['due_date']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="user_id">Assign to Employee:</label>
        <select name="user_id" id="user_id" class="form-control" required>
            <option value="">-- Select an Employee --</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['user_id'] ?>">
                    <?= htmlspecialchars($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" name="assign_quiz" class="btn btn-primary">Assign Quiz</button>
</form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>