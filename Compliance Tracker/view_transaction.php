<?php
// view_transaction.php - display quiz submission and corresponding on-chain memo

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/dbcon.php';

$submissionId = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;

if ($submissionId <= 0) {
    http_response_code(400);
    echo '<h2>Invalid or missing submission_id.</h2>';
    exit;
}

// Fetch submission from database, including the on-chain data account reference
$stmt = $connection->prepare('SELECT user_id, quiz_id, score, status, result_hash, transaction_id, submitted_at, solana_account FROM quiz_submissions WHERE submission_id = ?');
$stmt->bind_param('i', $submissionId);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();
$stmt->close();

if (!$submission) {
    http_response_code(404);
    echo '<h2>No submission found for that ID.</h2>';
    exit;
}

$txid = $submission['transaction_id'] ?? '';
$solanaAccount = $submission['solana_account'] ?? '';

// Prepare variables for display
$userId     = htmlspecialchars($submission['user_id']);
$quizId     = htmlspecialchars($submission['quiz_id']);
$score      = htmlspecialchars($submission['score']);
$status     = htmlspecialchars($submission['status']);
$resultHash = htmlspecialchars($submission['result_hash']);
$submittedAt = htmlspecialchars($submission['submitted_at']);
$txidHtml   = htmlspecialchars($txid);

// Account-based on-chain verification via getAccountInfo
$accountData = null;
$accountError = null;

if (!empty($solanaAccount)) {
    $rpcUrl = 'https://supereminently-ghostlier-annalise.ngrok-free.dev';
    $payload = [
        'jsonrpc' => '2.0',
        'id'      => 1,
        'method'  => 'getAccountInfo',
        'params'  => [
            $solanaAccount,
            [
                'encoding'   => 'base64',
                'commitment' => 'confirmed',
            ],
        ],
    ];

    $ch = curl_init($rpcUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    if ($response === false) {
        $accountError = 'RPC request failed: ' . curl_error($ch);
    } else {
        $decoded = json_decode($response, true);
        if (isset($decoded['error'])) {
            $accountError = 'RPC error: ' . htmlspecialchars(json_encode($decoded['error']));
        } else {
            $accountData = $decoded['result']['value'] ?? null;
        }
    }

    curl_close($ch);
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Transaction for Submission <?php echo $submissionId; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px; }
        .card { background: #fff; border-radius: 8px; padding: 20px; max-width: 900px; margin: 0 auto 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; }
        pre { background: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow: auto; }
        .label { font-weight: bold; }
        .error { color: #c0392b; }
        .success { color: #27ae60; }
        .section-title { margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
    </style>
</head>
<body>

<div class="card">
    <h2 class="section-title">Quiz Submission Details</h2>
    <p><span class="label">Submission ID:</span> <?php echo $submissionId; ?></p>
    <p><span class="label">User ID:</span> <?php echo $userId; ?></p>
    <p><span class="label">Quiz ID:</span> <?php echo $quizId; ?></p>
    <p><span class="label">Score:</span> <?php echo $score; ?></p>
    <p><span class="label">Status:</span> <?php echo $status; ?></p>
    <p><span class="label">Result Hash:</span> <?php echo $resultHash; ?></p>
    <p><span class="label">Submitted At:</span> <?php echo $submittedAt; ?></p>
    <p><span class="label">Transaction ID (Signature):</span> <?php echo $txidHtml ?: '<em>Not yet recorded</em>'; ?></p>
    <p><span class="label">Solana Data Account:</span> <?php echo htmlspecialchars($solanaAccount ?: 'Not recorded'); ?></p>
</div>

<div class="card">
    <h2 class="section-title">On-chain Account Data (Local Validator)</h2>

    <?php if (!$solanaAccount): ?>
        <p class="error">No solana_account recorded yet for this submission.</p>
    <?php elseif ($accountError): ?>
        <p class="error"><?php echo $accountError; ?></p>
    <?php elseif (!$accountData): ?>
        <p class="error">No account data returned from RPC. The account may not exist yet or the RPC may not have data.</p>
    <?php else: ?>
        <?php
            // Decode base64 account data into UTF-8 text
            $onchainText = null;
            if (isset($accountData['data'][0]) && is_string($accountData['data'][0])) {
                $rawBytes = base64_decode($accountData['data'][0]);
                if ($rawBytes !== false) {
                    // Trim trailing nulls and whitespace, then interpret as UTF-8 text
                    $onchainText = rtrim($rawBytes, "\0\x0B\x0C\n\r\t ");
                }
            }

            // Try to interpret on-chain text as JSON with rich fields
            $onchainPayload = [
                'submission_id' => null,
                'user_id'       => null,
                'quiz_id'       => null,
                'score'         => null,
                'result_hash'   => null,
            ];

            if ($onchainText !== null && $onchainText !== '') {
                $decodedJson = json_decode($onchainText, true);
                if (is_array($decodedJson) && isset($decodedJson['result_hash'])) {
                    foreach ($onchainPayload as $key => $_) {
                        if (array_key_exists($key, $decodedJson)) {
                            $onchainPayload[$key] = $decodedJson[$key];
                        }
                    }
                }
            }

            $hasJson = $onchainPayload['result_hash'] !== null;
            $onchainResultHash = $hasJson ? (string) $onchainPayload['result_hash'] : $onchainText;

            $matchesResultHash = ($onchainResultHash !== null && $onchainResultHash === $submission['result_hash']);
            $matchesUserId = $hasJson && $onchainPayload['user_id'] !== null
                ? ((string) $onchainPayload['user_id'] === (string) $submission['user_id'])
                : null;
            $matchesQuizId = $hasJson && $onchainPayload['quiz_id'] !== null
                ? ((string) $onchainPayload['quiz_id'] === (string) $submission['quiz_id'])
                : null;
            $matchesScore = $hasJson && $onchainPayload['score'] !== null
                ? ((string) $onchainPayload['score'] === (string) $submission['score'])
                : null;
        ?>

        <p><span class="label">Decoded On-chain Text:</span></p>
        <pre><?php echo htmlspecialchars($onchainText ?? ''); ?></pre>

        <?php if ($hasJson): ?>
            <p><em>On-chain data decoded as JSON payload.</em></p>
        <?php else: ?>
            <p><em>On-chain data is treated as a plain result hash string.</em></p>
        <?php endif; ?>

        <h3>Verification Result:
            <?php if ($matchesResultHash): ?>
                <span class="success">VERIFIED ✅</span>
            <?php else: ?>
                <span class="error">MISMATCH ❌</span>
            <?php endif; ?>
        </h3>

        <table style="border-collapse:collapse; margin-bottom:1em; width:100%; table-layout:fixed;">
            <tr>
                <th style="border:1px solid #ccc; padding:4px; word-wrap:break-word;">Field</th>
                <th style="border:1px solid #ccc; padding:4px; word-wrap:break-word;">Local (DB)</th>
                <th style="border:1px solid #ccc; padding:4px; word-wrap:break-word;">On-chain</th>
                <th style="border:1px solid #ccc; padding:4px; word-wrap:break-word;">Match</th>
            </tr>
            <tr>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;">result_hash</td>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;"><?php echo htmlspecialchars($submission['result_hash']); ?></td>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;"><?php echo htmlspecialchars($onchainResultHash ?? ''); ?></td>
                <td style="border:1px solid #ccc; padding:4px; text-align:center; word-wrap:break-word;">
                    <?php echo $matchesResultHash ? '✅' : '❌'; ?>
                </td>
            </tr>
            <?php if ($hasJson): ?>
            <tr>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;">user_id</td>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;"><?php echo htmlspecialchars($submission['user_id']); ?></td>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;"><?php echo htmlspecialchars((string) $onchainPayload['user_id']); ?></td>
                <td style="border:1px solid #ccc; padding:4px; text-align:center; word-wrap:break-word;">
                    <?php echo $matchesUserId === null ? '-' : ($matchesUserId ? '✅' : '❌'); ?>
                </td>
            </tr>
            <tr>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;">quiz_id</td>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;"><?php echo htmlspecialchars($submission['quiz_id']); ?></td>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;"><?php echo htmlspecialchars((string) $onchainPayload['quiz_id']); ?></td>
                <td style="border:1px solid #ccc; padding:4px; text-align:center; word-wrap:break-word;">
                    <?php echo $matchesQuizId === null ? '-' : ($matchesQuizId ? '✅' : '❌'); ?>
                </td>
            </tr>
            <tr>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;">score</td>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;"><?php echo htmlspecialchars($submission['score']); ?></td>
                <td style="border:1px solid #ccc; padding:4px; word-wrap:break-word;"><?php echo htmlspecialchars((string) $onchainPayload['score']); ?></td>
                <td style="border:1px solid #ccc; padding:4px; text-align:center; word-wrap:break-word;">
                    <?php echo $matchesScore === null ? '-' : ($matchesScore ? '✅' : '❌'); ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>

    <?php endif; ?>
</div>

</body>
</html>
