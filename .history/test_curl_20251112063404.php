<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/send-reset-code.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'jet']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo 'HTTP Code: ' . $httpCode . PHP_EOL;
echo 'Response: ' . $response . PHP_EOL;
