<?php
$db = \Config\Database::connect();
$fields = $db->getFieldNames('cashbon');
echo json_encode($fields);
