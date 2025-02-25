<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/*********************************************************
 * Multi‑Tenancy: Fetch company IDs associated with the logged‑in user
 *********************************************************/
$stmt = $pdo->prepare("SELECT company_id FROM user_companies WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$companyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($companyIds)) {
    // If the user isn't associated with any company, set to 0 to return no results.
    $companyIds = [0];
}

/*********************************************************
 * Capture Filter Criteria from GET (if provided)
 *********************************************************/
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

/*********************************************************
 * Build Dynamic SQL Query Based on Filters & Multi‑Tenancy
 *********************************************************/
$sql = "SELECT
           ips.id,
           ips.ip_address,
           ips.status,
           ips.assigned_to,
           ips.owner,
           ips.description,
           ips.type,
           ips.location,
           ips.created_at,
           ips.last_updated,
           subnets.subnet,
           u.username AS created_by_username,
           c.company_name,
           (
             SELECT GROUP_CONCAT(CONCAT(custom_fields.field_name, ': ', custom_fields.field_value) SEPARATOR '; ')
             FROM custom_fields
             WHERE custom_fields.ip_id = ips.id
           ) AS custom_fields
        FROM ips
        LEFT JOIN subnets ON ips.subnet_id = subnets.id
        LEFT JOIN users u ON ips.created_by = u.id
        LEFT JOIN companies c ON ips.company_id = c.company_id
        WHERE 1=1 ";
$params = [];

// Apply search filter if provided
if (!empty($search)) {
    $sql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Apply status filter if provided
if (!empty($status)) {
    $sql .= " AND ips.status = ? ";
    $params[] = $status;
}

// Apply multi‑tenancy filter: Only show IPs belonging to user's companies
$placeholders = implode(',', array_fill(0, count($companyIds), '?'));
$sql .= " AND ips.company_id IN ($placeholders) ";
$params = array_merge($params, $companyIds);

// Order by IP address
$sql .= " ORDER BY ips.ip_address ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - IP Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Top Navbar -->
  <div class="navbar">
    <div class="navbar-container">
      <div class="logo">
        <img src="ryan_logo.png" alt="Logo" style="max-height: 50px;">
      </div>
      <div class="nav-links">
        <a href="dashboard.php" class="nav-btn">🏠 Home</a>
        <a href="manage_ip.php?action=list" class="nav-btn">➕ Manage IP</a>
        <a href="manage_subnets.php?action=list" class="nav-btn">🌐 Manage Subnets</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="admin_users.php" class="nav-btn">👥 Manage Users</a>
          <a href="manage_companies.php" class="nav-btn">🏢 Manage Companies</a>
        <?php endif; ?>
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
      </div>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="filter-bar no-print">
    <div class="filter-bar-container">
      <form method="GET" class="filter-form">
        <label for="search">Search:</label>
        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="IP, Assigned To, or Owner...">
        <label for="status">Status:</label>
        <select name="status" id="status">
          <option value="">-- Any --</option>
          <option value="Available" <?= ($status === 'Available' ? 'selected' : '') ?>>Available</option>
          <option value="Reserved" <?= ($status === 'Reserved' ? 'selected' : '') ?>>Reserved</option>
          <option value="Assigned" <?= ($status === 'Assigned' ? 'selected' : '') ?>>Assigned</option>
          <option value="Expired" <?= ($status === 'Expired' ? 'selected' : '') ?>>Expired</option>
        </select>
        <button type="submit" class="nav-btn">Filter</button>
        <a href="dashboard.php" class="nav-btn">Reset</a>
      </form>
      <div class="filter-actions">
        <a href="bulk_upload.php" class="nav-btn">📤 Upload</a>
        <a href="export_ips.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="nav-btn">📊 Export</a>
        <button type="button" onclick="window.print()" class="nav-btn">🖨 Print</button>
        <button type="button" id="toggleColumnsBtn" class="nav-btn">📑 Columns</button>
        <a href="scheduler_manager.php" class="nav-btn">🗄 Backup Scheduler</a>
      </div>
    </div>
  </div>

  <!-- Print-Only Header -->
  <div class="print-header no-print">
    <h1>IP Management System</h1>
    <p>Printed by: <?= htmlspecialchars($_SESSION['username']) ?></p>
  </div>

  <!-- Main Content Container -->
  <div class="container-content">
    <div class="card">
      <div class="card-header">
        <h3 class="ip-list-title">📋 IP Address List</h3>
        <div class="current-user"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
      </div>
      <?php if (count($ips) > 0): ?>
        <table id="ipTable">
          <tr>
            <th>IP Address</th>
            <th>Subnet</th>
            <th>Status</th>
            <th>Assigned To</th>
            <th>Owner</th>
            <th>Description</th>
            <th>Type</th>
            <th>Location</th>
            <th>Company</th>
            <th>Created At</th>
            <th>Last Updated</th>
            <th>Actions</th>
          </tr>
          <?php foreach ($ips as $ip): ?>
          <tr>
            <td><?= htmlspecialchars($ip['ip_address']) ?></td>
            <td><?= htmlspecialchars($ip['subnet'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($ip['status']) ?></td>
            <td><?= htmlspecialchars($ip['assigned_to']) ?></td>
            <td><?= htmlspecialchars($ip['owner']) ?></td>
            <td><?= htmlspecialchars($ip['description']) ?></td>
            <td><?= htmlspecialchars($ip['type']) ?></td>
            <td><?= htmlspecialchars($ip['location']) ?></td>
            <td><?= htmlspecialchars($ip['company_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($ip['created_at']) ?></td>
            <td><?= htmlspecialchars($ip['last_updated']) ?></td>
            <td>
              <a href="manage_ip.php?action=edit&id=<?= $ip['id'] ?>" class="btn">Edit</a>
              <a href="manage_ip.php?action=delete&id=<?= $ip['id'] ?>" class="btn" onclick="return confirm('Are you sure you want to delete this IP?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p>No IP addresses found. <a href="manage_ip.php?action=add">Add your first IP</a></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Column Toggle Modal Markup -->
  <div id="toggleColumnsModal" class="modal">
    <div class="modal-content">
      <span class="close" id="toggleClose">&times;</span>
      <h3>Toggle Columns</h3>
      <div class="column-toggles">
        <label><input type="checkbox" data-col="0" checked> IP Address</label>
        <label><input type="checkbox" data-col="1" checked> Subnet</label>
        <label><input type="checkbox" data-col="2" checked> Status</label>
        <label><input type="checkbox" data-col="3" checked> Assigned To</label>
        <label><input type="checkbox" data-col="4" checked> Owner</label>
        <label><input type="checkbox" data-col="5" checked> Description</label>
        <label><input type="checkbox" data-col="6" checked> Type</label>
        <label><input type="checkbox" data-col="7" checked> Location</label>
        <label><input type="checkbox" data-col="8" checked> Company</label>
        <label><input type="checkbox" data-col="9"> Created At</label>
        <label><input type="checkbox" data-col="10"> Last Updated</label>
        <label><input type="checkbox" data-col="11"> Actions</label>
      </div>
    </div>
  </div>

  <!-- JavaScript for Modal and Column Toggle -->
  <script>
    // Modal functionality for column toggle
    var modal = document.getElementById("toggleColumnsModal");
    var btn = document.getElementById("toggleColumnsBtn");
    var closeBtn = document.getElementById("toggleClose");

    btn.onclick = function() {
      modal.style.display = "block";
    }
    closeBtn.onclick = function() {
      modal.style.display = "none";
    }
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }

    // Column toggle functionality with initial state sync
    document.addEventListener("DOMContentLoaded", function(){
      var table = document.getElementById("ipTable") || document.querySelector("table");
      var firstRow = table.querySelector("tr");
      var toggles = document.querySelectorAll(".column-toggles input[type=checkbox]");
      toggles.forEach(function(toggle) {
        var colIndex = parseInt(toggle.getAttribute("data-col"));
        if (firstRow && firstRow.children[colIndex].style.display === "none") {
          toggle.checked = false;
        } else {
          toggle.checked = true;
        }
        toggle.addEventListener("change", function(){
          table.querySelectorAll("tr").forEach(function(row) {
            var cells = row.children;
            if(cells.length > colIndex) {
              cells[colIndex].style.display = toggle.checked ? "" : "none";
            }
          });
        });
      });
    });
  </script>
</body>
</html>


