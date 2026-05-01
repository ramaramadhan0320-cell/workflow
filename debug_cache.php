<?php
// Debug script untuk check status serve-cached-pdf

echo "=== Cache File Status Check ===\n\n";

// List semua cache files
$cacheDir = 'writable/uploads/temp_pdf_cache/';
echo "Cache directory: $cacheDir\n";
echo "Directory exists: " . (is_dir($cacheDir) ? 'YES' : 'NO') . "\n";

if (is_dir($cacheDir)) {
    $files = array_diff(scandir($cacheDir), ['.', '..']);
    echo "Total cache files: " . count($files) . "\n\n";
    
    if (!empty($files)) {
        echo "Cache Files:\n";
        foreach ($files as $file) {
            $filePath = $cacheDir . $file;
            $size = filesize($filePath);
            $time = filemtime($filePath);
            echo "  - $file\n";
            echo "    Size: $size bytes\n";
            echo "    Modified: " . date('Y-m-d H:i:s', $time) . "\n";
            echo "    Readable: " . (is_readable($filePath) ? 'YES' : 'NO') . "\n";
        }
    }
} else {
    echo "Cache directory does not exist!\n";
}

// Check recent log entries
echo "\n=== Recent Log Entries ===\n";
$logFile = 'writable/logs/log-' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $lines = file_tail($logFile, 30);
    foreach (array_filter($lines) as $line) {
        if (strpos($line, 'serveCachedPdf') !== false || strpos($line, 'Cache file') !== false) {
            echo trim($line) . "\n";
        }
    }
} else {
    echo "No log file found\n";
}

function file_tail($file, $lines) {
    $handle = fopen($file, 'r');
    $linecounter = 0;
    $pos = -2;
    $beginning = false;
    $text = [];
    
    while ($linecounter < $lines) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter++;
        if ($beginning) break;
        $text[] = fgets($handle);
    }
    fclose($handle);
    return array_reverse($text);
}
