<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

session_start();
require_once 'dbcon.php';
require_once 'auth.php';

require_role('employee');

$user_id = $_SESSION['user_id'];

if (!$connection) {
    die("Database connection failed.");
}

// This page shows only Phishing quizzes
$category = 'phishing';

/* ---------------- FETCH ASSIGNED QUIZ FOR CATEGORY ---------------- */

$quizAssignmentQuery = "
    SELECT qa.quiz_id
    FROM quiz_assignments qa
    JOIN quiz q ON q.quiz_id = qa.quiz_id
    WHERE qa.user_id = ?
      AND qa.status = 'assigned'
      AND q.category = ?
    LIMIT 1
";
$quizAssignmentStmt = $connection->prepare($quizAssignmentQuery);
$quizAssignmentStmt->bind_param('is', $user_id, $category);
$quizAssignmentStmt->execute();
$quizAssignmentStmt->bind_result($quiz_id);
$quizAssignmentStmt->fetch();
$quizAssignmentStmt->close();

if (!$quiz_id) {
    die("No assigned quiz found for this category.");
}

/* ---------------- FETCH QUIZ TITLE ---------------- */

$quizQuery = "SELECT title FROM quiz WHERE quiz_id = ?";
$quizStmt = $connection->prepare($quizQuery);
$quizStmt->bind_param('i', $quiz_id);
$quizStmt->execute();
$quizStmt->bind_result($quiz_title);
$quizStmt->fetch();
$quizStmt->close();

if (!$quiz_title) {
    die("No quiz title found.");
}

/* ---------------- FETCH QUESTIONS ---------------- */

$questionsQuery = "SELECT question_id, question_text 
                   FROM question WHERE quiz_id = ?";
$questionsStmt = $connection->prepare($questionsQuery);
$questionsStmt->bind_param('i', $quiz_id);
$questionsStmt->execute();
$questionsResult = $questionsStmt->get_result();

$questions = [];
while ($row = $questionsResult->fetch_assoc()) {
    $questions[] = $row;
}
$questionsStmt->close();

/* ---------------- FETCH OPTIONS ---------------- */

foreach ($questions as $i => $question) {
    $optionsQuery = "SELECT option_id, option_text FROM options WHERE question_id = ?";
    $optionsStmt = $connection->prepare($optionsQuery);
    $optionsStmt->bind_param('i', $question['question_id']);
    $optionsStmt->execute();
    $optionsResult = $optionsStmt->get_result();

    $questions[$i]['options'] = [];
    while ($option = $optionsResult->fetch_assoc()) {
        $questions[$i]['options'][] = $option;
    }
    $optionsStmt->close();
}

/* ---------------- HANDLE SUBMISSION ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correctAnswers = 0;
    $totalQuestions = count($questions);

    foreach ($questions as $question) {

        $questionId = $question['question_id'];
        $submittedOptionId = $_POST['question_' . $questionId] ?? null;

        if ($submittedOptionId) {

            $correctOptionQuery = "SELECT is_correct 
                                   FROM options 
                                   WHERE option_id = ? AND question_id = ?";
            $correctOptionStmt = $connection->prepare($correctOptionQuery);
            $correctOptionStmt->bind_param('ii', $submittedOptionId, $questionId);
            $correctOptionStmt->execute();
            $correctOptionStmt->bind_result($isCorrect);
            $correctOptionStmt->fetch();
            $correctOptionStmt->close();

            if ($isCorrect) {
                $correctAnswers++;
            }
        }
    }

    $score = ($correctAnswers / $totalQuestions) * 100;
    $status = $score >= 50 ? 'passed' : 'failed';
    $submittedAt = date('Y-m-d H:i:s');

    $submissionQuery = "INSERT INTO quiz_submissions 
                        (user_id, quiz_id, score, status, submitted_at) 
                        VALUES (?, ?, ?, ?, ?)";

    $submissionStmt = $connection->prepare($submissionQuery);
    $submissionStmt->bind_param('iisss', 
        $user_id, 
        $quiz_id, 
        $score, 
        $status, 
        $submittedAt
    );

    if ($submissionStmt->execute()) {

        $updateAssignmentQuery = "UPDATE quiz_assignments 
                                  SET status = 'completed' 
                                  WHERE user_id = ? AND quiz_id = ?";
        $updateAssignmentStmt = $connection->prepare($updateAssignmentQuery);
        $updateAssignmentStmt->bind_param('ii', $user_id, $quiz_id);
        $updateAssignmentStmt->execute();
        $updateAssignmentStmt->close();

        echo "<p>Quiz submitted successfully! Redirecting...</p>";
        echo "<script>setTimeout(function(){ window.location.href='employee.php'; },3000);</script>";
        exit;
    }

    $submissionStmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Quiz</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f9;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        .question {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #007BFF;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><?= htmlspecialchars($quiz_title) ?></h1>

    <form method="POST" id="quizForm">

        <?php $questionNumber = 1; ?>
        <?php foreach ($questions as $question): ?>
            <div class="question">
                <h4>Question <?= $questionNumber ?>: 
                    <?= htmlspecialchars($question['question_text']) ?>
                </h4>

                <?php foreach ($question['options'] as $option): ?>
                    <label>
                        <input type="radio"
                               name="question_<?= $question['question_id'] ?>"
                               value="<?= $option['option_id'] ?>">
                        <?= htmlspecialchars($option['option_text']) ?>
                    </label><br>
                <?php endforeach; ?>
            </div>
            <?php $questionNumber++; ?>
        <?php endforeach; ?>

        <button type="submit">Submit Quiz</button>
    </form>
</div>

<script>
document.getElementById('quizForm').addEventListener('submit', function(e) {

    let allAnswered = true;
    const questions = document.querySelectorAll('.question');

    questions.forEach(question => {

        const radios = question.querySelectorAll('input[type="radio"]');
        const answered = Array.from(radios).some(radio => radio.checked);

        if (!answered) {
            allAnswered = false;
            question.style.border = "2px solid red";
        } else {
            question.style.border = "1px solid #ddd";
        }
    });

    if (!allAnswered) {
        e.preventDefault();
        alert("Please answer all questions before submitting.");
    }
});
</script>

</body>
</html>
