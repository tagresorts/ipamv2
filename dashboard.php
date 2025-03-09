<?php
// dashboard.php
session_start();
include 'config.php';
include 'helpers.php';

// Set individual chart dimensions (in pixels) for each chart
$typeChartWidth = 600;
$typeChartHeight = 300;
$companyChartWidth = 400;
$companyChartHeight = 400;
$locationChartWidth = 600;
$locationChartHeight = 300;
$subnetChartWidth = 400;
$subnetChartHeight = 400;

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Multi‑Tenancy: Get user company IDs and companies
$companyIds = getUserCompanyIds($pdo, $userId);
$userCompanies = getUserCompanies($pdo, $userId);

// Capture filter criteria
$search = $_GET['search'] ?? '';
$companyFilter = $_GET['company'] ?? '';

// Build common filter clause and parameters
list($whereClause, $params) = buildFilterClause($search, $companyFilter, $companyIds);

// Query for total IP count
$totalItems = getTotalIPCount($pdo, $whereClause, $params);

// Fetch chart data using helper function

// IP Distribution by Type
$typeQuery = "SELECT ips.type as type, COUNT(*) as count FROM ips" . $whereClause . " GROUP BY ips.type";
$typeChartData = fetchChartData($pdo, $typeQuery, $params);
$typeChartData['datasets'][0]['label'] = 'IP Distribution by Type';

// IP Distribution by Company
$companyQuery = "SELECT c.company_name as company, COUNT(*) as count FROM ips LEFT JOIN companies c ON ips.company_id = c.company_id" . $whereClause . " GROUP BY c.company_name";
$companyChartData = fetchChartData($pdo, $companyQuery, $params);
$companyChartData['datasets'][0]['label'] = 'IP Distribution by Company';

// IP Distribution by Location
$locationQuery = "SELECT ips.location as location, COUNT(*) as count FROM ips" . $whereClause . " GROUP BY ips.location";
$locationChartData = fetchChartData($pdo, $locationQuery, $params);
$locationChartData['datasets'][0]['label'] = 'IP Distribution by Location';

// IP Distribution by Subnet
$subnetQuery = "SELECT IFNULL(subnets.subnet, 'N/A') as subnet, COUNT(*) as count FROM ips LEFT JOIN subnets ON ips.subnet_id = subnets.id" . $whereClause . " GROUP BY subnet";
$subnetChartData = fetchChartData($pdo, $subnetQuery, $params);
$subnetChartData['datasets'][0]['label'] = 'IP Distribution by Subnet';

// Get current page for IP list from $_GET, defaulting to 1
$currentPage = max(1, intval($_GET['page'] ?? 1));
$perPage = 10; // Must match the value passed to getIPList()

// Updated allowed sort columns including custom fields and extra columns
$allowedSortColumns = [
    'ip_address',
    'subnet',
    'status',
    'assigned_to',
    'owner',
    'description',
    'type',
    'location',
    'company_name',
    'custom_fields',
    'created_at',
    'last_updated'
];

// Get the IP list using the current page and per-page value
list($ips, $totalPages, $currentPage, $totalItems) = getIPList($pdo, $whereClause, $params, $allowedSortColumns, 'ip_address', $perPage, $currentPage);
?>
<?php include 'header.php'; ?>

<!-- Dashboard Summary (Graphs) -->
<div id="dashboardGraphs" class="dashboard-summary">
  <div class="dashboard-summary-header">
    <h3>Dashboard Summary</h3>
  </div>
  <div class="summary-cards">
    <div class="summary-card">
      <h4>Total Companies</h4>
      <p><?= count($userCompanies) ?></p>
    </div>
    <div class="summary-card">
      <h4>Total Types</h4>
      <p><?= count($typeChartData['labels']) ?></p>
    </div>
    <div class="summary-card">
      <h4>Total Locations</h4>
      <p><?= count($locationChartData['labels']) ?></p>
    </div>
    <div class="summary-card">
      <h4>Total IPs</h4>
      <p><?= $totalItems ?></p>
    </div>
    <div class="summary-card">
      <h4>Total Subnets</h4>
      <p><?= count($subnetChartData['labels']) ?></p>
    </div>
  </div>
  <div class="chart-grid">
    <div class="chart-cell">
      <canvas id="typeChart" width="<?= $typeChartWidth ?>" height="<?= $typeChartHeight ?>"></canvas>
    </div>
    <div class="chart-cell">
      <canvas id="companyChart" width="<?= $companyChartWidth ?>" height="<?= $companyChartHeight ?>"></canvas>
    </div>
    <div class="chart-cell">
      <canvas id="locationChart" width="<?= $locationChartWidth ?>" height="<?= $locationChartHeight ?>"></canvas>
    </div>
    <div class="chart-cell">
      <canvas id="subnetChart" width="<?= $subnetChartWidth ?>" height="<?= $subnetChartHeight ?>"></canvas>
    </div>
  </div>
</div>

<!-- IP List Section -->
<div id="ipListSection" class="container-content" style="display:none;">
  <div class="card">
    <div class="card-header">
      <?php
      // Calculate display range using a fixed per-page value
      $startIndex = ($currentPage - 1) * $perPage + 1;
      $endIndex = min($totalItems, $startIndex + $perPage - 1);
      ?>
      <h3 class="ip-list-title">
        📋 IP Address List - "Displaying <?= $startIndex ?> - <?= $endIndex ?> of <?= $totalItems ?> IPs"
      </h3>
      <div class="current-user"><?= htmlspecialchars((!empty($_SESSION['first_name']) ? $_SESSION['first_name'] : $_SESSION['username']) . " - " . $_SESSION['role']) ?></div>
    </div>
    <?php if(count($ips) > 0): ?>
    <table id="ipTable">
      <thead>
      <tr>
        <?php
        // Define headers with keys (these keys will be used as data attributes)
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
          'custom_fields'       => 'Custom Fields',
          'created_at'          => 'Created At',
          'last_updated'        => 'Last Updated',
          'created_by_username' => 'Created by'
        ];
        foreach ($headers as $column => $text): ?>
          <th data-col="<?= $column ?>">
            <?php if (in_array($column, $allowedSortColumns)): ?>
              <a href="?<?= http_build_query(array_merge($_GET, [
                'sort' => $column,
                'direction' => (($_GET['sort'] ?? '') === $column && ($_GET['direction'] ?? '') === 'ASC') ? 'DESC' : 'ASC'
              ])) ?>">
                <?= $text ?>
                <?php if ((isset($sort) && $sort === $column)): ?>
                  <?= (isset($direction) && $direction === 'ASC') ? '↑' : '↓' ?>
                <?php endif; ?>
              </a>
            <?php else: ?>
              <?= $text ?>
            <?php endif; ?>
          </th>
        <?php endforeach; ?>
        <?php if ($userRole !== 'guest'): ?>
          <th data-col="actions">Actions</th>
        <?php endif; ?>
      </tr>
      </thead>
      <tbody>
      <?php foreach($ips as $ip): ?>
      <tr>
        <td data-col="ip_address"><?= htmlspecialchars($ip['ip_address']) ?></td>
        <td data-col="subnet"><?= htmlspecialchars($ip['subnet'] ?? 'N/A') ?></td>
        <td data-col="status">
          <span class="status-badge status-<?= strtolower($ip['status']) ?>">
            <?= htmlspecialchars($ip['status']) ?>
          </span>
        </td>
        <td data-col="assigned_to"><?= htmlspecialchars($ip['assigned_to']) ?></td>
        <td data-col="owner"><?= htmlspecialchars($ip['owner']) ?></td>
        <td data-col="description"><?= htmlspecialchars($ip['description']) ?></td>
        <td data-col="type"><?= htmlspecialchars($ip['type']) ?></td>
        <td data-col="location"><?= htmlspecialchars($ip['location']) ?></td>
        <td data-col="company_name"><?= htmlspecialchars($ip['company_name'] ?? 'N/A') ?></td>
        <td data-col="custom_fields"><?= htmlspecialchars($ip['custom_fields'] ?? 'N/A') ?></td>
        <td data-col="created_at"><?= htmlspecialchars($ip['created_at']) ?></td>
        <td data-col="last_updated"><?= htmlspecialchars($ip['last_updated']) ?></td>
        <td data-col="created_by_username"><?= htmlspecialchars($ip['created_by_username']) ?></td>
        <?php if ($userRole !== 'guest'): ?>
        <td data-col="actions">
          <a href="manage_ip.php?action=edit&id=<?= $ip['id'] ?>" class="nav-btn">Edit</a>
          <a href="manage_ip.php?action=delete&id=<?= $ip['id'] ?>" class="nav-btn" onclick="return confirm('Are you sure you want to delete this IP?');">Delete</a>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="pagination">
      <?php if ($currentPage > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>">« Previous</a>
      <?php endif; ?>
      <span>Page <?= $currentPage ?> of <?= $totalPages ?></span>
      <?php if ($currentPage < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>">Next »</a>
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
        <label><input type="checkbox" data-col="9" checked> Custom Fields</label>
      </div>
      <div class="toggle-item">
        <label><input type="checkbox" data-col="10"> Created At</label>
      </div>
      <div class="toggle-item">
        <label><input type="checkbox" data-col="11"> Last Updated</label>
      </div>
      <div class="toggle-item">
        <label><input type="checkbox" data-col="12" checked> Created by</label>
      </div>
      <?php if ($userRole !== 'guest'): ?>
      <div class="toggle-item">
        <label><input type="checkbox" data-col="13" checked> Actions</label>
      </div>
      <?php endif; ?>
    </div>
    <div class="button-group">
      <button id="saveBtn" class="nav-btn">Save</button>
      <button id="restoreBtn" class="nav-btn">Restore Default View</button>
    </div>
  </div>
</div>

<!-- Column dragging functionality -->
<script src="column_sorting.js"></script>

<!-- Include custom JS for other functionality -->
<script src="column_modal.js"></script>

<?php include 'footer.php'; ?>
