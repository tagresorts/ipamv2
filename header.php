<?php
// header.php - Cleaned version (business logic removed; assumed variables are set in dashboard.php)
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
  <link rel="stylesheet" href="modal.css">
  <!-- Chart.js Library -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-piechart-outlabels@1.0.2/dist/chartjs-plugin-piechart-outlabels.min.js"></script>
  <style>
    /* Inline styles from original dashboard.php */
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
    .summary-card { flex: 1; padding: 0px; margin: 3px; border: 1px solid #ccc; border-radius: 5px; text-align: center; background-color: #fff; font-size: 0.9em; }
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
  <!-- Navigation Bar -->
  <div class="navbar">
    <div class="navbar-container">
      <div class="logo">
        <img src="ryan_logo.png" alt="Logo" style="max-height:50px;">
      </div>
      <div class="nav-links">
        <button id="toggleViewButton" class="nav-btn">Show IP List</button>
        <?php if ($userRole !== 'guest'): ?>
          <a href="manage_ip.php?action=add" class="nav-btn">➕ Manage IP</a>
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
  <!-- Filter Bar -->
  <div class="filter-bar no-print">
    <div class="filter-bar-container">
      <form method="GET" class="filter-form" onsubmit="document.getElementById('loadingOverlay').style.display='block'">
        <label for="search">Search:</label>
        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="IP, Assigned To, Owner, Description, Location or Type...">
        <label for="company">Company:</label>
        <select name="company" id="company">
          <option value="">-- All Companies --</option>
          <?php if(isset($userCompanies)): ?>
          <?php foreach ($userCompanies as $company): ?>
            <option value="<?= $company['company_id'] ?>" <?= (isset($companyFilter) && $companyFilter == $company['company_id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($company['company_name']) ?>
            </option>
          <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <button type="submit" class="nav-btn">Filter</button>
        <a href="dashboard.php" class="nav-btn">Reset</a>
      </form>
      <div class="filter-actions">
        <?php if (isset($userRole) && $userRole !== 'guest'): ?>
          <a href="bulk_upload.php" class="nav-btn">📤 Upload</a>
        <?php endif; ?>
        <a href="export_ips.php?<?= http_build_query($_GET) ?>" class="nav-btn">📊 Export</a>
        <button type="button" onclick="window.print()" class="nav-btn">🖨 Print</button>
        <button type="button" id="toggleColumnsBtn" class="nav-btn">📑 Columns</button>
        <?php if (isset($userRole) && $userRole !== 'guest'): ?>
          <a href="scheduler_manager.php" class="nav-btn">🗄 Cron</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
