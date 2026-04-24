<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

session_start();
require_once 'dbcon.php';
require_once 'auth.php';

require_role('employee');

$user_id = $_SESSION['user_id'];

// Track whether there is an assigned quiz so we can still render the page nicely when there isn't one
$hasQuiz = (bool)$connection;

if (!$connection) {
    // If the DB connection failed, just show a friendly no-quiz message instead of exiting.
    $hasQuiz = false;
}

// This page shows only Phishing quizzes
$category = 'phishing';

/* ---------------- FETCH ASSIGNED QUIZ FOR CATEGORY ---------------- */

$quizAssignmentQuery = "
    SELECT qa.quiz_id
    FROM quiz_assignments qa
    JOIN quiz q ON q.quiz_id = qa.quiz_id
    WHERE qa.user_id = ?
      AND qa.status = 'assigned'
      AND q.category = ?
    LIMIT 1
";
$quizAssignmentStmt = $connection->prepare($quizAssignmentQuery);
$quizAssignmentStmt->bind_param('is', $user_id, $category);
$quizAssignmentStmt->execute();
$quizAssignmentStmt->bind_result($quiz_id);
$quizAssignmentStmt->fetch();
$quizAssignmentStmt->close();

if (!$quiz_id) {
    $hasQuiz = false;
}

if ($hasQuiz) {

/* ---------------- FETCH QUIZ TITLE ---------------- */

$quizQuery = "SELECT title FROM quiz WHERE quiz_id = ?";
$quizStmt = $connection->prepare($quizQuery);
$quizStmt->bind_param('i', $quiz_id);
$quizStmt->execute();
$quizStmt->bind_result($quiz_title);
$quizStmt->fetch();
$quizStmt->close();

if (!$quiz_title) {
    $hasQuiz = false;
}

/* ---------------- FETCH QUESTIONS ---------------- */

$questionsQuery = "SELECT question_id, question_text 
                   FROM question WHERE quiz_id = ?";
$questionsStmt = $connection->prepare($questionsQuery);
$questionsStmt->bind_param('i', $quiz_id);
$questionsStmt->execute();
$questionsResult = $questionsStmt->get_result();

$questions = [];
while ($row = $questionsResult->fetch_assoc()) {
    $questions[] = $row;
}
$questionsStmt->close();

/* ---------------- FETCH OPTIONS ---------------- */

foreach ($questions as $i => $question) {
    // Only take the 4 most recently inserted options for each question to avoid showing old/stale options
    $optionsQuery = "SELECT option_id, option_text FROM (
                         SELECT option_id, option_text
                         FROM options
                         WHERE question_id = ?
                         ORDER BY option_id DESC
                         LIMIT 4
                     ) AS recent
                     ORDER BY option_id ASC";
    $optionsStmt = $connection->prepare($optionsQuery);
    $optionsStmt->bind_param('i', $question['question_id']);
    $optionsStmt->execute();
    $optionsResult = $optionsStmt->get_result();

    $questions[$i]['options'] = [];
    while ($option = $optionsResult->fetch_assoc()) {
        $questions[$i]['options'][] = $option;
    }
    $optionsStmt->close();
}

if (empty($questions)) {
    $hasQuiz = false;
}

} // end if ($hasQuiz)

/* ---------------- HANDLE SUBMISSION ---------------- */

if ($hasQuiz && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $correctAnswers = 0;
    $totalQuestions = count($questions);

    foreach ($questions as $question) {

        $questionId = $question['question_id'];
        $submittedOptionId = $_POST['question_' . $questionId] ?? null;

        if ($submittedOptionId) {

            $correctOptionQuery = "SELECT is_correct 
                                   FROM options 
                                   WHERE option_id = ? AND question_id = ?";
            $correctOptionStmt = $connection->prepare($correctOptionQuery);
            $correctOptionStmt->bind_param('ii', $submittedOptionId, $questionId);
            $correctOptionStmt->execute();
            $correctOptionStmt->bind_result($isCorrect);
            $correctOptionStmt->fetch();
            $correctOptionStmt->close();

            if ($isCorrect) {
                $correctAnswers++;
            }
        }
    }

    $score = ($correctAnswers / $totalQuestions) * 100;
    $status = $score >= 50 ? 'passed' : 'failed';
    $submittedAt = date('Y-m-d H:i:s');

    $submissionQuery = "INSERT INTO quiz_submissions 
                        (user_id, quiz_id, score, status, submitted_at) 
                        VALUES (?, ?, ?, ?, ?)";

    $submissionStmt = $connection->prepare($submissionQuery);
    $submissionStmt->bind_param('iisss', 
        $user_id, 
        $quiz_id, 
        $score, 
        $status, 
        $submittedAt
    );

    if ($submissionStmt->execute()) {

        // Generate result hash and update submission
        $submission_id = $connection->insert_id;
        $data_string = $submission_id . $user_id . $quiz_id . $score . $status . $submittedAt;
        $result_hash = hash('sha256', $data_string);

        $updateHashQuery = "UPDATE quiz_submissions SET result_hash = ? WHERE submission_id = ?";
        $updateHashStmt = $connection->prepare($updateHashQuery);
        $updateHashStmt->bind_param('si', $result_hash, $submission_id);
        $updateHashStmt->execute();
        $updateHashStmt->close();

        $updateAssignmentQuery = "UPDATE quiz_assignments 
                                  SET status = 'completed' 
                                  WHERE user_id = ? AND quiz_id = ?";
        $updateAssignmentStmt = $connection->prepare($updateAssignmentQuery);
        $updateAssignmentStmt->bind_param('ii', $user_id, $quiz_id);
        $updateAssignmentStmt->execute();
        $updateAssignmentStmt->close();

        // Render blockchain verification step similar to malwarequiz
        echo "<!DOCTYPE html>\n";
        echo "<html>\n<head>\n<meta charset=\"utf-8\">\n<title>Phishing Quiz - Blockchain Verification</title>\n";
        echo "<script src=\"https://unpkg.com/@solana/web3.js@1.95.0/lib/index.iife.min.js\"></script>\n";
    echo "</head>\n<body style=\"font-family: Arial; text-align:center; padding-top:50px; background: url('Images/mainbackground.png') no-repeat center center fixed; background-size: cover;\">\n";
    echo "<h2>Phishing Quiz Submitted Successfully</h2>\n";
    echo "<p>Please approve the transaction in your Backpack wallet to write the result hash into the on-chain account.</p>\n";
        echo "<div id=\"quiz-data\" data-result-hash=\"$result_hash\" data-submission-id=\"$submission_id\" data-user-id=\"$user_id\" data-quiz-id=\"$quiz_id\" data-score=\"$score\"></div>\n";
        echo "<button id=\"backpack-sign-btn\" style=\"margin-top:20px; padding:10px 20px; font-size:16px;\">Write Result Hash On-chain</button>\n";
        echo "<script>\n";
    echo "  (async () => {\n";
    echo "    const quizDataEl = document.getElementById('quiz-data');\n";
    echo "    const resultHash = quizDataEl ? quizDataEl.dataset.resultHash : null;\n";
    echo "    const submissionId = quizDataEl ? quizDataEl.dataset.submissionId : null;\n";
    echo "    const userId = quizDataEl ? quizDataEl.dataset.userId : null;\n";
    echo "    const quizId = quizDataEl ? quizDataEl.dataset.quizId : null;\n";
    echo "    const score = quizDataEl ? quizDataEl.dataset.score : null;\n";
    echo "    const signBtn = document.getElementById('backpack-sign-btn');\n";
        echo "    if (!signBtn) {\n";
        echo "      console.error('Sign button not found');\n";
        echo "      return;\n";
        echo "    }\n";
                echo "    const RPC_URL = 'https://supereminently-ghostlier-annalise.ngrok-free.dev';\n";
                    echo "    // Deployed Solana program ID for quiz_result_program\n";
                    echo "    const PROGRAM_ID = new solanaWeb3.PublicKey('G1MGHwD4wdsfgcLN6mRQjyxKoiDNTxDWS2XPLpGhwSxC');\n";
                echo "    const ACCOUNT_SPACE = 256;\n";
                echo "    let hasStarted = false;\n";
                echo "    const startOnchainFlow = async () => {\n";
                echo "      if (hasStarted) return;\n";
                echo "      hasStarted = true;\n";
                    echo "      // Only use Backpack; do not fall back to Phantom\n";
                    echo "      const provider = (window.backpack && window.backpack.solana)\n";
                    echo "        ? window.backpack.solana\n";
                    echo "        : null;\n";
                echo "      if (!provider) {\n";
                echo "        alert('Backpack or Phantom wallet not found.');\n";
                echo "        return;\n";
                echo "      }\n";
                echo "      if (!resultHash || !submissionId || !userId || !quizId || !score) {\n";
                echo "        alert('Missing quiz metadata for on-chain write.');\n";
                echo "        return;\n";
                echo "      }\n";
                echo "      try {\n";
                echo "        await provider.connect();\n";
                echo "        const publicKey = provider.publicKey;\n";
                echo "        const connection = new solanaWeb3.Connection(RPC_URL, 'confirmed');\n";
                echo "        const dataAccount = solanaWeb3.Keypair.generate();\n";
                echo "        const lamports = await connection.getMinimumBalanceForRentExemption(ACCOUNT_SPACE);\n";
                echo "        const createIx = solanaWeb3.SystemProgram.createAccount({\n";
                echo "          fromPubkey: publicKey,\n";
                echo "          newAccountPubkey: dataAccount.publicKey,\n";
                echo "          space: ACCOUNT_SPACE,\n";
                echo "          lamports,\n";
                echo "          programId: PROGRAM_ID,\n";
                echo "        });\n";
                echo "        const payload = {\n";
                echo "          submission_id: Number(submissionId),\n";
                echo "          user_id: Number(userId),\n";
                echo "          quiz_id: Number(quizId),\n";
                echo "          score: Number(score),\n";
                echo "          result_hash: resultHash,\n";
                echo "        };\n";
                echo "        const payloadStr = JSON.stringify(payload);\n";
                echo "        const dataBytes = new TextEncoder().encode(payloadStr);\n";
                echo "        if (dataBytes.length > ACCOUNT_SPACE) {\n";
                echo "          throw new Error('On-chain payload is too large for allocated account space');\n";
                echo "        }\n";
                echo "        const programIx = new solanaWeb3.TransactionInstruction({\n";
                echo "          programId: PROGRAM_ID,\n";
                echo "          keys: [\n";
                echo "            { pubkey: dataAccount.publicKey, isSigner: false, isWritable: true },\n";
                echo "          ],\n";
                echo "          data: dataBytes,\n";
                echo "        });\n";
                echo "        const { blockhash, lastValidBlockHeight } = await connection.getLatestBlockhash();\n";
                echo "        const transaction = new solanaWeb3.Transaction({\n";
                echo "          recentBlockhash: blockhash,\n";
                echo "          feePayer: publicKey,\n";
                echo "        });\n";
                echo "        transaction.add(createIx, programIx);\n";
                echo "        transaction.partialSign(dataAccount);\n";
                echo "        const signedTx = await provider.signTransaction(transaction);\n";
                echo "        const serializedTx = signedTx.serialize();\n";
                echo "        const txSignature = await connection.sendRawTransaction(serializedTx, { skipPreflight: false });\n";
                echo "        console.log('On-chain tx signature:', txSignature);\n";
                echo "        // Try to confirm the transaction; if this fails due to block height or timeout,\n";
                echo "        // log the error but still continue to save the reference in the database.\n";
                echo "        try {\n";
                echo "          await connection.confirmTransaction(\n";
                echo "            { signature: txSignature, blockhash, lastValidBlockHeight },\n";
                echo "            'confirmed'\n";
                echo "          );\n";
                echo "        } catch (confirmErr) {\n";
                echo "          console.warn('Transaction confirmation error (continuing to save reference anyway):', confirmErr);\n";
                echo "        }\n";
                echo "        try {\n";
                echo "          const resp = await fetch('save_onchain_ref.php', {\n";
                echo "            method: 'POST',\n";
                echo "            headers: { 'Content-Type': 'application/json' },\n";
                echo "            body: JSON.stringify({\n";
                echo "              submission_id: submissionId,\n";
                echo "              solana_account: dataAccount.publicKey.toBase58(),\n";
                echo "              tx_signature: txSignature,\n";
                echo "            }),\n";
                echo "          });\n";
                echo "          const result = await resp.json().catch(() => null);\n";
                echo "          if (!resp.ok || !result || !result.success) {\n";
                echo "            console.error('Failed to save on-chain reference', result);\n";
                echo "            alert('On-chain write succeeded, but saving the reference to the server failed. Signature: ' + txSignature);\n";
                echo "          } else {\n";
                echo "            alert('On-chain write and server save succeeded! Signature: ' + txSignature);\n";
                echo "          }\n";
                echo "        } catch (saveErr) {\n";
                echo "          console.error('Error while calling save_onchain_ref.php:', saveErr);\n";
                echo "          alert('On-chain write succeeded, but there was an error saving the reference: ' + (saveErr.message || String(saveErr)) + '\\nSignature: ' + txSignature);\n";
                echo "        }\n";
                echo "      } catch (err) {\n";
                echo "        console.error('Backpack transaction error:', err);\n";
                echo "        alert('Error during on-chain write: ' + (err.message || String(err)));\n";
                echo "      }\n";
                echo "    }\n";
                echo "    // Only start on-chain write when user clicks the button\n";
                echo "    signBtn.addEventListener('click', startOnchainFlow);\n";
                echo "  })();\n";
        echo "</script>\n";
        echo "</body>\n</html>\n";

        exit;
    }

    $submissionStmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Quiz</title>
    <style>
        body {
            font-family: Arial;
            background: url('Images/mainbackground.png') no-repeat center center fixed;
            background-size: cover;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        .question {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #007BFF;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .no-quiz-table {
            margin: 40px auto;
            border-collapse: collapse;
            background: #fff;
        }
        .no-quiz-table td {
            border: 1px solid #ccc;
            padding: 20px 30px;
            text-align: center;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="container">
    <?php if (!empty($hasQuiz) && !empty($questions)): ?>
        <h1><?= htmlspecialchars($quiz_title) ?></h1>

        <form method="POST" id="quizForm">

            <?php $questionNumber = 1; ?>
            <?php foreach ($questions as $question): ?>
                <div class="question">
                    <h4>Question <?= $questionNumber ?>: 
                        <?= htmlspecialchars($question['question_text']) ?>
                    </h4>

                    <?php foreach ($question['options'] as $option): ?>
                        <label>
                            <input type="radio"
                                   name="question_<?= $question['question_id'] ?>"
                                   value="<?= $option['option_id'] ?>">
                            <?= htmlspecialchars($option['option_text']) ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
                <?php $questionNumber++; ?>
            <?php endforeach; ?>

            <button type="submit">Submit Quiz</button>
        </form>
    <?php else: ?>
        <table class="no-quiz-table">
            <tr>
                <td>No quizzes have been assigned to you for this category.</td>
            </tr>
        </table>
    <?php endif; ?>
</div>

<script>
document.getElementById('quizForm').addEventListener('submit', function(e) {

    let allAnswered = true;
    const questions = document.querySelectorAll('.question');

    questions.forEach(question => {

        const radios = question.querySelectorAll('input[type="radio"]');
        const answered = Array.from(radios).some(radio => radio.checked);

        if (!answered) {
            allAnswered = false;
            question.style.border = "2px solid red";
        } else {
            question.style.border = "1px solid #ddd";
        }
    });

    if (!allAnswered) {
        e.preventDefault();
        alert("Please answer all questions before submitting.");
    }
});
</script>

</body>
</html>
