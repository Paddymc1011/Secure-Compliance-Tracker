<?php
// save_tx.php - store Solana transaction id for a quiz submission

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['txid'], $data['submission_id'])) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Invalid payload']);
	exit;
}

$txid = $data['txid'];
$submission_id = (int) $data['submission_id'];

require_once __DIR__ . '/dbcon.php';

if (!$connection) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Database connection failed']);
	exit;
}

$stmt = $connection->prepare("UPDATE quiz_submissions SET transaction_id = ? WHERE submission_id = ?");
$stmt->bind_param('si', $txid, $submission_id);

if ($stmt->execute()) {
	echo json_encode(['success' => true]);
} else {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
?>
