<?php

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

$wallet = $data["wallet"];
$signature = $data["signature"]; // array of bytes
$message = $data["message"];

$payload = [
    "wallet"    => $wallet,
    "signature" => $signature,
    "message"   => $message
];

$ch = curl_init("https://api.attestto.com/v1/verify");

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: " . "Bearer YOUR_ATTESTTO_API_KEY"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

// Decode API response
$result = json_decode($response, true);

// Handle result
if (!isset($result["valid"])) {
    echo "Attestto API error: " . $response;
    exit;
}

if ($result["valid"] === true) {
    // Store session / login success logic here
    echo "✅ Wallet verified successfully!";
} else {
    echo "❌ Wallet verification failed.";
}