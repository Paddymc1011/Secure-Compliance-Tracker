<?php
session_start();
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

// Ensure only admins can create quizzes
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quiz'])) {

    $title = trim($_POST['title'] ?? '');
    $due_date = $_POST['due_date'] ?? null;
    $questions = $_POST['questions'] ?? [];

    if ($title === '') {
        $creation_error = 'Quiz title is required.';
    } elseif (empty($questions)) {
        $creation_error = 'You must add at least one question.';
    } else {
        try {
            $connection->begin_transaction(); // Start transaction

            // Debug: Log the SQL query
            error_log('Executing SQL: INSERT INTO quiz (quiz_id, title, due_date, created_at) VALUES (' . $quiz_id . ', ' . $title . ', ' . $due_date . ', NOW())');

            // Create quiz
            $quiz_id = random_int(100000, 999999);

            $stmt = $connection->prepare(
                "INSERT INTO quiz (quiz_id, title, due_date, created_at)
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->bind_param("iss", $quiz_id, $title, $due_date);
            $stmt->execute();

            // Debug: Check for SQL errors
            if ($stmt->error) {
                error_log('SQL Error: ' . $stmt->error);
            }

            $stmt->close();

            // Debug: Log the questions array
            error_log('Questions: ' . var_export($questions, true));

            // Loop questions
            foreach ($questions as $qIndex => $questionText) {
                $questionText = trim($questionText);

                // Insert question into quiz_questions table
                $stmt = $connection->prepare(
                    "INSERT INTO quiz_questions (quiz_id, question_text)
                     VALUES (?, ?)"
                );
                $stmt->bind_param("is", $quiz_id, $questionText);
                $stmt->execute();

                if ($stmt->error) {
                    error_log('SQL Error (Quiz Questions): ' . $stmt->error);
                    throw new Exception('Failed to insert question into quiz_questions table.');
                }

                $question_id = $stmt->insert_id;
                $stmt->close();

                // Debug: Log the inserted question ID
                // Ensure question_id is valid before inserting options
                if (!$question_id) {
                    error_log('Error: No question_id generated for question: ' . $questionText);
                    throw new Exception('No question_id generated. Cannot insert options.');
                }
                error_log('Inserted Question ID: ' . $question_id);

                // Insert options for the question
                $options = $_POST['options_' . ($qIndex + 1)] ?? [];
                $correctIndex = $_POST['correct_' . ($qIndex + 1)] ?? null;

                foreach ($options as $optIndex => $optionText) {
                    $optionText = trim($optionText);
                    $isCorrect = ($optIndex == $correctIndex) ? 1 : 0;

                    $stmt = $connection->prepare(
                        "INSERT INTO options (question_id, option_text, is_correct)
                         VALUES (?, ?, ?)"
                    );
                    $stmt->bind_param("isi", $question_id, $optionText, $isCorrect);
                    $stmt->execute();

                    if ($stmt->error) {
                        error_log('SQL Error (Options): ' . $stmt->error);
                        throw new Exception('Failed to insert option into options table.');
                    }

                    // Debug: Log the inserted option details
                    error_log('Inserted Option: Question ID: ' . $question_id . ', Option: ' . $optionText . ', Is Correct: ' . $isCorrect);

                    $stmt->close();
                }
            }

            $connection->commit(); // Commit transaction

            $_SESSION['success_message'] = 'Quiz created successfully!';
            header('Location: admin.php');
            exit();

        } catch (Exception $e) {
            $connection->rollback(); // Rollback transaction on error

            // Debug: Log the exception message
            error_log('Exception: ' . $e->getMessage());

            $creation_error = 'Database error: ' . $e->getMessage();
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input[type="text"], input[type="date"], input[type="time"], input[type="datetime-local"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            resize: vertical;
        }

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .questions-container {
            margin-top: 20px;
        }

        .options-container {
            margin-top: 10px;
        }

        .options-container div {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            addQuestion(); // Automatically add the first question on page load
        });

        function addQuestion() {
            const questionsContainer = document.getElementById('questions-container');
            const questionCount = questionsContainer.children.length + 1;

            const questionDiv = document.createElement('div');
            questionDiv.classList.add('form-group');
            questionDiv.innerHTML = `
                <label for="question_${questionCount}">Question ${questionCount}</label>
                <input type="text" id="question_${questionCount}" name="questions[]" class="form-control" placeholder="Enter your question here" required>
                <div class="options-container">
                    <label>Options:</label>
                    <div>
                        <input type="radio" name="correct_${questionCount}" value="0" required>
                        <input type="text" name="options_${questionCount}[]" class="form-control" placeholder="Option 1" required>
                    </div>
                    <div>
                        <input type="radio" name="correct_${questionCount}" value="1" required>
                        <input type="text" name="options_${questionCount}[]" class="form-control" placeholder="Option 2" required>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addOption(this, ${questionCount})">Add Option</button>
                </div>
            `;

            questionsContainer.appendChild(questionDiv);
        }

        function addOption(button, questionIndex) {
            const optionsContainer = button.parentElement;
            const optionCount = optionsContainer.querySelectorAll('input[type="text"]').length + 1;

            const optionDiv = document.createElement('div');
            optionDiv.innerHTML = `
                <input type="radio" name="correct_${questionIndex}" value="${optionCount - 1}" required>
                <input type="text" name="options_${questionIndex}[]" class="form-control" placeholder="Option ${optionCount}" required>
            `;

            optionsContainer.insertBefore(optionDiv, button);
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Create a New Quiz</h1>

        <?php if (!empty($creation_error)): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($creation_error) ?> </div>
        <?php elseif (isset($success_message)): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success_message) ?> </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Quiz Title</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="Enter quiz title" required>
            </div>

            <div class="form-group">
                <label for="due_date">Due Date and Time</label>
                <input type="datetime-local" id="due_date" name="due_date" class="form-control" required>
            </div>

            <div id="questions-container" class="questions-container"></div>

            <button type="button" onclick="addQuestion()" class="btn btn-secondary">Add Question</button>
            <button type="submit" name="create_quiz" class="btn">Create Quiz</button>
        </form>
    </div>
</body>
</html>
