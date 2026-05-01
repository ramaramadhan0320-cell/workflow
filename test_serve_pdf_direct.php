<?php
// Test serve-pdf.php endpoint

echo "=== Testing serve-pdf.php Endpoint ===\n";

$cacheDir = 'writable/uploads/temp_pdf_cache/';
$files = array_diff(scandir($cacheDir), ['.', '..']);

if (empty($files)) {
    echo "ERROR: No cache files found\n";
    exit(1);
}

$testFile = reset($files);
echo "Test file: $testFile\n";
echo "File path: $cacheDir$testFile\n";
echo "File size: " . filesize($cacheDir . $testFile) . " bytes\n\n";

// Test curl to the serve-pdf.php endpoint
$url = "http://192.168.2.6:8080/serve-pdf.php?file=" . urlencode($testFile);
echo "Testing URL: $url\n\n";

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

if ($httpCode === 200) {
    echo "\n✅ SUCCESS: Got 200 OK\n";
    if (strlen($body) > 0) {
        echo "PDF Header (first 4 bytes): " . bin2hex(substr($body, 0, 4)) . " (" . substr($body, 0, 4) . ")\n";
        if (substr($body, 0, 4) === '%PDF') {
            echo "✅ Valid PDF header!\n";
        }
    }
} else {
    echo "\n❌ ERROR: Got HTTP $httpCode\n";
    echo "\nResponse headers:\n";
    echo $header;
}

curl_close($ch);
