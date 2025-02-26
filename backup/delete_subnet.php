<?php
session_start();
include 'config.php';

// Restrict deletion to admin users only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: subnets.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: subnets.php");
    exit;
}

$id = $_GET['id'];

// Check if subnet has any IP usage
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ips WHERE subnet_id = ?");
$stmt->execute([$id]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    // Subnet in use, cannot delete
    header("Location: subnets.php");
    exit;
}

try {
    // Delete the subnet
    $stmt = $pdo->prepare("DELETE FROM subnets WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: subnets.php");
    exit;
} catch (PDOException $e) {
    echo "Error deleting subnet: " . $e->getMessage();
}
?>
