<?php
session_start();
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_id'])) {
    $quiz_id = (int)$_POST['quiz_id'];

    // Debugging: Log the received quiz_id
    error_log("Received quiz_id: " . $quiz_id);

    try {
        $connection->begin_transaction();

        // Delete options associated with the quiz
        $stmt = $connection->prepare(
            "DELETE o FROM options o
             JOIN question q ON o.question_id = q.question_id
             WHERE q.quiz_id = ?"
        );
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        error_log("Deleted options for quiz_id: " . $quiz_id);
        $stmt->close();

        // Delete questions associated with the quiz
        $stmt = $connection->prepare("DELETE FROM question WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        error_log("Deleted questions for quiz_id: " . $quiz_id);
        $stmt->close();

        // Delete the quiz
        $stmt = $connection->prepare("DELETE FROM quiz WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        error_log("Deleted quiz with quiz_id: " . $quiz_id);
        $stmt->close();

        $connection->commit();

        header('Location: admin.php?deleted=1');
        exit();
    } catch (Exception $e) {
        $connection->rollback();
        error_log('Error deleting quiz: ' . $e->getMessage());
        header('Location: admin.php?error=' . urlencode('Failed to delete quiz.'));
        exit();
    }
}

header('Location: admin.php');
exit();
?>