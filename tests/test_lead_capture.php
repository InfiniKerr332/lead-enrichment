<?php

// Test script to capture a lead via API

$url = 'http://localhost/lead-enrichment-system/public/api/leads/capture';

$testLead = [
    'email' => 'test' . time() . '@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'company' => 'Acme Corp',
    'phone' => '+1234567890',
    'source' => 'test_script'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testLead));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
echo "Response: {$response}\n";

$data = json_decode($response, true);

if ($data && isset($data['success']) && $data['success']) {
    echo "\n✓ Lead captured successfully!\n";
    echo "Lead ID: {$data['lead_id']}\n";
} else {
    echo "\n✗ Failed to capture lead\n";
}