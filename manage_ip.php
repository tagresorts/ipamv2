<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Determine current action: list, add, edit, delete
$action = $_GET['action'] ?? 'list';
$error = '';

// Redirect to dashboard if action is list (Option 2)
if ($action === 'list') {
    header("Location: dashboard.php");
    exit;
}

// ------------------------------------------------------------------
// MULTI‑TENANCY SETUP: Determine which companies the user can access
// ------------------------------------------------------------------
if ($_SESSION['role'] === 'admin') {
    // Admin sees all companies
    $stmt = $pdo->query("SELECT company_id FROM companies");
    $companiesForQuery = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $allCompanies = $pdo->query("SELECT * FROM companies ORDER BY company_name ASC")->fetchAll();
} else {
    // Non-admin: only assigned companies
    $stmt = $pdo->prepare("SELECT company_id FROM user_companies WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $companiesForQuery = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $allCompanies = []; // not used in non-admin for add/edit
}
if (empty($companiesForQuery)) {
    $companiesForQuery = [0]; // Prevent query errors if no companies
}
// For non-admin, default company is first in their list
$default_company_id = $companiesForQuery[0];

// ------------------------------------------------------------------
// Load auxiliary data (dropdown options, subnets)
// ------------------------------------------------------------------
$folder = "data";
$filePath = $folder . "/dropdown_options.json";
$defaultOptions = [
    "types" => ["Router", "Server", "Switch", "Firewall", "Workstation", "Other"],
    "locations" => ["Data Center", "Office", "Remote", "Warehouse", "Other"]
];
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}
if (file_exists($filePath)) {
    $jsonData = file_get_contents($filePath);
    $options = json_decode($jsonData, true);
    if ($options === null) {
        $options = $defaultOptions;
    }
} else {
    $options = $defaultOptions;
}
$subnets = $pdo->query("SELECT * FROM subnets")->fetchAll();

// ------------------------------------------------------------------
// Process Actions: add, edit, delete
// ------------------------------------------------------------------

// ----------------------
// ADD NEW IP
// ----------------------
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip_address'];
    $subnet_id = $_POST['subnet_id'];
    $status = $_POST['status'];
    $description = $_POST['description'];
    $assigned_to = $_POST['assigned_to'];
    $owner = $_POST['owner'];
    $type = $_POST['type'];
    $location = $_POST['location'];
    // For admin, allow company selection; for non-admin, auto-assign default
    if ($_SESSION['role'] === 'admin' && !empty($_POST['company_id'])) {
        $company_id = $_POST['company_id'];
    } else {
        $company_id = $default_company_id;
    }

    $stmt = $pdo->prepare("
        INSERT INTO ips
        (ip_address, subnet_id, status, description, assigned_to, owner, type, location, created_by, company_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    try {
        $stmt->execute([$ip, $subnet_id, $status, $description, $assigned_to, $owner, $type, $location, $_SESSION['user_id'], $company_id]);
        $ip_id = $pdo->lastInsertId();

        // Insert custom fields if provided
        if (isset($_POST['custom_field_name']) && is_array($_POST['custom_field_name'])) {
            $names = $_POST['custom_field_name'];
            $values = $_POST['custom_field_value'];
            for ($i = 0; $i < count($names); $i++) {
                $field_name = trim($names[$i]);
                $field_value = trim($values[$i]);
                if (!empty($field_name)) {
                    $cfStmt = $pdo->prepare("INSERT INTO custom_fields (ip_id, field_name, field_value) VALUES (?, ?, ?)");
                    $cfStmt->execute([$ip_id, $field_name, $field_value]);
                }
            }
        }
        header("Location: dashboard.php");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// ----------------------
// EDIT EXISTING IP
// ----------------------
if ($action === 'edit') {
    if (!isset($_GET['id'])) {
        die("No IP ID provided.");
    }
    $ip_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM ips WHERE id = ?");
    $stmt->execute([$ip_id]);
    $ip_record = $stmt->fetch();
    if (!$ip_record) {
        die("IP record not found.");
    }
    // Fetch existing custom fields
    $cfStmt = $pdo->prepare("SELECT * FROM custom_fields WHERE ip_id = ?");
    $cfStmt->execute([$ip_id]);
    $custom_fields = $cfStmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ip_address = $_POST['ip_address'];
        $subnet_id = $_POST['subnet_id'];
        $status = $_POST['status'];
        $description = $_POST['description'];
        $assigned_to = $_POST['assigned_to'];
        $owner = $_POST['owner'];
        $type = $_POST['type'];
        $location = $_POST['location'];
        if ($_SESSION['role'] === 'admin' && !empty($_POST['company_id'])) {
            $company_id = $_POST['company_id'];
        } else {
            $company_id = $ip_record['company_id'];
        }

        $stmt = $pdo->prepare("
            UPDATE ips
            SET ip_address = ?, subnet_id = ?, status = ?, description = ?, assigned_to = ?, owner = ?, type = ?, location = ?, company_id = ?
            WHERE id = ?
        ");
        try {
            $stmt->execute([$ip_address, $subnet_id, $status, $description, $assigned_to, $owner, $type, $location, $company_id, $ip_id]);
            // Delete old custom fields and insert updated ones
            $pdo->prepare("DELETE FROM custom_fields WHERE ip_id = ?")->execute([$ip_id]);
            if (isset($_POST['custom_field_name']) && is_array($_POST['custom_field_name'])) {
                $names = $_POST['custom_field_name'];
                $values = $_POST['custom_field_value'];
                for ($i = 0; $i < count($names); $i++) {
                    $field_name = trim($names[$i]);
                    $field_value = trim($values[$i]);
                    if (!empty($field_name)) {
                        $cfStmt = $pdo->prepare("INSERT INTO custom_fields (ip_id, field_name, field_value) VALUES (?, ?, ?)");
                        $cfStmt->execute([$ip_id, $field_name, $field_value]);
                    }
                }
            }
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ----------------------
// DELETE IP
// ----------------------
if ($action === 'delete') {
    if (!isset($_GET['id'])) {
        die("No IP ID provided for deletion.");
    }
    $ip_id = $_GET['id'];
    try {
        $pdo->prepare("DELETE FROM custom_fields WHERE ip_id = ?")->execute([$ip_id]);
        $pdo->prepare("DELETE FROM ips WHERE id = ?")->execute([$ip_id]);
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
  <title>Manage IPs - IP Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .container { width: 90%; max-width: 1200px; margin: 20px auto; }
    .form-container { display: flex; flex-wrap: wrap; gap: 0px; }
    .form-group { flex: 1 1 45%; min-width: 300px; }
    .full-width { flex: 1 1 100%; }
    #customFieldsContainer { width: 100%; }
    .custom-field-row { display: flex; gap: 10px; margin-bottom: 10px; }
    .custom-field-row input { flex: 1; }
  </style>
</head>
<body>
  <?php $isAdmin = ($_SESSION['role'] === 'admin'); ?>
  <div class="nav">
    <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
    <a href="manage_json.php" class="nav-btn" <?php if (!$isAdmin) echo 'onclick="alert(\'Only admins are allowed to manage hardware types and locations, Iyak ka muna!!\'); return false;"'; ?>>
     Location/H-Types
    </a>
  </div>
  <div class="container">
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add'): ?>
      <h1>Add New IP Address</h1>
      <form method="POST" class="form-container">
        <div class="form-group">
          <label for="ip_address">IP Address:</label>
          <input type="text" id="ip_address" name="ip_address" required>
        </div>
        <div class="form-group">
          <label for="subnet_id">Subnet:</label>
          <select name="subnet_id" id="subnet_id">
            <?php foreach ($subnets as $subnet): ?>
              <option value="<?= $subnet['id'] ?>"><?= htmlspecialchars($subnet['subnet']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="status">Status:</label>
          <select name="status" id="status">
            <option value="Available">Available</option>
            <option value="Reserved">Reserved</option>
            <option value="Assigned">Assigned</option>
            <option value="Expired">Expired</option>
          </select>
        </div>
        <div class="form-group">
          <label for="description">Description:</label>
          <input type="text" id="description" name="description">
        </div>
        <div class="form-group">
          <label for="assigned_to">Assigned To:</label>
          <input type="text" id="assigned_to" name="assigned_to">
        </div>
        <div class="form-group">
          <label for="owner">Owner:</label>
          <input type="text" id="owner" name="owner">
        </div>
        <div class="form-group">
          <label for="type">Type (Hardware):</label>
          <select name="type" id="type" required>
            <option value="">-- Select Type --</option>
            <?php foreach ($options['types'] as $typeOption): ?>
              <option value="<?= htmlspecialchars($typeOption) ?>"><?= htmlspecialchars($typeOption) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="location">Location (Deployment Site):</label>
          <select name="location" id="location" required>
            <option value="">-- Select Location --</option>
            <?php foreach ($options['locations'] as $locationOption): ?>
              <option value="<?= htmlspecialchars($locationOption) ?>"><?= htmlspecialchars($locationOption) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="form-group">
          <label for="company_id">Company:</label>
          <select name="company_id" id="company_id" required>
            <option value="">-- Select Company --</option>
            <?php foreach ($allCompanies as $comp): ?>
              <option value="<?= $comp['company_id'] ?>"><?= htmlspecialchars($comp['company_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php else: ?>
          <input type="hidden" name="company_id" value="<?= $default_company_id ?>">
        <?php endif; ?>
        <!-- Custom Fields -->
        <div id="customFieldsContainer" class="full-width">
          <h3>Custom Fields</h3>
          <div class="custom-field-row">
            <input type="text" name="custom_field_name[]" placeholder="Field Name">
            <input type="text" name="custom_field_value[]" placeholder="Field Value">
          </div>
        </div>
        <div class="full-width">
          <button type="button" onclick="addCustomField()" class="btn">Add Custom Field</button>
        </div>
        <div class="full-width">
          <button type="submit" class="btn">Add IP</button>
        </div>
      </form>

    <?php elseif ($action === 'edit'): ?>
      <h1>Edit IP Record</h1>
      <?php if (!isset($ip_record)) { die("IP record not found."); } ?>
      <form method="POST" class="form-container">
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
          <select name="type" id="type" required>
            <option value="">-- Select Type --</option>
            <?php foreach ($options['types'] as $typeOption): ?>
              <option value="<?= htmlspecialchars($typeOption) ?>" <?= $ip_record['type'] === $typeOption ? 'selected' : '' ?>>
                <?= htmlspecialchars($typeOption) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="location">Location (Deployment Site):</label>
          <select name="location" id="location" required>
            <option value="">-- Select Location --</option>
            <?php foreach ($options['locations'] as $locationOption): ?>
              <option value="<?= htmlspecialchars($locationOption) ?>" <?= $ip_record['location'] === $locationOption ? 'selected' : '' ?>>
                <?= htmlspecialchars($locationOption) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="form-group">
          <label for="company_id">Company:</label>
          <select name="company_id" id="company_id" required>
            <option value="">-- Select Company --</option>
            <?php foreach ($allCompanies as $comp): ?>
              <option value="<?= $comp['company_id'] ?>" <?= ($ip_record['company_id'] == $comp['company_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($comp['company_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php else: ?>
          <input type="hidden" name="company_id" value="<?= $ip_record['company_id'] ?>">
        <?php endif; ?>
        <!-- Custom Fields Section -->
        <div id="customFieldsContainer" class="full-width">
          <h3>Custom Fields</h3>
          <?php if (!empty($custom_fields)): ?>
              <?php foreach ($custom_fields as $field): ?>
                  <div class="custom-field-row">
                      <input type="text" name="custom_field_name[]" placeholder="Field Name" value="<?= htmlspecialchars($field['field_name']) ?>">
                      <input type="text" name="custom_field_value[]" placeholder="Field Value" value="<?= htmlspecialchars($field['field_value']) ?>">
                  </div>
              <?php endforeach; ?>
          <?php else: ?>
              <div class="custom-field-row">
                  <input type="text" name="custom_field_name[]" placeholder="Field Name">
                  <input type="text" name="custom_field_value[]" placeholder="Field Value">
              </div>
          <?php endif; ?>
        </div>
        <div class="full-width">
          <button type="button" onclick="addCustomField()" class="btn">Add Custom Field</button>
        </div>
        <div class="full-width">
          <button type="submit" class="btn">Save Changes</button>
          <a href="dashboard.php" class="btn">Cancel</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
  <script>
    function addCustomField() {
      var container = document.getElementById("customFieldsContainer");
      var div = document.createElement("div");
      div.className = "custom-field-row";
      div.innerHTML = '<input type="text" name="custom_field_name[]" placeholder="Field Name"> <input type="text" name="custom_field_value[]" placeholder="Field Value">';
      container.appendChild(div);
    }
  </script>
</body>
</html>
