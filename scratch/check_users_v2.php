<?php
require 'app/Config/Paths.php';
$paths = new \Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';
$db = \Config\Database::connect();
$users = $db->table('users')->get()->getResultArray();
foreach ($users as $user) {
    echo "ID: {$user['id']} | Name: {$user['username']} | Profile: {$user['profile']}\n";
}
