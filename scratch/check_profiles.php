<?php
require 'app/Config/Paths.php';
require 'system/bootstrap.php';
$db = \Config\Database::connect();
$users = $db->table('users')->get()->getResultArray();
echo json_encode($users, JSON_PRETTY_PRINT);
