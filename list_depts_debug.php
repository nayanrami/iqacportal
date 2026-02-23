<?php
require 'includes/functions.php';
$stmt = $pdo->query('SELECT id, code, name FROM departments');
while($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | CODE: {$row['code']} | NAME: {$row['name']}\n";
}
