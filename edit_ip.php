<?php
session_start();
include 'config.php';

// Restrict access to admin users only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Check for the IP record id in GET parameters
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$ip_id = $_GET['id'];

// Retrieve the IP record from the database
$stmt = $pdo->prepare("SELECT * FROM ips WHERE id = ?");
$stmt->execute([$ip_id]);
$ip_record = $stmt->fetch();

if (!$ip_record) {
    // If record doesn't exist, redirect with an error message
    header("Location: dashboard.php");
    exit;
}

// Fetch subnets for the dropdown list
$subnets = $pdo->query("SELECT * FROM subnets")->fetchAll();

// Load dropdown options for "types" and "locations" (as in add_ip.php)
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

// Fetch existing custom fields for this IP
$cfStmt = $pdo->prepare("SELECT * FROM custom_fields WHERE ip_id = ?");
$cfStmt->execute([$ip_id]);
$custom_fields = $cfStmt->fetchAll();

// Handle form submission to update the record
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve updated fields from the form
    $ip_address   = $_POST['ip_address'];
    $subnet_id    = $_POST['subnet_id'];
    $status       = $_POST['status'];
    $description  = $_POST['description'];
    $assigned_to  = $_POST['assigned_to'];
    $owner        = $_POST['owner'];
    $type         = $_POST['type'];
    $location     = $_POST['location'];
    
    try {
        // Update the main IP record
        $stmt = $pdo->prepare("
            UPDATE ips 
            SET ip_address = ?, subnet_id = ?, status = ?, description = ?, assigned_to = ?, owner = ?, type = ?, location = ?
            WHERE id = ?
        ");
        $stmt->execute([$ip_address, $subnet_id, $status, $description, $assigned_to, $owner, $type, $location, $ip_id]);

        // Update custom fields: delete existing ones and re-insert new entries
        $pdo->prepare("DELETE FROM custom_fields WHERE ip_id = ?")->execute([$ip_id]);
        if (isset($_POST['custom_field_name']) && is_array($_POST['custom_field_name'])) {
            $names = $_POST['custom_field_name'];
            $values = $_POST['custom_field_value'];
            for ($i = 0; $i < count($names); $i++) {
                $field_name = trim($names[$i]);
                $field_value = trim($values[$i]);
                if (!empty($field_name)) {
                    $cfInsertStmt = $pdo->prepare("INSERT INTO custom_fields (ip_id, field_name, field_value) VALUES (?, ?, ?)");
                    $cfInsertStmt->execute([$ip_id, $field_name, $field_value]);
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
    <title>Edit IP - IP Management System</title>
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
        <h1>Edit IP Record</h1>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
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
            <!-- Custom Fields Section -->
            <div id="customFieldsContainer" class="form-group full-width">
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
            <div class="form-group full-width">
                <button type="button" onclick="addCustomField()" class="btn">Add Custom Field</button>
            </div>
            <div class="form-group full-width">
                <button type="submit" class="btn">Save Changes</button>
                <a href="dashboard.php" class="btn">Cancel</a>
            </div>
        </form>
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
