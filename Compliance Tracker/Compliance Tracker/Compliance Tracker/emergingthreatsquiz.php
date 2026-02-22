<?php
session_start();

// Require login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? '';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:20px;">
    <div class="card">
        <h2>Emerging Threats — Quiz</h2>
        <p class="muted">This quiz covers emerging threats and trends. Complete it to update your awareness and skills.</p>

        <p>No quiz available yet. Contact your administrator to assign this quiz.</p>

        <p style="margin-top:12px"><a href="employee.php" class="btn btn-primary">Back to Dashboard</a></p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
