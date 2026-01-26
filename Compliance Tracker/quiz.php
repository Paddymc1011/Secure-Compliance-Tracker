<?php
session_start();
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

// Support admin creation via ?action=create
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'create') {
    // only admin may create
    require_role('admin');
}

// Require login for viewing/using quizzes
require_login();

$userId = (int)($_SESSION['user_id'] ?? 0);
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$assignmentId = isset($_GET['assignment']) ? (int)$_GET['assignment'] : 0;

// Handle employee marking assigned quiz complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    if ($assignmentId > 0) {
        $uStmt = $connection->prepare('UPDATE user_quizzes SET status = ?, completed_at = NOW() WHERE id = ? AND user_id = ?');
        if ($uStmt) {
            $status = 'completed';
            $uStmt->bind_param('sii', $status, $assignmentId, $userId);
            $uStmt->execute();
            $uStmt->close();
        }
    } else {
        $iStmt = $connection->prepare('INSERT INTO user_quizzes (user_id, quiz_id, status, completed_at) VALUES (?, ?, ?, NOW())');
        if ($iStmt) {
            $status = 'completed';
            $iStmt->bind_param('iis', $userId, $quizId, $status);
            $iStmt->execute();
            $iStmt->close();
        }
    }
    header('Location: employee.php');
    exit();
}

// Handle admin quiz creation
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;

    if ($title === '') {
        $creation_error = 'Title is required.';
    } else {
        $newQuizId = 0;
        if (empty($due_date)) {
            $stmt = $connection->prepare('INSERT INTO quizzes (title, description) VALUES (?, ?)');
            if ($stmt) {
                $stmt->bind_param('ss', $title, $description);
                $stmt->execute();
                $newQuizId = $stmt->insert_id;
                $stmt->close();
            }
        } else {
            $stmt = $connection->prepare('INSERT INTO quizzes (title, description, due_date) VALUES (?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('sss', $title, $description, $due_date);
                $stmt->execute();
                $newQuizId = $stmt->insert_id;
                $stmt->close();
            }
        }

        if ($newQuizId > 0) {
            $questions = isset($_POST['questions']) && is_array($_POST['questions']) ? $_POST['questions'] : [];
            foreach ($questions as $qIndex => $qText) {
                $qText = trim($qText);
                if ($qText === '') continue;
                $pos = (int)$qIndex;
                $qStmt = $connection->prepare('INSERT INTO questions (quiz_id, text, position) VALUES (?, ?, ?)');
                if (!$qStmt) continue;
                $qStmt->bind_param('isi', $newQuizId, $qText, $pos);
                $qStmt->execute();
                $questionId = $qStmt->insert_id;
                $qStmt->close();

                $optKey = 'options_' . $qIndex;
                $correctKey = 'correct_' . $qIndex;
                $options = isset($_POST[$optKey]) && is_array($_POST[$optKey]) ? $_POST[$optKey] : [];
                $correctIndex = isset($_POST[$correctKey]) ? (int)$_POST[$correctKey] : -1;
                foreach ($options as $optIndex => $optText) {
                    $optText = trim($optText);
                    if ($optText === '') continue;
                    $isCorrect = ($optIndex === $correctIndex) ? 1 : 0;
                    $oStmt = $connection->prepare('INSERT INTO options (question_id, text, is_correct, position) VALUES (?, ?, ?, ?)');
                    if (!$oStmt) continue;
                    $posOpt = (int)$optIndex;
                    $oStmt->bind_param('isii', $questionId, $optText, $isCorrect, $posOpt);
                    $oStmt->execute();
                    $oStmt->close();
                }
            }
            header('Location: quiz.php?id=' . $newQuizId);
            exit();
        } else {
            $creation_error = 'Failed to create quiz. Ensure the quizzes table exists and migrations were run.';
        }
    }
}

// If not creating, we expect an id to view the quiz. Allow admins to open create form by visiting quiz.php
$quiz = null;
if ($action !== 'create') {
    if ($quizId <= 0) {
        // if the current user is admin, treat visiting quiz.php (no id) as create form
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($isAdmin) {
            $action = 'create';
        } else {
            die('Invalid quiz.');
        }
    }

    if ($action !== 'create') {
        $qStmt = $connection->prepare('SELECT * FROM quizzes WHERE id = ? LIMIT 1');
        if ($qStmt) {
            $qStmt->bind_param('i', $quizId);
            $qStmt->execute();
            $qRes = $qStmt->get_result();
            if ($qRes && $qRes->num_rows > 0) {
                $quiz = $qRes->fetch_assoc();
            }
            $qStmt->close();
        }

        if (!$quiz) {
            die('Quiz not found.');
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="center-viewport" style="min-height:60vh;">
    <div class="login-card" style="max-width:720px;">
    <?php if ($action === 'create'): ?>
        <h2>Create Quiz</h2>
        <?php if (!empty($creation_error)): ?>
            <div class="error" style="color:#900;margin-bottom:12px"><?= htmlspecialchars($creation_error) ?></div>
        <?php endif; ?>

        <form method="post" id="create-quiz-form">
            <label>Title<br>
                <input type="text" name="title" required style="width:100%" />
            </label>
            <label style="display:block;margin-top:8px">Description<br>
                <textarea name="description" rows="3" style="width:100%"></textarea>
            </label>
            <label style="display:block;margin-top:8px">Due date (optional)<br>
                <input type="datetime-local" name="due_date" />
            </label>

            <hr />
            <div id="questions-container">
                <!-- JS will add question blocks here -->
            </div>

            <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
                <button type="button" id="add-question" class="btn">Add Question</button>
                <button type="submit" name="create_quiz" class="btn btn-primary">Create Quiz</button>
                <a href="admin.php" class="btn">Cancel</a>
            </div>
        </form>

        <script>
        (function(){
            let qIndex = 0;
            const container = document.getElementById('questions-container');
            const addQBtn = document.getElementById('add-question');
            function addQuestionBlock(prefillText=''){
                const qId = qIndex++;
                const block = document.createElement('div');
                block.className = 'question-block';
                block.style.border='1px solid #e1e1e1';
                block.style.padding='10px';
                block.style.marginTop='8px';
                block.innerHTML = `
                    <label>Question<br><input type="text" name="questions[]" value="${prefillText}" required style="width:100%" /></label>
                    <div style="margin-top:8px">Options (mark the correct one)</div>
                    <div class="options-list" data-qindex="${qId}">
                        <label><input type="radio" name="correct_${qId}" value="0" checked> <input type="text" name="options_${qId}[]" placeholder="Option 1" style="width:80%" required></label>
                        <label style="display:block;margin-top:6px"><input type="radio" name="correct_${qId}" value="1"> <input type="text" name="options_${qId}[]" placeholder="Option 2" style="width:80%" required></label>
                    </div>
                    <div style="margin-top:6px"><button type="button" class="add-option btn small">Add Option</button> <button type="button" class="remove-question btn small">Remove Question</button></div>
                `;
                container.appendChild(block);

                // attach handlers
                block.querySelector('.add-option').addEventListener('click', function(){
                    const optList = block.querySelector('.options-list');
                    const idx = optList.getAttribute('data-qindex');
                    const optionCount = optList.querySelectorAll('input[type=text]').length;
                    const lbl = document.createElement('label');
                    lbl.style.display='block';
                    lbl.style.marginTop='6px';
                    lbl.innerHTML = `<input type="radio" name="correct_${idx}" value="${optionCount}"> <input type="text" name="options_${idx}[]" placeholder="Option ${optionCount+1}" style="width:80%" required>`;
                    optList.appendChild(lbl);
                });

                block.querySelector('.remove-question').addEventListener('click', function(){
                    block.remove();
                });
            }

            addQBtn.addEventListener('click', function(){ addQuestionBlock(); });
            // start with one question
            addQuestionBlock();
        })();
        </script>

    <?php else: ?>
        <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
        <?php if (!empty($quiz['due_date'])): ?>
            <div class="muted">Due: <?php echo htmlspecialchars($quiz['due_date']); ?></div>
        <?php endif; ?>

        <p><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>

        <form method="post">
            <button type="submit" name="mark_complete" class="btn btn-primary">Mark as Completed</button>
            <a href="employee.php" class="btn">Back</a>
        </form>
    <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php';
