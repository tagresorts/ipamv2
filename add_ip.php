<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Define folder and file path for dropdown options (for later use in JSON editor)
$folder = "data";
$filePath = $folder . "/dropdown_options.json";
$defaultOptions = [
    "types" => ["Router", "Server", "Switch", "Firewall", "Workstation", "Other"],
    "locations" => ["Data Center", "Office", "Remote", "Warehouse", "Other"]
];

// Ensure the folder exists (the JSON editor will handle file creation if needed)
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

// Load dropdown options from the JSON file; if not available, use defaults
if (file_exists($filePath)) {
    $jsonData = file_get_contents($filePath);
    $options = json_decode($jsonData, true);
    if ($options === null) {
        $options = $defaultOptions;
    }
} else {
    $options = $defaultOptions;
}

// Get existing subnets for dropdown
$subnets = $pdo->query("SELECT * FROM subnets")->fetchAll();

// Handle form submission for adding a new IP
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip_address'];
    $subnet_id = $_POST['subnet_id'];
    $status = $_POST['status'];
    $description = $_POST['description'];
    $assigned_to = $_POST['assigned_to'];
    $owner = $_POST['owner'];
    $type = $_POST['type'];
    $location = $_POST['location'];
    
    // Insert main IP record, including created_by (current user)
    $stmt = $pdo->prepare("
        INSERT INTO ips 
        (ip_address, subnet_id, status, description, assigned_to, owner, type, location, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    try {
        $stmt->execute([$ip, $subnet_id, $status, $description, $assigned_to, $owner, $type, $location, $_SESSION['user_id']]);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add IP - IP Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <style>
      /* Fluid container layout */
      .container {
        width: 90%;
        max-width: 1200px;
        margin: 20px auto;
      }
      .form-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
      }
      .form-group {
        flex: 1 1 45%;
        min-width: 300px;
      }
      .full-width {
        flex: 1 1 100%;
      }
      #customFieldsContainer {
        width: 100%;
      }
      .custom-field-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
      }
      .custom-field-row input {
        flex: 1;
      }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
    </div>

    <div class="container">
        <h1>Add New IP Address</h1>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Admin-only link to open JSON editor -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="full-width" style="margin-bottom: 20px;">
                <a href="manage_dropdown.php" class="btn">Manage Dropdown Options</a>
            </div>
        <?php endif; ?>

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
                <?php if (empty($subnets)): ?>
                    <p>No subnets found. <a href="subnets.php">Create one first</a></p>
                <?php endif; ?>
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

            <!-- Custom Fields Section -->
            <div id="customFieldsContainer" class="form-group full-width">
                <h3>Custom Fields</h3>
                <div class="custom-field-row">
                    <input type="text" name="custom_field_name[]" placeholder="Field Name">
                    <input type="text" name="custom_field_value[]" placeholder="Field Value">
                </div>
            </div>
            <div class="form-group full-width">
                <button type="button" onclick="addCustomField()" class="btn">Add Custom Field</button>
            </div>
            <div class="form-group full-width">
                <button type="submit" class="btn">Add IP</button>
            </div>
        </form>
    </div>
</body>
</html>
