<?php
// get_blockhash.php
// Backend helper to fetch a recent blockhash from the Solana devnet RPC.

header('Content-Type: application/json');

// Your Solana devnet RPC endpoint (Triton/RPCPool)
//$rpcUrl = 'https://api.devnet.solana.com'; // Use Solana devnet for testing

// Update the RPC URL to use the custom ngrok-forwarded endpoint
$rpcUrl = 'https://supereminently-ghostlier-annalise.ngrok-free.dev';

$requestBody = json_encode([
    'jsonrpc' => '2.0',
    'id'      => 1,
    'method'  => 'getLatestBlockhash',
    'params'  => [],
]);

$ch = curl_init($rpcUrl);
if ($ch === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Failed to initialize cURL',
    ]);
    exit;
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => $requestBody,
    CURLOPT_TIMEOUT        => 15,
]);

$response = curl_exec($ch);

if ($response === false) {
    $err = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'RPC request failed: ' . $err,
    ]);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'RPC HTTP status ' . $httpCode,
    ]);
    exit;
}


$data = json_decode($response, true);
if (!isset($data['result']['value']['blockhash'], $data['result']['value']['lastValidBlockHeight'])) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid RPC response',
    ]);
    exit;
}

// Return a single JSON response
echo json_encode([
    'success'              => true,
    'blockhash'            => $data['result']['value']['blockhash'],
    'lastValidBlockHeight' => $data['result']['value']['lastValidBlockHeight'],
]);
