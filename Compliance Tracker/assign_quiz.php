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
    "SELECT quiz_id, title, due_date, created_at
     FROM quiz
     ORDER BY created_at DESC"
);
while ($row = $result->fetch_assoc()) {
    $quizzes[] = $row;
}

/* ================= FETCH EMPLOYEES ================= */
$users = [];
$result = $connection->query(
    "SELECT id, username
     FROM users
     WHERE role = 'employee'
     ORDER BY username"
);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

/* ================= ASSIGN QUIZ ================= */
$categoryPages = [
    'Malware and Ransomware Attacks' => 'malware.php',
    'Phishing Attacks' => 'phishingquiz.php',
    'Emerging Threats' => 'emergingthreatsquiz.php',
    'IoT Attacks' => 'iotattackquiz.php',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_quiz'])) {
    $quiz_id = (int)$_POST['quiz_id'];
    $user_id = (int)$_POST['user_id'];
    $category = trim($_POST['category']);

    // Validate category input
    if (!array_key_exists($category, $categoryPages)) {
        $error = 'Invalid category selected.';
    } elseif ($quiz_id > 0 && $user_id > 0) {
        try {
            $stmt = $connection->prepare("INSERT INTO user_quizzes (quiz_id, user_id, category, status) VALUES (?, ?, ?, 'assigned')");
            $stmt->bind_param('iis', $quiz_id, $user_id, $category);
            $stmt->execute();
            $stmt->close();

            // Redirect to the appropriate page
            header("Location: " . $categoryPages[$category]);
            exit;
        } catch (Exception $e) {
            $error = 'Failed to assign quiz: ' . $e->getMessage();
        }
    } else {
        $error = 'Please select a valid quiz, user, and category.';
    }
}

/* ================= FETCH QUIZ PREVIEW ================= */
$quizDetails = [];
$result = $connection->query(
    "SELECT q.quiz_id,
            qq.id AS question_id,
            qq.question_text,
            o.option_text,
            o.is_correct
     FROM quiz q
     JOIN quiz_questions qq ON q.quiz_id = qq.quiz_id
     JOIN options o ON qq.id = o.question_id
     ORDER BY q.quiz_id, qq.id, o.id"
);

while ($row = $result->fetch_assoc()) {
    $quizDetails[$row['quiz_id']]['questions'][$row['question_id']]['text'] = $row['question_text'];

    $quizDetails[$row['quiz_id']]['questions'][$row['question_id']]['options'][] = [
        'text' => $row['option_text'],
        'is_correct' => $row['is_correct'],
    ];
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

<form method="post">
    <div class="form-group">
        <label>Select Quiz</label>
        <select name="quiz_id" id="quiz_id" class="form-control" required>
            <option value="">-- Select Quiz --</option>
            <?php foreach ($quizzes as $quiz): ?>
                <option value="<?= $quiz['quiz_id'] ?>">
                    <?= htmlspecialchars($quiz['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Select Employee</label>
        <select name="user_id" class="form-control" required>
            <option value="">-- Select Employee --</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>">
                    <?= htmlspecialchars($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="category">Category</label>
        <select name="category" id="category" class="form-control" required>
            <option value="">-- Select a Category --</option>
            <?php foreach ($categoryPages as $category => $page): ?>
                <option value="<?php echo htmlspecialchars($category); ?>">
                    <?php echo htmlspecialchars($category); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" name="assign_quiz" class="btn btn-primary">
        Assign Quiz
    </button>
</form>

<hr>

<h3>Quiz Preview</h3>
<div id="quiz_questions">Select a quiz to preview.</div>
</div>

<script>
const quizDetails = <?= json_encode($quizDetails) ?>;

document.getElementById('quiz_id').addEventListener('change', function () {
    const quizId = this.value;
    const container = document.getElementById('quiz_questions');
    container.innerHTML = '';

    if (!quizDetails[quizId]) {
        container.innerHTML = '<p>No questions found.</p>';
        return;
    }

    Object.values(quizDetails[quizId].questions).forEach(q => {
        const div = document.createElement('div');
        div.innerHTML = `<strong>${q.text}</strong>`;

        const ul = document.createElement('ul');
        q.options.forEach(o => {
            const li = document.createElement('li');
            li.textContent = o.text + (o.is_correct ? ' (Correct)' : '');
            ul.appendChild(li);
        });

        div.appendChild(ul);
        container.appendChild(div);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>