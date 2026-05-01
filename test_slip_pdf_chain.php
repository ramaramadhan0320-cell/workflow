<?php
// Test complete chain: get-slip-data -> get-slip-pdf

echo "=== Testing GET-SLIP-PDF Chain ===\n";

// Simulate session
session_start();
$_SESSION['isLoggedIn'] = true;  // Simulate logged-in user
$_SESSION['user_id'] = 1;

// Test 1: get-slip-data endpoint
echo "\n1. Testing /payment/get-slip-data\n";
$ch1 = curl_init('http://192.168.2.6:8080/payment/get-slip-data');
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_HEADER, false);
curl_setopt($ch1, CURLOPT_TIMEOUT, 10);
curl_setopt($ch1, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode1\n";

if ($httpCode1 == 200) {
    $data = json_decode($response1, true);
    echo "Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    if ($data['status'] == 200 && isset($data['data']['filename'])) {
        $filename = $data['data']['filename'];
        echo "\nFilename: $filename\n";
        
        // Test 2: get-slip-pdf endpoint
        echo "\n2. Testing /payment/get-slip-pdf?filename=$filename\n";
        $ch2 = curl_init('http://192.168.2.6:8080/payment/get-slip-pdf?filename=' . urlencode($filename));
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HEADER, false);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch2, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
        
        $response2 = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        
        echo "HTTP Code: $httpCode2\n";
        
        if ($httpCode2 == 200) {
            $pdfData = json_decode($response2, true);
            echo "Response:\n";
            echo json_encode($pdfData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            
            if ($pdfData['status'] == 200) {
                if (isset($pdfData['data']['pdf_url'])) {
                    echo "\nPDF URL: " . $pdfData['data']['pdf_url'] . "\n";
                    echo "✅ PDF URL ready for PDF.js to load!\n";
                } else {
                    echo "❌ No PDF URL in response\n";
                }
            } else {
                echo "❌ Get slip data failed\n";
            }
        } else {
            echo "Response: " . substr($response2, 0, 200) . "\n";
        }
        
        curl_close($ch2);
    } else {
        echo "❌ No filename in slip data response\n";
    }
} else {
    echo "Response: " . substr($response1, 0, 200) . "\n";
}

curl_close($ch1);
