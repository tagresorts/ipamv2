<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userRole = $_SESSION['role'];

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
 * Get User's Companies for Company Dropdown
 *********************************************************/
$stmt = $pdo->prepare("SELECT c.company_id, c.company_name
                      FROM companies c
                      INNER JOIN user_companies uc ON c.company_id = uc.company_id
                      WHERE uc.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*********************************************************
 * Capture Filter Criteria from GET (if provided)
 *********************************************************/
$search        = $_GET['search']   ?? '';
$companyFilter = $_GET['company']  ?? '';

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

// Apply search filter (extended across multiple fields)
if (!empty($search)) {
    $sql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ? OR ips.description LIKE ? OR ips.location LIKE ? OR ips.type LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, array_fill(0, 6, $searchTerm));
}

// Apply company filter if provided; otherwise apply multi‑tenancy filter
if (!empty($companyFilter)) {
    $sql .= " AND ips.company_id = ? ";
    $params[] = $companyFilter;
} else {
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $sql .= " AND ips.company_id IN ($placeholders) ";
    $params = array_merge($params, $companyIds);
}

/*********************************************************
 * Sorting and Pagination
 *********************************************************/
// Allowed sort columns (only a subset are sortable)
$allowedSortColumns = ['ip_address', 'status', 'assigned_to', 'owner', 'created_at', 'last_updated'];
$sort = in_array($_GET['sort'] ?? '', $allowedSortColumns) ? $_GET['sort'] : 'ip_address';
$direction = (isset($_GET['direction']) && $_GET['direction'] === 'DESC') ? 'DESC' : 'ASC';

// Special ordering for IP addresses
if ($sort === 'ip_address') {
    $sql .= " ORDER BY INET_ATON(ips.ip_address) $direction";
} else {
    $sql .= " ORDER BY $sort $direction";
}

// Pagination: limit to 10 results per page
$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));

// Clone query for count
$countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") AS countTable";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

// Apply pagination limits
$offset = ($page - 1) * $perPage;
$sql .= " LIMIT $perPage OFFSET $offset";

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
  <!-- External Stylesheet (including filter bar styles) -->
  <link rel="stylesheet" href="style.css">
  <style>
    /* Basic Styles and Responsive Enhancements */
    th {
      cursor: move;
      user-select: none;
      white-space: nowrap;
    }
    
    .status-badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.85em;
      font-weight: 500;
      display: inline-block;
      min-width: 80px;
      text-align: center;
    }
    .status-available { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-reserved { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .status-assigned { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .status-expired { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
    .modal.show { display: block; }
    .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
    .close { float: right; font-size: 24px; font-weight: bold; cursor: pointer; color: #aaa; }
    .close:hover, .close:focus { color: #000; }

    /* Removed inline filter bar styles; filter bar styling is now in style.css */

    .container-content {
      margin: 20px;
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 8px;
      border: 1px solid #ddd;
      text-align: left;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .pagination {
      margin: 20px 0;
      text-align: center;
    }
    .pagination a, .pagination span {
      display: inline-block;
      padding: 8px 16px;
      margin: 0 4px;
      border: 1px solid #ddd;
      border-radius: 4px;
      text-decoration: none;
    }
    .pagination a:hover {
      background: #eee;
    }

    .loading-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.8);
      z-index: 1000;
    }
    .loading-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      border: 4px solid #f3f3f3;
      border-top: 4px solid #3498db;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 1200px) {
      #ipTable { min-width: 1200px; }
    }
  </style>
</head>
<body>
  <div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
  </div>

  <!-- Top Navbar -->
  <div class="navbar">
    <div class="navbar-container">
      <div class="logo">
        <img src="ryan_logo.png" alt="Logo" style="max-height:50px;">
      </div>
      <div class="nav-links">
        <a href="dashboard.php" class="nav-btn">🏠 Home</a>
        <?php if ($userRole !== 'guest'): ?>
          <a href="manage_ip.php?action=list" class="nav-btn">➕ Manage IP</a>
          <a href="manage_subnets.php?action=list" class="nav-btn">🌐 Manage Subnets</a>
        <?php endif; ?>
        <?php if ($userRole === 'admin'): ?>
          <a href="admin_users.php" class="nav-btn">👥 Manage Users</a>
          <a href="manage_companies.php" class="nav-btn">🏢 Manage Companies</a>
        <?php endif; ?>
        <a href="logout.php" class="logout-btn nav-btn">🚪 Logout</a>
      </div>
    </div>
  </div>

  <!-- Filter Bar (Using style.css for styling) -->
  <div class="filter-bar no-print">
    <div class="filter-bar-container">
      <form method="GET" class="filter-form" onsubmit="document.getElementById('loadingOverlay').style.display='block'">
        <label for="search">Search:</label>
        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="IP, Assigned To, Owner, Description, Location or Type...">
        
        <label for="company">Company:</label>
        <select name="company" id="company">
          <option value="">-- All Companies --</option>
          <?php foreach ($userCompanies as $company): ?>
            <option value="<?= $company['company_id'] ?>" <?= ($companyFilter == $company['company_id'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($company['company_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <button type="submit" class="nav-btn">Filter</button>
        <a href="dashboard.php" class="nav-btn">Reset</a>
      </form>
      <div class="filter-actions">
        <?php if ($userRole !== 'guest'): ?>
          <a href="bulk_upload.php" class="nav-btn">📤 Upload</a>
        <?php endif; ?>
        <a href="export_ips.php?<?= http_build_query($_GET) ?>" class="nav-btn">📊 Export</a>
        <button type="button" onclick="window.print()" class="nav-btn">🖨 Print</button>
        <button type="button" id="toggleColumnsBtn" class="nav-btn">📑 Columns</button>
        <?php if ($userRole !== 'guest'): ?>
          <a href="scheduler_manager.php" class="nav-btn">🗄 Cron</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container-content">
    <div class="card">
      <div class="card-header">
        <h3 class="ip-list-title">
          📋 IP Address List - "Displaying <?= count($ips) ?> of <?= $totalItems ?> IPs"
        </h3>
        <div class="current-user"><?= htmlspecialchars((!empty($_SESSION['first_name']) ? $_SESSION['first_name'] : $_SESSION['username']) . " - " . $_SESSION['role']) ?></div>
      </div>
      <?php if(count($ips) > 0): ?>
      <table id="ipTable">
        <tr>
          <?php
          // Define table headers and allowed sortable columns
          $headers = [
            'ip_address'          => 'IP Address',
            'subnet'              => 'Subnet',
            'status'              => 'Status',
            'assigned_to'         => 'Assigned To',
            'owner'               => 'Owner',
            'description'         => 'Description',
            'type'                => 'Type',
            'location'            => 'Location',
            'company_name'        => 'Company',
            'created_at'          => 'Created At',
            'last_updated'        => 'Last Updated',
            'created_by_username' => 'Created by'
          ];
          $allowedSortColumns = ['ip_address', 'status', 'assigned_to', 'owner', 'created_at', 'last_updated'];
          
          foreach ($headers as $column => $text): ?>
            <th>
              <?php if (in_array($column, $allowedSortColumns)): ?>
                <a href="?<?= http_build_query(array_merge($_GET, [
                  'sort' => $column,
                  'direction' => (($_GET['sort'] ?? '') === $column && ($_GET['direction'] ?? '') === 'ASC') ? 'DESC' : 'ASC'
                ])) ?>">
                  <?= $text ?> 
                  <?php if (($sort === $column)): ?>
                    <?= $direction === 'ASC' ? '↑' : '↓' ?>
                  <?php endif; ?>
                </a>
              <?php else: ?>
                <?= $text ?>
              <?php endif; ?>
            </th>
          <?php endforeach; ?>
          <?php if ($userRole !== 'guest'): ?>
            <th>Actions</th>
          <?php endif; ?>
        </tr>
        <?php foreach($ips as $ip): ?>
        <tr>
          <td><?= htmlspecialchars($ip['ip_address']) ?></td>
          <td><?= htmlspecialchars($ip['subnet'] ?? 'N/A') ?></td>
          <td>
            <span class="status-badge status-<?= strtolower($ip['status']) ?>">
              <?= htmlspecialchars($ip['status']) ?>
            </span>
          </td>
          <td><?= htmlspecialchars($ip['assigned_to']) ?></td>
          <td><?= htmlspecialchars($ip['owner']) ?></td>
          <td><?= htmlspecialchars($ip['description']) ?></td>
          <td><?= htmlspecialchars($ip['type']) ?></td>
          <td><?= htmlspecialchars($ip['location']) ?></td>
          <td><?= htmlspecialchars($ip['company_name'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($ip['created_at']) ?></td>
          <td><?= htmlspecialchars($ip['last_updated']) ?></td>
          <td><?= htmlspecialchars($ip['created_by_username']) ?></td>
          <?php if ($userRole !== 'guest'): ?>
          <td>
            <a href="manage_ip.php?action=edit&id=<?= $ip['id'] ?>" class="btn">Edit</a>
            <a href="manage_ip.php?action=delete&id=<?= $ip['id'] ?>" class="btn" onclick="return confirm('Are you sure you want to delete this IP?');">Delete</a>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </table>
      
      <!-- Pagination -->
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« Previous</a>
        <?php endif; ?>
        
        <span>Page <?= $page ?> of <?= $totalPages ?></span>
        
        <?php if ($page < $totalPages): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next »</a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <p>No IP addresses found. <?php if ($userRole !== 'guest'): ?><a href="manage_ip.php?action=add">Add your first IP</a><?php endif; ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Column Toggle Modal -->
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
        <?php if ($userRole !== 'guest'): ?>
        <div class="toggle-item">
          <label><input type="checkbox" data-col="12" checked> Actions</label>
        </div>
        <?php endif; ?>
      </div>
      <div class="button-group">
        <button id="saveBtn">Save</button>
        <button id="restoreBtn">Restore Default View</button>
      </div>
    </div>
  </div>

  <!-- External JavaScript -->
  <script src="column_modal.js"></script>
</body>
</html>
