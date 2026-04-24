<?php
// confirm_register.php - Admin approval for pending user registrations

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Use main background styling for admin pages
$pageClass = 'main-bg';

require_once __DIR__ . '/dbcon.php';
require_once __DIR__ . '/auth.php';
require_role('admin');

$success = '';
$error = '';

// Handle approve / deny actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pending_id'], $_POST['action'])) {
    $pendingId = (int)$_POST['pending_id'];
    $action = $_POST['action'];

    // Fetch the pending registration row (including phone number so we can carry it into users)
    $stmt = $connection->prepare("SELECT id, user_id, username, phone_number, password, role, created_at FROM pending_registrations WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $pendingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $pending = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    } else {
        $pending = null;
    }

    if (!$pending) {
        $error = 'Pending registration not found or already processed.';
    } else {
        if ($action === 'approve') {
            // Move this record into the main users table, including phone_number
            try {
                $insert = $connection->prepare("INSERT INTO users (user_id, username, phone_number, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                if ($insert) {
                    $insert->bind_param(
                        'isssss',
                        $pending['user_id'],
                        $pending['username'],
                        $pending['phone_number'],
                        $pending['password'],
                        $pending['role'],
                        $pending['created_at']
                    );
                    if ($insert->execute()) {
                        $insert->close();
                        // Delete from pending table
                        $del = $connection->prepare("DELETE FROM pending_registrations WHERE id = ?");
                        if ($del) {
                            $del->bind_param('i', $pendingId);
                            $del->execute();
                            $del->close();
                        }
                        $success = 'User "' . htmlspecialchars($pending['username'], ENT_QUOTES, 'UTF-8') . '" has been approved and added to the system.';
                    } else {
                        $error = 'Failed to approve user: ' . $insert->error;
                        $insert->close();
                    }
                } else {
                    $error = 'Failed to prepare user insert: ' . $connection->error;
                }
            } catch (Exception $e) {
                $error = 'Error while approving user: ' . $e->getMessage();
            }
        } elseif ($action === 'deny') {
            // Simply delete from pending table
            $del = $connection->prepare("DELETE FROM pending_registrations WHERE id = ?");
            if ($del) {
                $del->bind_param('i', $pendingId);
                if ($del->execute()) {
                    $success = 'Pending registration for "' . htmlspecialchars($pending['username'], ENT_QUOTES, 'UTF-8') . '" has been denied and removed.';
                } else {
                    $error = 'Failed to deny registration: ' . $del->error;
                }
                $del->close();
            } else {
                $error = 'Failed to prepare delete statement: ' . $connection->error;
            }
        }
    }
}

// Fetch all pending registrations
$pendingRegistrations = [];
if ($connection) {
    // Include phone_number so we can display it in the table
    $sql = "SELECT id, user_id, username, phone_number, role, created_at FROM pending_registrations ORDER BY created_at DESC";
    if ($result = $connection->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $pendingRegistrations[] = $row;
        }
        $result->free();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:8px;">
    <section class="card">
        <h3>Pending Registrations</h3>
        <p>Approve or deny users before they are added to the system.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:12px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:12px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pendingRegistrations)): ?>
            <p>No pending registrations at this time.</p>
        <?php else: ?>
            <div class="padded-table-container">
                <div class="table-responsive">
                    <table class="table" role="table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">User ID</th>
                                <th scope="col">Username</th>
                                <th scope="col">Phone Number</th>
                                <th scope="col">Role</th>
                                <th scope="col">Requested At</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pendingRegistrations as $p): ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td><?= htmlspecialchars($p['user_id']) ?></td>
                                <td><?= htmlspecialchars($p['username']) ?></td>
                                <td><?= htmlspecialchars($p['phone_number'] ?? '') ?></td>
                                <td><?= htmlspecialchars($p['role']) ?></td>
                                <td><?= htmlspecialchars($p['created_at']) ?></td>
                                <td>
                                    <form method="post" action="confirm_register.php" style="display:inline-block; margin-right:6px;">
                                        <input type="hidden" name="pending_id" value="<?= (int)$p['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                    </form>
                                    <form method="post" action="confirm_register.php" style="display:inline-block;">
                                        <input type="hidden" name="pending_id" value="<?= (int)$p['id'] ?>">
                                        <input type="hidden" name="action" value="deny">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Deny this registration request?');">Deny</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
