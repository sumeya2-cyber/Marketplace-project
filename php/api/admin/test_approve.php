<?php
// test_approve.php
$url = 'http://localhost/MarketPlace/php/api/admin/approve_listing.php';
$data = json_encode(['type' => 'product', 'id' => '1', 'action' => 'approve']);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);
echo $response;
?>