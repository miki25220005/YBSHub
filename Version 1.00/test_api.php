<?php
header('Content-Type: application/json');

// သင်၏ API Key ကို ထည့်ပါ
$apiKey = "AIzaSyBqmvIm8UiI3TnnL6VJWw6aBQ52G8q5WQI"; 

// Model များကို စစ်ဆေးမည့် URL
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo json_encode([
        "status" => "Success",
        "available_models" => $data['models']
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        "status" => "Error",
        "code" => $httpCode,
        "message" => $response
    ], JSON_PRETTY_PRINT);
}
?>