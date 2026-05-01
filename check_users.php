<?php
require 'app/Config/Database.php';

$db = \Config\Database::connect();

echo "=== DUMMY USERS ===\n";
$users = $db->table('users')->select('id, username')->get()->getResultArray();
foreach($users as $user) {
    echo "ID: {$user['id']} - Username: {$user['username']}\n";
}

echo "\n=== QR CODE FORMAT ===\n";
echo "Format: USER_ID|TIMESTAMP|LOCATION\n";
echo "Contoh: 1|2024-01-03 08:30:00|Office Main Entrance\n";
?>