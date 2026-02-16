<?php
// admin_delete_user.php
// Deletes a user by ID. Admin-only action.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

if (empty($_POST['user_id'])) {
    header('Location: admin.php?error=' . urlencode('Missing user id'));
    exit;
}

$user_id = (int) $_POST['user_id'];

// Prevent deleting yourself accidentally
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $user_id) {
    header('Location: admin.php?error=' . urlencode("You can't delete your own account while signed in."));
    exit;
}

try {
    // Use the existing DB connection variable from config.php (e.g. $connection)
    $stmt = $connection->prepare('DELETE FROM Securecompliancetracker.users WHERE id = ?');
    if ($stmt === false) {
        header('Location: admin.php?error=' . urlencode('DB prepare failed'));
        exit;
    }
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        // success
        header('Location: admin.php?deleted=1');
        exit;
    } else {
        header('Location: admin.php?error=' . urlencode('Delete failed'));
        exit;
    }
} catch (Exception $e) {
    header('Location: admin.php?error=' . urlencode('Exception: ' . $e->getMessage()));
    exit;
}
