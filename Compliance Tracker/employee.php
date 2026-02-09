<?php
session_start();

// Handle logout (when the logout button posts)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// If the user is not logged in, redirect to login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<div class="container employee-page" style="margin-top:20px;">
    <div class="login-card" style="max-width:720px;margin:0 auto;">
        <h2>Welcome, <?php echo htmlspecialchars($username ?: 'User'); ?></h2>

        <!-- Compliance Quiz placeholder (content coming soon) -->
        <section style="margin-top:18px;">
            <h3>Compliance Quiz</h3>
            <p class="muted">This area will show assigned compliance quizzes. Content coming soon.</p>
        </section>

        <!-- Categories: at least 3 separate categories -->
        <section style="margin-top:18px;">
            <h3>Categories</h3>
            <p class="muted">Quick links to common compliance areas.</p>

            <div class="categories" style="margin-top:12px;display:grid;grid-template-columns:repeat(2, 1fr);gap:12px;">
                <div class="category-card card">
                    <h4>Malware And Ransomware Attacks</h4>
                    <p class="muted">Please check for new Quizzes Daily</p>
                    <p style="margin-top:10px"><a class="btn btn-primary" href="malwarequiz.php">Open</a></p>
                </div>

                <div class="category-card card">
                    <h4>Phishing Attacks</h4>
                    <p class="muted">Please check for new Quizzes Daily</p>
                    <p style="margin-top:10px"><a class="btn btn-primary" href="phishingquiz.php">Open</a></p>
                </div>

                <div class="category-card card">
                    <h4>Emerging Threats</h4>
                    <p class="muted">Please check for new Quizzes Daily</p>
                    <p style="margin-top:10px"><a class="btn btn-primary" href="emergingthreatsquiz.php">Open</a></p>
                </div>

                <div class="category-card card">
                    <h4>IoT Attacks</h4>
                    <p class="muted">Please check for new Quizzes Daily</p>
                    <p style="margin-top:10px"><a class="btn btn-primary" href="iotattackquiz.php">Open</a></p>
                </div>
            </div>
        </section>

        <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap;">
            <form method="post" style="display:inline">
                <button type="submit" name="logout" class="btn">Logout</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
