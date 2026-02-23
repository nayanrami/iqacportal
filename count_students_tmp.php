<?php
require 'includes/functions.php';
echo $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
