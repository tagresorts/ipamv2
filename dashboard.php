<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/*********************************************************
 * Capture Filter Criteria from GET (if provided)
 *********************************************************/
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

/*********************************************************
 * Build Dynamic SQL Query Based on Filters
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
           (
             SELECT GROUP_CONCAT(CONCAT(custom_fields.field_name, ': ', custom_fields.field_value) SEPARATOR '; ')
             FROM custom_fields
             WHERE custom_fields.ip_id = ips.id
           ) AS custom_fields
        FROM ips
        LEFT JOIN subnets ON ips.subnet_id = subnets.id
        LEFT JOIN users u ON ips.created_by = u.id
        WHERE 1=1 ";
$params = [];

// Apply filter criteria if provided
if (!empty($search)) {
    $sql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $sql .= " AND ips.status = ? ";
    $params[] = $status;
}

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
  <!-- Dark Blue Navbar -->
  <div class="navbar">
    <div class="navbar-container">
      <div class="logo">
        <h2>IP Management System</h2>
      </div>
      <div class="user-info">
        <p>Welcome, 
          <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong> 
          (Role: <?= htmlspecialchars($_SESSION['role']) ?>)
        </p>
      </div>
      <div class="nav-links">
        <a href="dashboard.php" class="nav-btn">🏠 Home</a>
        <a href="add_ip.php" class="nav-btn">➕ Add IP</a>
        <a href="subnets.php" class="nav-btn">🌐 Subnets</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="admin_users.php" class="nav-btn">👥 Manage Users</a>
        <?php endif; ?>
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
      </div>
    </div>
  </div>

  <!-- Filter and Column Toggle Panel (Non-printable) -->
  <div class="container no-print">
    <form method="GET" class="filter-form" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
      <div>
        <label for="search"><strong>Search:</strong></label>
        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="IP, Assigned To, or Owner...">
      </div>
      <div>
        <label for="status"><strong>Status:</strong></label>
        <select name="status" id="status">
          <option value="">-- Any --</option>
          <option value="Available" <?= ($status === 'Available' ? 'selected' : '') ?>>Available</option>
          <option value="Reserved"  <?= ($status === 'Reserved'  ? 'selected' : '') ?>>Reserved</option>
          <option value="Assigned"  <?= ($status === 'Assigned'  ? 'selected' : '') ?>>Assigned</option>
          <option value="Expired"   <?= ($status === 'Expired'   ? 'selected' : '') ?>>Expired</option>
        </select>
      </div>
      <div>
        <button type="submit" class="nav-btn">Filter</button>
        <a href="dashboard.php" class="nav-btn">Reset</a>
      </div>
      <div style="margin-left: auto;">
        <a href="export_ips.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="nav-btn" style="background: #28a745; color: #fff;">Export to Excel</a>
        <button type="button" onclick="window.print()" class="nav-btn" style="background: #17a2b8; color: #fff;">Print</button>
        <button type="button" id="toggleColumnsBtn" class="nav-btn">Toggle Columns</button>
      </div>
    </form>
  </div>

  <!-- Column Toggle Modal -->
  <div id="toggleColumnsModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
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
        <label><input type="checkbox" data-col="8" checked> Created At</label>
        <label><input type="checkbox" data-col="9" checked> Created by</label>
        <label><input type="checkbox" data-col="10" checked> Last Updated</label>
        <label><input type="checkbox" data-col="11" checked> Custom Fields</label>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <label><input type="checkbox" data-col="12" checked> Actions</label>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Print-Only Header -->
  <div class="print-header no-print">
    <h1>IP Management System</h1>
    <p>Printed by: <?= htmlspecialchars($_SESSION['username']) ?></p>
  </div>

  <!-- Main Content Container -->
  <div class="container">
    <h3 class="ip-list-title">📋 IP Address List</h3>
    <?php if (count($ips) > 0): ?>
      <table>
        <tr>
          <th>IP Address</th>         <!-- Column 0 -->
          <th>Subnet</th>               <!-- Column 1 -->
          <th>Status</th>               <!-- Column 2 -->
          <th>Assigned To</th>          <!-- Column 3 -->
          <th>Owner</th>                <!-- Column 4 -->
          <th>Description</th>          <!-- Column 5 -->
          <th>Type</th>                 <!-- Column 6 -->
          <th>Location</th>             <!-- Column 7 -->
          <th>Created At</th>           <!-- Column 8 -->
          <th>Created by</th>           <!-- Column 9 -->
          <th>Last Updated</th>         <!-- Column 10 -->
          <th>Custom Fields</th>        <!-- Column 11 -->
          <?php if($_SESSION['role'] === 'admin'): ?>
            <th>Actions</th>            <!-- Column 12 -->
          <?php endif; ?>
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
          <td><?= htmlspecialchars($ip['created_at']) ?></td>
          <td><?= htmlspecialchars($ip['created_by_username'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($ip['last_updated']) ?></td>
          <td><?= htmlspecialchars($ip['custom_fields'] ?? '') ?></td>
          <?php if($_SESSION['role'] === 'admin'): ?>
            <td>
              <a href="edit_ip.php?id=<?= $ip['id'] ?>" class="btn">Edit</a>
              <a href="delete_ip.php?id=<?= $ip['id'] ?>" class="btn" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
            </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>No IP addresses found. <a href="add_ip.php">Add your first IP</a></p>
    <?php endif; ?>
  </div>

  <!-- JavaScript for Modal and Column Toggle -->
  <script>
    // Modal functionality
    var modal = document.getElementById("toggleColumnsModal");
    var btn = document.getElementById("toggleColumnsBtn");
    var span = document.getElementsByClassName("close")[0];

    // Open modal on button click
    btn.onclick = function() {
      modal.style.display = "block";
    }

    // Close modal when user clicks the close (x)
    span.onclick = function() {
      modal.style.display = "none";
    }

    // Close modal if user clicks outside modal content
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }

    // Column toggle functionality
    document.addEventListener("DOMContentLoaded", function(){
      var toggles = document.querySelectorAll(".column-toggles input[type=checkbox]");
      toggles.forEach(function(toggle) {
        toggle.addEventListener("change", function(){
          var colIndex = parseInt(this.getAttribute("data-col"));
          var table = document.querySelector("table");
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
