<?php
session_start();
require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';

require_role('admin');

if (isset($_GET['quiz_id'])) {
    $quiz_id = (int)$_GET['quiz_id'];

    try {
        $stmt = $connection->prepare("DELETE FROM create_quiz WHERE quiz_id = ?");
        $stmt->bind_param('i', $quiz_id);
        $stmt->execute();
        $stmt->close();

        // Redirect back to created_quizzes.php
        header('Location: created_quizzes.php');
        exit;
    } catch (Exception $e) {
        die('Failed to delete quiz: ' . $e->getMessage());
    }
} else {
    die('Invalid request.');
}
?>