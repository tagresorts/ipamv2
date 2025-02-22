<?php
session_start();
include 'config.php';

// Restrict access to admin users only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Check for the IP record id in GET parameters
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$ip_id = $_GET['id'];

// Retrieve the IP record from the database
$stmt = $pdo->prepare("SELECT * FROM ips WHERE id = ?");
$stmt->execute([$ip_id]);
$ip_record = $stmt->fetch();

if (!$ip_record) {
    // If record doesn't exist, redirect with an error message
    header("Location: dashboard.php");
    exit;
}

// Fetch subnets for the dropdown list
$subnets = $pdo->query("SELECT * FROM subnets")->fetchAll();

// Handle form submission to update the record
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve updated fields from the form
    $ip_address   = $_POST['ip_address'];
    $subnet_id    = $_POST['subnet_id'];
    $status       = $_POST['status'];
    $description  = $_POST['description'];
    $assigned_to  = $_POST['assigned_to'];
    $owner        = $_POST['owner'];
    $type         = $_POST['type'];
    $location     = $_POST['location'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE ips 
            SET ip_address = ?, subnet_id = ?, status = ?, description = ?, assigned_to = ?, owner = ?, type = ?, location = ?
            WHERE id = ?
        ");
        $stmt->execute([$ip_address, $subnet_id, $status, $description, $assigned_to, $owner, $type, $location, $ip_id]);
        header("Location: dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit IP - IP Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
    <div class="container">
        <h1>Edit IP Record</h1>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="ip_address">IP Address:</label>
                <input type="text" id="ip_address" name="ip_address" value="<?= htmlspecialchars($ip_record['ip_address']) ?>" required>
            </div>
            <div class="form-group">
                <label for="subnet_id">Subnet:</label>
                <select name="subnet_id" id="subnet_id">
                    <?php foreach ($subnets as $subnet): ?>
                        <option value="<?= $subnet['id'] ?>" <?= ($subnet['id'] == $ip_record['subnet_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subnet['subnet']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="Available" <?= $ip_record['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                    <option value="Reserved" <?= $ip_record['status'] === 'Reserved' ? 'selected' : '' ?>>Reserved</option>
                    <option value="Assigned" <?= $ip_record['status'] === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                    <option value="Expired" <?= $ip_record['status'] === 'Expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <input type="text" id="description" name="description" value="<?= htmlspecialchars($ip_record['description']) ?>">
            </div>
            <div class="form-group">
                <label for="assigned_to">Assigned To:</label>
                <input type="text" id="assigned_to" name="assigned_to" value="<?= htmlspecialchars($ip_record['assigned_to']) ?>">
            </div>
            <div class="form-group">
                <label for="owner">Owner:</label>
                <input type="text" id="owner" name="owner" value="<?= htmlspecialchars($ip_record['owner']) ?>">
            </div>
            <div class="form-group">
                <label for="type">Type (Hardware):</label>
                <input type="text" id="type" name="type" value="<?= htmlspecialchars($ip_record['type']) ?>">
            </div>
            <div class="form-group">
                <label for="location">Location (Deployment Site):</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($ip_record['location']) ?>">
            </div>
            <button type="submit" class="btn">Save Changes</button>
            <a href="dashboard.php" class="btn">Cancel</a>
        </form>
    </div>
</body>
</html>
