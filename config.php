<?php
$db_host = 'localhost';
$db_name = 'ip_management';
$db_user = 'ipadmin';
$db_pass = 'StrongPassword123!';

try {
  $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}
?>
