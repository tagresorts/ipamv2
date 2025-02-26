<?php
session_start();
include 'config.php';

// Restrict deletion to admin users only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = $_GET['id'];

try {
    // Delete associated history log records
    $hlStmt = $pdo->prepare("DELETE FROM history_log WHERE ip_id = ?");
    $hlStmt->execute([$id]);

    // Delete associated custom fields
    $cfStmt = $pdo->prepare("DELETE FROM custom_fields WHERE ip_id = ?");
    $cfStmt->execute([$id]);

    // Delete the IP record
    $stmt = $pdo->prepare("DELETE FROM ips WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: dashboard.php");
    exit;
} catch (PDOException $e) {
    // Optionally log the error and show a user-friendly message
    echo "Error deleting record: " . $e->getMessage();
}
?>
