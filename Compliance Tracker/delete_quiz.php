<?php
session_start();
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_id'])) {
    $quiz_id = (int)$_POST['quiz_id'];

    try {
        $connection->begin_transaction();

        // Delete options associated with the quiz
        $stmt = $connection->prepare(
            "DELETE o FROM options o
             JOIN quiz_questions qq ON o.question_id = qq.id
             WHERE qq.quiz_id = ?"
        );
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $stmt->close();

        // Delete questions associated with the quiz
        $stmt = $connection->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $stmt->close();

        // Delete the quiz
        $stmt = $connection->prepare("DELETE FROM create_quiz WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
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