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
  <style>
    /* Navbar adjustments */
    .navbar-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 10px;
    }
    .logo {
      margin-left: 10px;
    }
    .nav-links {
      margin-right: 10px;
    }
    /* Draggable columns */
    th {
      cursor: move;
      user-select: none;
    }
    /* Inline Modal Styles for Column Toggle */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }
    .modal.show {
      display: block;
    }
    .modal-content {
      background-color: #fefefe;
      margin: 10% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 80%;
      max-width: 500px;
      border-radius: 8px;
    }
    .close {
      float: right;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      color: #aaa;
    }
    .close:hover,
    .close:focus {
      color: #000;
    }
    /* Grid layout for toggle checkboxes */
    .toggle-container {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 10px;
      margin-bottom: 20px;
    }
    .toggle-item {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      background: #f9f9f9;
      text-align: center;
    }
    .button-group {
      text-align: center;
    }
    .button-group button {
      padding: 8px 12px;
      margin: 0 5px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <!-- Top Navbar -->
  <div class="navbar">
    <div class="navbar-container">
      <div class="logo">
        <img src="ryan_logo.png" alt="Logo" style="max-height:50px;">
      </div>
      <div class="nav-links">
        <a href="dashboard.php" class="nav-btn">🏠 Home</a>
        <a href="manage_ip.php?action=list" class="nav-btn">➕ Manage IP</a>
        <a href="manage_subnets.php?action=list" class="nav-btn">🌐 Manage Subnets</a>
        <?php if($_SESSION['role'] === 'admin'): ?>
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
    <p>Printed by: <?= htmlspecialchars(($_SESSION['first_name'] ?? $_SESSION['username']) . " - " . $_SESSION['role']) ?></p>
  </div>

  <!-- Main Content Container -->
  <div class="container-content">
    <div class="card">
      <div class="card-header">
        <h3 class="ip-list-title">📋 IP Address List</h3>
        <div class="current-user"><?= htmlspecialchars(($_SESSION['first_name'] ?? $_SESSION['username']) . " - " . $_SESSION['role']) ?></div>
      </div>
      <?php if(count($ips) > 0): ?>
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
          <th>Created by</th>
          <th>Actions</th>
        </tr>
        <?php foreach($ips as $ip): ?>
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
          <td><?= htmlspecialchars($ip['created_by_username']) ?></td>
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

  <!-- Inline Column Toggle Modal -->
  <div id="toggleColumnsModal" class="modal">
    <div class="modal-content">
      <span class="close" id="toggleClose">&times;</span>
      <h3>Toggle & Rearrange Columns</h3>
      <div class="toggle-container">
        <div class="toggle-item">
          <label><input type="checkbox" data-col="0" checked> IP Address</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="1" checked> Subnet</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="2" checked> Status</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="3" checked> Assigned To</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="4" checked> Owner</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="5" checked> Description</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="6" checked> Type</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="7" checked> Location</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="8" checked> Company</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="9"> Created At</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="10"> Last Updated</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="11" checked> Created by</label>
        </div>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="12" checked> Actions</label>
        </div>
      </div>
      <div class="button-group">
        <button id="saveBtn">Save</button>
        <button id="restoreBtn">Restore Default View</button>
      </div>
    </div>
  </div>

  <!-- JavaScript for Inline Modal, Column Toggling, and Drag & Drop -->
  <script>
    // Modal toggle for inline modal
    const modal = document.getElementById("toggleColumnsModal");
    const toggleBtn = document.getElementById("toggleColumnsBtn");
    const closeBtn = document.getElementById("toggleClose");
    toggleBtn.addEventListener("click", function() {
        modal.classList.add("show");
    });
    closeBtn.addEventListener("click", function() {
        modal.classList.remove("show");
    });
    window.addEventListener("click", function(e) {
        if (e.target == modal) {
            modal.classList.remove("show");
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        const table = document.getElementById("ipTable");
        if (!table) return;

        // Apply saved column order if available
        const savedOrder = localStorage.getItem("ipTableColumnOrder");
        if (savedOrder) {
            let order = JSON.parse(savedOrder);
            applyColumnOrder(table, order);
        }

        // Apply saved hidden column settings
        const savedHidden = localStorage.getItem("ipTableHiddenColumns");
        if (savedHidden) {
            let hiddenColumns = JSON.parse(savedHidden);
            hiddenColumns.forEach(idx => {
                toggleColumnVisibility(table, idx, false);
                const checkbox = document.querySelector(`.toggle-container input[data-col="${idx}"]`);
                if (checkbox) checkbox.checked = false;
            });
        }

        // Draggable header cells
        const headerRow = table.querySelector("tr");
        let draggedIndex;
        Array.from(headerRow.children).forEach((th, index) => {
            th.setAttribute("draggable", true);
            th.addEventListener("dragstart", (e) => {
                draggedIndex = index;
                e.dataTransfer.effectAllowed = "move";
            });
            th.addEventListener("dragover", (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = "move";
            });
            th.addEventListener("drop", (e) => {
                e.preventDefault();
                const targetIndex = index;
                if (draggedIndex === targetIndex) return;
                reorderTableColumns(table, draggedIndex, targetIndex);
                const newOrder = getCurrentColumnOrder(table);
                localStorage.setItem("ipTableColumnOrder", JSON.stringify(newOrder));
            });
        });

        // Modal toggle checkboxes functionality
        const toggles = document.querySelectorAll(".toggle-container input[type=checkbox]");
        toggles.forEach(function(toggle) {
            var colIndex = parseInt(toggle.getAttribute("data-col"));
            toggle.addEventListener("change", function(){
                const show = toggle.checked;
                toggleColumnVisibility(table, colIndex, show);
                let hiddenColumns = localStorage.getItem("ipTableHiddenColumns");
                hiddenColumns = hiddenColumns ? JSON.parse(hiddenColumns) : [];
                if (!show) {
                    if (!hiddenColumns.includes(colIndex)) {
                        hiddenColumns.push(colIndex);
                    }
                } else {
                    hiddenColumns = hiddenColumns.filter(idx => idx !== colIndex);
                }
                localStorage.setItem("ipTableHiddenColumns", JSON.stringify(hiddenColumns));
            });
        });

        // Restore default view button functionality
        const restoreBtn = document.getElementById("restoreBtn");
        restoreBtn.addEventListener("click", function() {
            localStorage.removeItem("ipTableColumnOrder");
            localStorage.removeItem("ipTableHiddenColumns");
            location.reload();
        });

        // Helper: Toggle column visibility
        function toggleColumnVisibility(table, colIndex, visible) {
            table.querySelectorAll("tr").forEach(function(row) {
                let cells = row.children;
                if(cells.length > colIndex) {
                    cells[colIndex].style.display = visible ? "" : "none";
                }
            });
        }

        // Helper: Get current column order (array of header texts)
        function getCurrentColumnOrder(table) {
            const headerCells = table.querySelector("tr").children;
            let order = [];
            for (let i = 0; i < headerCells.length; i++) {
                order.push(headerCells[i].innerText.trim());
            }
            return order;
        }

        // Helper: Apply a saved column order (array of header texts)
        function applyColumnOrder(table, order) {
            const rows = table.querySelectorAll("tr");
            let currentOrder = [];
            Array.from(rows[0].children).forEach(cell => {
                currentOrder.push(cell.innerText.trim());
            });
            let newIndices = order.map(headerText => currentOrder.indexOf(headerText));
            rows.forEach(row => {
                let cells = Array.from(row.children);
                let newCells = [];
                newIndices.forEach(idx => {
                    if (cells[idx]) {
                        newCells.push(cells[idx]);
                    }
                });
                row.innerHTML = "";
                newCells.forEach(cell => row.appendChild(cell));
            });
        }

        // Helper: Reorder table columns when dragging header cells
        function reorderTableColumns(table, oldIndex, newIndex) {
            const rows = table.querySelectorAll("tr");
            rows.forEach(row => {
                let cells = Array.from(row.children);
                const cell = cells.splice(oldIndex, 1)[0];
                cells.splice(newIndex, 0, cell);
                row.innerHTML = "";
                cells.forEach(c => row.appendChild(c));
            });
        }
    });
  </script>
</body>
</html>
