<?php
require_once 'dbcon.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$submission_id = $data['submission_id'] ?? null;
$solana_account = $data['solana_account'] ?? null;
$tx_signature = $data['tx_signature'] ?? null;

if (!$submission_id || !$solana_account || !$tx_signature) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

if (!isset($connection) || !$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection not available.']);
    exit;
}

// NOTE: This reuses the existing transaction_id column to store the Solana
// transaction signature, so you don't need a separate tx_signature column.
// Ensure your quiz_submissions table has at least:
//   solana_account  VARCHAR(..) NULL
//   transaction_id  VARCHAR(..) NULL

$stmt = $connection->prepare("UPDATE quiz_submissions SET solana_account = ?, transaction_id = ? WHERE submission_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement.']);
    exit;
}

$stmt->bind_param('ssi', $solana_account, $tx_signature, $submission_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No rows updated. Check submission_id.']);
}
