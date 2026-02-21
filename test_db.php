<?php
try {
  $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
  echo 'Connection OK with empty password';
} catch(Exception $e) {
  echo 'Empty pass failed: ' . $e->getMessage() . PHP_EOL;
}
try {
  $pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
  echo 'Connection OK with 127.0.0.1';
} catch(Exception $e) {
  echo '127.0.0.1 failed: ' . $e->getMessage() . PHP_EOL;
}
try {
  $pdo = new PDO('mysql:host=localhost;port=3306;charset=utf8mb4', 'root', '');
  echo 'Connection OK with port 3306';
} catch(Exception $e) {
  echo 'Port 3306 failed: ' . $e->getMessage() . PHP_EOL;
}
?>
