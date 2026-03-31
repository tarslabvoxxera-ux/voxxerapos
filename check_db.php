<?php
require_once __DIR__ . '/app/Config/Constants.php';
$db = \Config\Database::connect();
$fields = $db->getFieldNames('ospos_receivings');
print_r($fields);
