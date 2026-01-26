<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_role('admin');

// Query users
$sql = "SELECT id, username, password, role, created_at FROM Securecompliancetracker.users ORDER BY id DESC";
$result = $connection->query($sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_export.csv');

$output = fopen('php://output', 'w');
// Header row
fputcsv($output, ['id', 'username', 'password', 'role', 'created_at']);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Ensure consistent order
        fputcsv($output, [
            $row['id'] ?? '',
            $row['username'] ?? '',
            $row['password'] ?? '',
            $row['role'] ?? '',
            $row['created_at'] ?? '',
        ]);
    }
}

fclose($output);
exit();
