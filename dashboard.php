<?php
session_start();
include 'config.php';

// Set the chart diameter (in pixels) for the pie charts
$chartDiameter = 350; // Update this value to change the diameter

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
 * Query for Summary Data: Total IP Count
 *********************************************************/
$listSql = "SELECT COUNT(*) as total FROM ips WHERE 1=1";
$listParams = [];
if (!empty($search)) {
    $listSql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ? OR ips.description LIKE ? OR ips.location LIKE ? OR ips.type LIKE ?)";
    $listParams = array_merge($listParams, array_fill(0, 6, "%$search%"));
}
if (!empty($companyFilter)) {
    $listSql .= " AND ips.company_id = ? ";
    $listParams[] = $companyFilter;
} else {
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $listSql .= " AND ips.company_id IN ($placeholders) ";
    $listParams = array_merge($listParams, $companyIds);
}
$stmtList = $pdo->prepare($listSql);
$stmtList->execute($listParams);
$totalItems = $stmtList->fetchColumn();

/*********************************************************
 * Query for IP Distribution by Type
 *********************************************************/
$typeSql = "SELECT ips.type as type, COUNT(*) as count FROM ips WHERE 1=1";
$typeParams = [];
if (!empty($search)) {
    $typeSql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ? OR ips.description LIKE ? OR ips.location LIKE ? OR ips.type LIKE ?)";
    $typeParams = array_merge($typeParams, array_fill(0, 6, "%$search%"));
}
if (!empty($companyFilter)) {
    $typeSql .= " AND ips.company_id = ? ";
    $typeParams[] = $companyFilter;
} else {
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $typeSql .= " AND ips.company_id IN ($placeholders) ";
    $typeParams = array_merge($typeParams, $companyIds);
}
$typeSql .= " GROUP BY ips.type";
$stmtType = $pdo->prepare($typeSql);
$stmtType->execute($typeParams);
$typeLabels = [];
$typeData = [];
while($row = $stmtType->fetch(PDO::FETCH_ASSOC)) {
    $typeLabels[] = $row['type'] ? $row['type'] : 'N/A';
    $typeData[] = (int)$row['count'];
}
$typeChartData = [
    'labels' => $typeLabels,
    'datasets' => [[
        'label' => 'IP Distribution by Type',
        'data' => $typeData,
        'backgroundColor' => [
            'rgba(75, 192, 192, 0.6)',
            'rgba(255, 205, 86, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)'
        ]
    ]]
];

/*********************************************************
 * Query for IP Distribution by Company
 *********************************************************/
$companyQuery = "SELECT c.company_name as company, COUNT(*) as count
                 FROM ips
                 LEFT JOIN companies c ON ips.company_id = c.company_id
                 WHERE 1=1";
$companyParams = [];
if (!empty($search)) {
    $companyQuery .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ? OR ips.description LIKE ? OR ips.location LIKE ? OR ips.type LIKE ?)";
    $companyParams = array_merge($companyParams, array_fill(0, 6, "%$search%"));
}
if (!empty($companyFilter)) {
    $companyQuery .= " AND ips.company_id = ?";
    $companyParams[] = $companyFilter;
} else {
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $companyQuery .= " AND ips.company_id IN ($placeholders)";
    $companyParams = array_merge($companyParams, $companyIds);
}
$companyQuery .= " GROUP BY c.company_name";
$stmtCompany = $pdo->prepare($companyQuery);
$stmtCompany->execute($companyParams);
$companyLabels = [];
$companyData = [];
while($row = $stmtCompany->fetch(PDO::FETCH_ASSOC)){
    $companyLabels[] = $row['company'] ? $row['company'] : 'N/A';
    $companyData[] = (int)$row['count'];
}
$companyChartData = [
    'labels' => $companyLabels,
    'datasets' => [[
         'label' => 'IP Distribution by Company',
         'data' => $companyData,
         'backgroundColor' => [
            'rgba(255, 99, 132, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(255, 205, 86, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)'
         ]
    ]]
];

/*********************************************************
 * Query for IP Distribution by Location
 *********************************************************/
$locationSql = "SELECT ips.location as location, COUNT(*) as count FROM ips WHERE 1=1";
$locationParams = [];
if (!empty($search)) {
    $locationSql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ? OR ips.description LIKE ? OR ips.location LIKE ? OR ips.type LIKE ?)";
    $locationParams = array_merge($locationParams, array_fill(0, 6, "%$search%"));
}
if (!empty($companyFilter)) {
    $locationSql .= " AND ips.company_id = ? ";
    $locationParams[] = $companyFilter;
} else {
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $locationSql .= " AND ips.company_id IN ($placeholders) ";
    $locationParams = array_merge($locationParams, $companyIds);
}
$locationSql .= " GROUP BY ips.location";
$stmtLocation = $pdo->prepare($locationSql);
$stmtLocation->execute($locationParams);
$locationLabels = [];
$locationData = [];
while($row = $stmtLocation->fetch(PDO::FETCH_ASSOC)) {
    $locationLabels[] = $row['location'] ? $row['location'] : 'N/A';
    $locationData[] = (int)$row['count'];
}
$locationChartData = [
    'labels' => $locationLabels,
    'datasets' => [[
        'label' => 'IP Distribution by Location',
        'data' => $locationData,
        'backgroundColor' => [
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(255, 205, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)'
        ]
    ]]
];

/*********************************************************
 * Query for IP Distribution by Subnet
 *********************************************************/
$subnetSql = "SELECT IFNULL(subnets.subnet, 'N/A') as subnet, COUNT(*) as count
              FROM ips
              LEFT JOIN subnets ON ips.subnet_id = subnets.id
              WHERE 1=1";
$subnetParams = [];
if (!empty($search)) {
    $subnetSql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ? OR ips.description LIKE ? OR ips.location LIKE ? OR ips.type LIKE ?)";
    $subnetParams = array_merge($subnetParams, array_fill(0, 6, "%$search%"));
}
if (!empty($companyFilter)) {
    $subnetSql .= " AND ips.company_id = ? ";
    $subnetParams[] = $companyFilter;
} else {
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $subnetSql .= " AND ips.company_id IN ($placeholders) ";
    $subnetParams = array_merge($subnetParams, $companyIds);
}
$subnetSql .= " GROUP BY subnet";
$stmtSubnet = $pdo->prepare($subnetSql);
$stmtSubnet->execute($subnetParams);
$subnetLabels = [];
$subnetData = [];
while($row = $stmtSubnet->fetch(PDO::FETCH_ASSOC)) {
    $subnetLabels[] = $row['subnet'];
    $subnetData[] = (int)$row['count'];
}
$subnetChartData = [
    'labels' => $subnetLabels,
    'datasets' => [[
        'label' => 'IP Distribution by Subnet',
        'data' => $subnetData,
        'backgroundColor' => [
            'rgba(255, 99, 132, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(255, 205, 86, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)'
        ]
    ]]
];

/*********************************************************
 * Build Dynamic SQL Query for IP List (Table)
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
if (!empty($search)) {
    $sql .= " AND (ips.ip_address LIKE ? OR ips.assigned_to LIKE ? OR ips.owner LIKE ? OR ips.description LIKE ? OR ips.location LIKE ? OR ips.type LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, array_fill(0, 6, $searchTerm));
}
if (!empty($companyFilter)) {
    $sql .= " AND ips.company_id = ? ";
    $params[] = $companyFilter;
} else {
    $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
    $sql .= " AND ips.company_id IN ($placeholders) ";
    $params = array_merge($params, $companyIds);
}
$allowedSortColumns = ['ip_address', 'status', 'assigned_to', 'owner', 'created_at', 'last_updated'];
$sort = in_array($_GET['sort'] ?? '', $allowedSortColumns) ? $_GET['sort'] : 'ip_address';
$direction = (isset($_GET['direction']) && $_GET['direction'] === 'DESC') ? 'DESC' : 'ASC';
if ($sort === 'ip_address') {
    $sql .= " ORDER BY INET_ATON(ips.ip_address) $direction";
} else {
    $sql .= " ORDER BY $sort $direction";
}
$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") AS countTable";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);
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
  <!-- External Stylesheet -->
  <link rel="stylesheet" href="style.css">
  <!-- Chart.js Library -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
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
    .container-content { margin: 20px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .pagination { margin: 20px 0; text-align: center; }
    .pagination a, .pagination span { display: inline-block; padding: 8px 16px; margin: 0 4px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; }
    .pagination a:hover { background: #eee; }
    .loading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 1000; }
    .loading-spinner { position: absolute; top: 50%; left: 50%; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @media (max-width: 1200px) { #ipTable { min-width: 1200px; } }
    .dashboard-summary { margin: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
    .dashboard-summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .summary-cards { display: flex; justify-content: space-around; margin-bottom: 20px; }
    /* Updated compact summary card style */
    .summary-card { flex: 1; padding: 5px; margin: 3px; border: 1px solid #ccc; border-radius: 5px; text-align: center; background-color: #fff; font-size: 0.9em; }
    .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .chart-cell { }
    .chart-cell canvas { display: block; margin: auto; }
    .view-toggle { text-align: center; margin: 20px; }
    .nav-btn { padding: 6px 12px; background: #007BFF; color: #fff; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
    .nav-btn:hover { background: #0056b3; }
  </style>
</head>
<body>
  <div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
  </div>

  <!-- Nav Bar (Original Design with Toggle View Button) -->
  <div class="navbar">
    <div class="navbar-container">
      <div class="logo">
        <img src="ryan_logo.png" alt="Logo" style="max-height:50px;">
      </div>
      <div class="nav-links">
        <button id="toggleViewButton" class="nav-btn">Show IP List</button>
        <?php if ($userRole !== 'guest'): ?>
          <a href="manage_ip.php?action=list" class="nav-btn">➕ Manage IP</a>
          <a href="manage_subnets.php?action=list" class="nav-btn">🌐 Manage Subnets</a>
        <?php endif; ?>
        <?php if ($userRole === 'admin'): ?>
          <a href="admin_users.php" class="nav-btn">👥 Manage Users</a>
          <a href="manage_companies.php" class="nav-btn">🏢 Manage Companies</a>
        <?php endif; ?>
        <a href="logout.php" class="nav-btn">🚪 Logout</a>
      </div>
    </div>
  </div>

  <!-- Filter Bar (Using external style.css for styling) -->
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

  <!-- Dashboard Summary Section -->
  <div id="dashboardGraphs" class="dashboard-summary">
    <div class="dashboard-summary-header">
      <h3>Dashboard Summary</h3>
    </div>
    <!-- Compact Summary Cards -->
    <div class="summary-cards">
      <div class="summary-card">
        <h4>Total Companies</h4>
        <p><?= count($companyLabels) ?></p>
      </div>
      <div class="summary-card">
        <h4>Total Types</h4>
        <p><?= count($typeLabels) ?></p>
      </div>
      <div class="summary-card">
        <h4>Total Locations</h4>
        <p><?= count($locationLabels) ?></p>
      </div>
      <div class="summary-card">
        <h4>Total IPs</h4>
        <p><?= $totalItems ?></p>
      </div>
      <div class="summary-card">
        <h4>Total Subnets</h4>
        <p><?= count($subnetLabels) ?></p>
      </div>
    </div>
    <!-- Chart Grid (2 Columns) -->
    <div class="chart-grid">
      <div class="chart-cell">
        <canvas id="typeChart" width="<?= $chartDiameter ?>" height="<?= $chartDiameter ?>"></canvas>
      </div>
      <div class="chart-cell">
        <canvas id="companyChart" width="<?= $chartDiameter ?>" height="<?= $chartDiameter ?>"></canvas>
      </div>
      <div class="chart-cell">
        <canvas id="locationChart" width="<?= $chartDiameter ?>" height="<?= $chartDiameter ?>"></canvas>
      </div>
      <div class="chart-cell">
        <canvas id="subnetChart" width="<?= $chartDiameter ?>" height="<?= $chartDiameter ?>"></canvas>
      </div>
    </div>
  </div>

  <!-- IP List Section -->
  <div id="ipListSection" class="container-content" style="display:none;">
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
            <a href="manage_ip.php?action=edit&id=<?= $ip['id'] ?>" class="nav-btn">Edit</a>
            <a href="manage_ip.php?action=delete&id=<?= $ip['id'] ?>" class="nav-btn" onclick="return confirm('Are you sure you want to delete this IP?');">Delete</a>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </table>
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
        <button id="saveBtn" class="nav-btn">Save</button>
        <button id="restoreBtn" class="nav-btn">Restore Default View</button>
      </div>
    </div>
  </div>

  <!-- External JavaScript -->
  <script src="column_modal.js"></script>
  <script>
    // Pass PHP chart data to JavaScript as global variables
    var typeChartData = <?php echo json_encode($typeChartData); ?>;
    var companyChartData = <?php echo json_encode($companyChartData); ?>;
    var locationChartData = <?php echo json_encode($locationChartData); ?>;
    var subnetChartData = <?php echo json_encode($subnetChartData); ?>;
  </script>
  <!-- Include graphs.js which handles all Chart.js initializations -->
  <script src="graphs.js"></script>
  <script>
    // Toggle view between graphs and IP list
    document.addEventListener("DOMContentLoaded", function(){
      const toggleButton = document.getElementById("toggleViewButton");
      const graphsSection = document.getElementById("dashboardGraphs");
      const ipListSection = document.getElementById("ipListSection");

      // Initially, graphs are visible and IP list is hidden
      graphsSection.style.display = "block";
      ipListSection.style.display = "none";

      toggleButton.addEventListener("click", function(){
         if(graphsSection.style.display === "block"){
            graphsSection.style.display = "none";
            ipListSection.style.display = "block";
            toggleButton.textContent = "Show Graphs";
         } else {
            graphsSection.style.display = "block";
            ipListSection.style.display = "none";
            toggleButton.textContent = "Show IP List";
         }
      });
    });
  </script>
</body>
</html>
