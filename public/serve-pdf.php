<?php
/**
 * Simple direct PDF file server - no CodeIgniter framework overhead
 * Access: /serve-pdf.php?file=filename
 */

// Get filename from query string
$file = isset($_GET['file']) ? $_GET['file'] : null;

if (!$file) {
    http_response_code(400);
    die('Missing file parameter');
}

// Security: validate filename format (MD5 hash only)
if (!preg_match('/^[a-f0-9]{32}\.pdf$/', $file)) {
    http_response_code(400);
    die('Invalid file format');
}

$filePath = __DIR__ . '/writable/uploads/temp_pdf_cache/' . $file;

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Get file size
$fileSize = filesize($filePath);
if ($fileSize === false || $fileSize === 0) {
    http_response_code(500);
    die('Invalid file size');
}

// Set proper headers for PDF
header('Content-Type: application/pdf', true);
header('Content-Disposition: inline; filename="slip_gaji.pdf"', true);
header('Content-Length: ' . $fileSize, true);
header('Cache-Control: public, max-age=3600', true);
header('Pragma: public', true);
header('Accept-Ranges: bytes', true);
http_response_code(200);

// Read and output file
readfile($filePath);
exit();
