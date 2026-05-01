<?php
$dir = 'c:/Users/Productiv/Documents/visual studio code/website development project/code ignighter/ci4-app/app';
$oldUrl = 'https://cloud.daftartugasku.my.id/';
$newUrl = 'http://192.168.100.20:8080/';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, $oldUrl) !== false) {
            $newContent = str_replace($oldUrl, $newUrl, $content);
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done.\n";
