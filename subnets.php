<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle subnet creation (admin-only)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    $subnet = $_POST['subnet'];
    $description = $_POST['description'];
    $vlan_id = $_POST['vlan_id'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO subnets 
            (subnet, description, vlan_id, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$subnet, $description, $vlan_id, $_SESSION['user_id']]);
        header("Location: subnets.php");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all subnets with creator info and count of IPs assigned
$subnets = $pdo->query("
    SELECT s.*, u.username AS creator, 
           (SELECT COUNT(*) FROM ips WHERE subnet_id = s.id) AS ip_count
    FROM subnets s
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.subnet ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Subnet Management - IP Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="style.css">
  <style>
    /* Fluid container */
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 20px auto;
    }
    /* Increase spacing in forms */
    .form-group {
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="nav">
    <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
  </div>

  <div class="container">
    <h1>Subnet Management</h1>
    <?php if ($_SESSION['role'] === 'admin'): ?>
      <h2>Create New Subnet</h2>
      <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <form method="POST" class="form-container">
        <div class="form-group">
          <label for="subnet">Subnet (CIDR):</label>
          <input type="text" id="subnet" name="subnet" placeholder="192.168.1.0/24" required>
        </div>
        
        <div class="form-group">
          <label for="description">Description:</label>
          <input type="text" id="description" name="description">
        </div>
        
        <div class="form-group">
          <label for="vlan_id">VLAN ID:</label>
          <input type="text" id="vlan_id" name="vlan_id">
        </div>
        
        <div class="form-group">
          <button type="submit" class="btn">Create Subnet</button>
        </div>
      </form>
    <?php endif; ?>

    <h2>Existing Subnets</h2>
    <?php if (count($subnets) > 0): ?>
      <table>
        <tr>
          <th>Subnet</th>
          <th>Description</th>
          <th>VLAN ID</th>
          <th>Created By</th>
          <th>Usage</th>
          <th>Actions</th>
        </tr>
        <?php foreach ($subnets as $subnet): ?>
        <tr>
          <td><?= htmlspecialchars($subnet['subnet']) ?></td>
          <td><?= htmlspecialchars($subnet['description']) ?></td>
          <td><?= htmlspecialchars($subnet['vlan_id']) ?></td>
          <td><?= htmlspecialchars($subnet['creator']) ?></td>
          <td><?= $subnet['ip_count'] ?></td>
          <td>
            <a href="edit_subnet.php?id=<?= $subnet['id'] ?>" class="btn">Edit</a>
            <?php if ($subnet['ip_count'] == 0): ?>
              <a href="delete_subnet.php?id=<?= $subnet['id'] ?>" class="btn" onclick="return confirm('Are you sure you want to delete this subnet?');">Delete</a>
            <?php else: ?>
              <span style="color: #aaa;">Delete N/A</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>No subnets found. <a href="subnets.php">Create one first</a></p>
    <?php endif; ?>
  </div>
</body>
</html>
