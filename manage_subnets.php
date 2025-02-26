<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/*********************************************************
 * Multi‑Tenancy: Fetch companies based on user role
 *********************************************************/
if ($_SESSION['role'] === 'admin') {
    $allCompanies = $pdo->query("SELECT * FROM companies ORDER BY company_name ASC")->fetchAll();
    $companiesForQuery = $pdo->query("SELECT company_id FROM companies")->fetchAll(PDO::FETCH_COLUMN);
} else {
    $stmt = $pdo->prepare("SELECT c.* FROM companies c JOIN user_companies uc ON c.company_id = uc.company_id WHERE uc.user_id = ? ORDER BY c.company_name ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $allCompanies = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT company_id FROM user_companies WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $companiesForQuery = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
if(empty($companiesForQuery)) {
    $companiesForQuery = [0];
}

/*********************************************************
 * Determine action: list, add, edit, delete
 *********************************************************/
$action = $_GET['action'] ?? 'list';
$error = '';

// ----------------------
// DELETE SUBNET
// ----------------------
if ($action === 'delete' && isset($_GET['id'])) {
    $subnet_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ips WHERE subnet_id = ?");
    $stmt->execute([$subnet_id]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $error = "Cannot delete subnet in use.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM subnets WHERE id = ?");
            $stmt->execute([$subnet_id]);
            header("Location: manage_subnets.php?action=list");
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting subnet: " . $e->getMessage();
        }
    }
}

// ----------------------
// ADD NEW SUBNET
// ----------------------
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    $subnet = $_POST['subnet'];
    $description = $_POST['description'];
    $vlan_id = $_POST['vlan_id'];
    $company_id = $_POST['company_id'];
    try {
        $stmt = $pdo->prepare("INSERT INTO subnets (subnet, description, vlan_id, created_by, company_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$subnet, $description, $vlan_id, $_SESSION['user_id'], $company_id]);
        header("Location: manage_subnets.php?action=list");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// ----------------------
// EDIT SUBNET
// ----------------------
if ($action === 'edit' && isset($_GET['id'])) {
    $subnet_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM subnets WHERE id = ?");
    $stmt->execute([$subnet_id]);
    $subnet_record = $stmt->fetch();
    if (!$subnet_record) {
        die("Subnet not found.");
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_subnet = $_POST['subnet'];
        $description = $_POST['description'];
        $vlan_id = $_POST['vlan_id'];
        $company_id = $_POST['company_id'];
        try {
            $stmt = $pdo->prepare("UPDATE subnets SET subnet = ?, description = ?, vlan_id = ?, company_id = ? WHERE id = ?");
            $stmt->execute([$new_subnet, $description, $vlan_id, $company_id, $subnet_id]);
            header("Location: manage_subnets.php?action=list");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ----------------------
// LIST SUBNETS
// ----------------------
if ($action === 'list') {
    if ($_SESSION['role'] === 'admin') {
        $companiesForQueryList = $pdo->query("SELECT company_id FROM companies")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $companiesForQueryList = $companiesForQuery;
    }
    $placeholders = implode(',', array_fill(0, count($companiesForQueryList), '?'));
    $stmt = $pdo->prepare("
        SELECT s.*, u.username AS creator, c.company_name, 
               (SELECT COUNT(*) FROM ips WHERE subnet_id = s.id) AS ip_count
        FROM subnets s
        LEFT JOIN users u ON s.created_by = u.id
        LEFT JOIN companies c ON s.company_id = c.company_id
        WHERE s.company_id IN ($placeholders)
        ORDER BY s.subnet ASC
    ");
    $stmt->execute($companiesForQueryList);
    $subnets = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subnets - IP Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <style>
      .container { width: 90%; max-width: 1200px; margin: 20px auto; }
      .form-group { margin-bottom: 20px; }
    </style>
</head>
<body>
  <div class="nav">
    <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
    <a href="manage_subnets.php?action=list" class="nav-btn">Subnet List</a>
    <a href="manage_subnets.php?action=add" class="nav-btn">Add New Subnet</a>
  </div>
  <div class="container">
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
      <h1>Subnet List</h1>
      <?php if (count($subnets) > 0): ?>
        <table>
          <tr>
            <th>Subnet</th>
            <th>Description</th>
            <th>VLAN ID</th>
            <th>Company</th>
            <th>Created By</th>
            <th>Usage</th>
            <th>Actions</th>
          </tr>
          <?php foreach ($subnets as $subnet): ?>
          <tr>
            <td><?= htmlspecialchars($subnet['subnet']) ?></td>
            <td><?= htmlspecialchars($subnet['description']) ?></td>
            <td><?= htmlspecialchars($subnet['vlan_id']) ?></td>
            <td><?= htmlspecialchars($subnet['company_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($subnet['creator']) ?></td>
            <td><?= $subnet['ip_count'] ?></td>
            <td>
              <a href="manage_subnets.php?action=edit&id=<?= $subnet['id'] ?>" class="btn">Edit</a>
              <?php if ($subnet['ip_count'] == 0): ?>
                <a href="manage_subnets.php?action=delete&id=<?= $subnet['id'] ?>" class="btn" onclick="return confirm('Are you sure you want to delete this subnet?');">Delete</a>
              <?php else: ?>
                <span style="color: #aaa;">Delete N/A</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p>No subnets found. <a href="manage_subnets.php?action=add">Add one first</a></p>
      <?php endif; ?>

    <?php elseif ($action === 'add'): ?>
      <h1>Add New Subnet</h1>
      <form method="POST">
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
          <label for="company_id">Company:</label>
          <select name="company_id" id="company_id" required>
            <option value="">-- Select Company --</option>
            <?php foreach($allCompanies as $company): ?>
              <option value="<?= $company['company_id'] ?>"><?= htmlspecialchars($company['company_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn">Add Subnet</button>
      </form>

    <?php elseif ($action === 'edit'): ?>
      <h1>Edit Subnet</h1>
      <?php if (!isset($subnet_record)) { die("Subnet not found."); } ?>
      <form method="POST">
        <div class="form-group">
          <label for="subnet">Subnet (CIDR):</label>
          <input type="text" id="subnet" name="subnet" value="<?= htmlspecialchars($subnet_record['subnet']) ?>" required>
        </div>
        <div class="form-group">
          <label for="description">Description:</label>
          <input type="text" id="description" name="description" value="<?= htmlspecialchars($subnet_record['description']) ?>">
        </div>
        <div class="form-group">
          <label for="vlan_id">VLAN ID:</label>
          <input type="text" id="vlan_id" name="vlan_id" value="<?= htmlspecialchars($subnet_record['vlan_id']) ?>">
        </div>
        <div class="form-group">
          <label for="company_id">Company:</label>
          <select name="company_id" id="company_id" required>
            <option value="">-- Select Company --</option>
            <?php foreach($allCompanies as $company): ?>
              <option value="<?= $company['company_id'] ?>" <?= ($subnet_record['company_id'] == $company['company_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($company['company_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn">Save Changes</button>
        <a href="manage_subnets.php?action=list" class="btn">Cancel</a>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
