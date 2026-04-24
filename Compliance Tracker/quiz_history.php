<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'dbcon.php';
require_once 'auth.php';

require_role('employee');

$user_id = $_SESSION['user_id'];

// Use the main background styling
$pageClass = 'main-bg';

// Fetch all quiz submissions for this user
$submissions = [];

if ($connection) {
    $query = "SELECT qs.submission_id,
                     qs.quiz_id,
                     qs.score,
                     qs.status,
                     qs.submitted_at,
                     qs.result_hash,
                     q.title,
                     q.category
              FROM quiz_submissions qs
              JOIN quiz q ON q.quiz_id = qs.quiz_id
              WHERE qs.user_id = ?
              ORDER BY qs.submitted_at DESC";

    if ($stmt = $connection->prepare($query)) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
        $stmt->close();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:900px;margin:20px auto;background:white;padding:20px;border-radius:8px;">
    <h2>Your Previous Quizzes and Results</h2>

    <p style="margin-top:10px;">
        <a href="employee.php" class="btn" style="display:inline-block;margin-right:10px;padding:8px 14px;background:#007BFF;color:#fff;border-radius:4px;text-decoration:none;">Back to Dashboard</a>
    </p>

    <?php if (empty($submissions)): ?>
        <p>You have not completed any quizzes yet.</p>
    <?php else: ?>
        <div style="overflow-x:auto;margin-top:15px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="border:1px solid #ddd;padding:8px;">Quiz Title</th>
                        <th style="border:1px solid #ddd;padding:8px;">Category</th>
                        <th style="border:1px solid #ddd;padding:8px;">Score (%)</th>
                        <th style="border:1px solid #ddd;padding:8px;">Status</th>
                        <th style="border:1px solid #ddd;padding:8px;">Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td style="border:1px solid #ddd;padding:8px;">
                                <?= htmlspecialchars($sub['title'] ?? 'Untitled Quiz') ?>
                            </td>
                            <td style="border:1px solid #ddd;padding:8px;">
                                <?= htmlspecialchars($sub['category'] ?? 'N/A') ?>
                            </td>
                            <td style="border:1px solid #ddd;padding:8px;">
                                <?= htmlspecialchars(number_format((float)$sub['score'], 2)) ?>
                            </td>
                            <td style="border:1px solid #ddd;padding:8px;">
                                <?= htmlspecialchars(ucfirst($sub['status'])) ?>
                            </td>
                            <td style="border:1px solid #ddd;padding:8px;">
                                <?= htmlspecialchars($sub['submitted_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
