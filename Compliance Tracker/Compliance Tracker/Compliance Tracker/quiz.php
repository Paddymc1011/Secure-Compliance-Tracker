<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'dbcon.php';
require_once 'auth.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $questions = $_POST['questions'] ?? [];

    if (empty($title) || empty($due_date) || empty($questions)) {
        die("All fields are required.");
    }

    // Insert the quiz
    $stmt = $connection->prepare("INSERT INTO quiz (title, due_date) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $due_date);
    $stmt->execute();
    $last_quiz_id = $connection->insert_id;
    $stmt->close();

    // Insert each question and its options
    foreach ($questions as $question) {
        $question_text = $question['text'] ?? '';
        $options = $question['options'] ?? [];
        $correct_option = $question['correct_option'] ?? null;

        if (empty($question_text) || count($options) !== 4 || $correct_option === null) {
            die("Each question must have text, 4 options, and a correct option.");
        }

        // Insert the question
        $stmt = $connection->prepare("INSERT INTO question (quiz_id, question_text) VALUES (?, ?)");
        $stmt->bind_param("is", $last_quiz_id, $question_text);
        $stmt->execute();
        $last_question_id = $connection->insert_id;
        $stmt->close();

        // Insert the options
        foreach ($options as $index => $option_text) {
            $is_correct = ($index == $correct_option) ? 1 : 0;
            $stmt = $connection->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $last_question_id, $option_text, $is_correct);
            $stmt->execute();
            $stmt->close();
        }
    }

    echo "<script>alert('Quiz created successfully!'); window.location.href = 'admin.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            color: #555;
        }
        input[type="text"], input[type="date"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .question {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .question h4 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create a New Quiz</h1>
        <form method="POST">
            <label for="title">Quiz Title:</label>
            <input type="text" name="title" id="title" required>

            <label for="due_date">Due Date:</label>
            <input type="date" name="due_date" id="due_date" required>

            <div id="questions-container">
                <h3>Questions</h3>
                <button type="button" onclick="addQuestion()">Add Question</button>
            </div>

            <button type="submit">Create Quiz</button>
        </form>
    </div>

    <script>
        let questionCount = 1; // Start with Question 1

        function addQuestion() {
            const container = document.getElementById('questions-container');

            const questionDiv = document.createElement('div');
            questionDiv.classList.add('question');
            questionDiv.innerHTML = `
                <h4>Question ${questionCount}</h4>
                <label>Question Text:</label>
                <input type="text" name="questions[${questionCount}][text]" required>

                <label>Options:</label>
                <input type="text" name="questions[${questionCount}][options][]" placeholder="Option 1" required>
                <input type="text" name="questions[${questionCount}][options][]" placeholder="Option 2" required>
                <input type="text" name="questions[${questionCount}][options][]" placeholder="Option 3" required>
                <input type="text" name="questions[${questionCount}][options][]" placeholder="Option 4" required>

                <label>Correct Option (0-3):</label>
                <input type="number" name="questions[${questionCount}][correct_option]" min="0" max="3" required>
            `;

            container.appendChild(questionDiv);
            questionCount++; // Increment question count after adding
        }

        // Automatically add the first question when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            addQuestion();
        });
    </script>
</body>
</html>