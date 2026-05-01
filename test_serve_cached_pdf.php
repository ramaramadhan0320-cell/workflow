<?php
// Direct test of serveCachedPdf endpoint

echo "=== Testing serve-cached-pdf Endpoint ===\n";

// Get cache file from directory
$cacheDir = 'writable/uploads/temp_pdf_cache/';
$cacheFiles = array_diff(scandir($cacheDir), ['.', '..']);

if (empty($cacheFiles)) {
    echo "ERROR: No cache files found\n";
    exit(1);
}

$testFile = reset($cacheFiles);
echo "Test file: $testFile\n";
echo "File path: $cacheDir$testFile\n";
echo "File exists: " . (file_exists($cacheDir . $testFile) ? 'YES' : 'NO') . "\n";
echo "File size: " . filesize($cacheDir . $testFile) . " bytes\n";

// Test curl to the endpoint
$url = "http://192.168.2.6:8080/payment/serve-cached-pdf?file=" . urlencode($testFile);
echo "\nCurl test to: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_VERBOSE, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

echo "HTTP Code: $httpCode\n";
echo "Content-Type: $contentType\n";
echo "Header size: $headerSize bytes\n";

$header = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "Body size: " . strlen($body) . " bytes\n";
echo "Body empty: " . (empty($body) ? 'YES (PROBLEM!)' : 'NO') . "\n";

if ($httpCode === 204) {
    echo "\nERROR: Got 204 No Content - body is empty!\n";
    echo "This means response headers were sent but no body was sent.\n";
    echo "\nResponse headers:\n";
    echo $header;
} else if ($httpCode === 200) {
    echo "\nSUCCESS: Got 200 OK\n";
    if (strlen($body) > 0) {
        echo "PDF Header (first 4 bytes): " . bin2hex(substr($body, 0, 4)) . " (" . substr($body, 0, 4) . ")\n";
    }
} else {
    echo "\nERROR: Unexpected HTTP code $httpCode\n";
}

curl_close($ch);
