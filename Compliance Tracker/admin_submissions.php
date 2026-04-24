<?php
// admin_submissions.php - list quiz submissions with quick links to on-chain verification

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Use the main background on the submissions overview page
$pageClass = 'main-bg';

require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';
require_role('admin');

// Fetch recent submissions with basic info and whether an on-chain account is recorded
$submissions = [];
if ($connection) {
    $sql = "SELECT submission_id, user_id, quiz_id, score, status, submitted_at, solana_account, transaction_id
            FROM quiz_submissions
            ORDER BY submitted_at DESC
            LIMIT 200";
    if ($result = $connection->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
        $result->free();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:8px;">
    <section class="card">
        <h3>Quiz Submissions</h3>
        <p>Click "View / Verify" to open the submission details and on-chain verification page.</p>

        <?php if (empty($submissions)): ?>
            <p>No submissions found.</p>
        <?php else: ?>
            <div class="padded-table-container">
                <div class="table-responsive">
                    <table class="table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">Submission ID</th>
                                <th scope="col">User ID</th>
                                <th scope="col">Quiz ID</th>
                                <th scope="col">Score</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted At</th>
                                <th scope="col">On-chain Account</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($submissions as $s): ?>
                            <tr>
                                <td><?= (int)$s['submission_id'] ?></td>
                                <td><?= htmlspecialchars($s['user_id']) ?></td>
                                <td><?= htmlspecialchars($s['quiz_id']) ?></td>
                                <td><?= htmlspecialchars($s['score']) ?></td>
                                <td><?= htmlspecialchars($s['status']) ?></td>
                                <td><?= htmlspecialchars($s['submitted_at']) ?></td>
                                <td>
                                    <?php if (!empty($s['solana_account'])): ?>
                                        <span title="<?= htmlspecialchars($s['solana_account']) ?>">
                                            <?= htmlspecialchars(substr($s['solana_account'], 0, 8)) ?>...
                                        </span>
                                    <?php else: ?>
                                        <em>Not recorded</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-primary" href="view_transaction.php?submission_id=<?= (int)$s['submission_id'] ?>" target="_blank">View / Verify</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top:12px;">
            <a href="admin.php" class="btn">Back to Admin Dashboard</a>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
