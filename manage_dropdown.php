<?php
session_start();
include 'config.php';

// Only admin users can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Define the subfolder and file path.
$folder = "data";
$filePath = $folder . "/dropdown_options.json";

// Default options.
$defaultOptions = [
    "types" => ["Router", "Server", "Switch", "Firewall", "Workstation", "Other"],
    "locations" => ["Data Center", "Office", "Remote", "Warehouse", "Other"]
];

// Ensure the folder exists.
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

// If the JSON file doesn't exist, create it with default values.
if (!file_exists($filePath)) {
    file_put_contents($filePath, json_encode($defaultOptions, JSON_PRETTY_PRINT));
}

$message = "";
$error = "";

// Process form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get submitted arrays; filter out empty values.
    $types = isset($_POST['types']) ? array_values(array_filter($_POST['types'], function($val) {
        return trim($val) !== "";
    })) : [];
    $locations = isset($_POST['locations']) ? array_values(array_filter($_POST['locations'], function($val) {
        return trim($val) !== "";
    })) : [];
    
    // Build new options array.
    $newOptions = [
        "types" => $types,
        "locations" => $locations
    ];
    
    // Save the JSON file.
    if (file_put_contents($filePath, json_encode($newOptions, JSON_PRETTY_PRINT)) !== false) {
        $message = "Dropdown options saved successfully.";
    } else {
        $error = "Failed to save dropdown options.";
    }
}

// Reload current options.
$jsonData = file_get_contents($filePath);
$options = json_decode($jsonData, true);
if ($options === null) {
    $options = $defaultOptions;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Dropdown Options - IP Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <style>
      .container {
          width: 90%;
          max-width: 1200px;
          margin: 20px auto;
      }
      h1, h2 {
          text-align: center;
      }
      .alert {
          padding: 10px;
          margin-bottom: 20px;
          border-radius: 4px;
      }
      .alert.error {
          background-color: #f8d7da;
          color: #721c24;
      }
      .alert.success {
          background-color: #d4edda;
          color: #155724;
      }
      table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 20px;
      }
      table, th, td {
          border: 1px solid #ddd;
      }
      th, td {
          padding: 8px;
          text-align: left;
      }
      .btn {
          padding: 8px 12px;
          background-color: var(--primary-color);
          color: #fff;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          margin-right: 5px;
      }
      .btn:hover {
          background-color: var(--primary-dark);
      }
      .add-btn {
          margin-top: 10px;
      }
      .form-container {
          display: flex;
          flex-direction: column;
          gap: 20px;
      }
      .section {
          margin-bottom: 40px;
      }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
    </div>
    <div class="container">
        <h1>Manage Dropdown Options</h1>
        <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="form-container">
            <!-- Section for Hardware Types -->
            <div class="section">
                <h2>Hardware Types</h2>
                <table id="typesTable">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($options['types'] as $type): ?>
                        <tr>
                            <td>
                                <input type="text" name="types[]" value="<?= htmlspecialchars($type) ?>" style="width: 100%;">
                            </td>
                            <td>
                                <button type="button" class="btn remove-row">Remove</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" id="addTypeBtn" class="btn add-btn">Add Type</button>
            </div>
            
            <!-- Section for Deployment Locations -->
            <div class="section">
                <h2>Deployment Locations</h2>
                <table id="locationsTable">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($options['locations'] as $location): ?>
                        <tr>
                            <td>
                                <input type="text" name="locations[]" value="<?= htmlspecialchars($location) ?>" style="width: 100%;">
                            </td>
                            <td>
                                <button type="button" class="btn remove-row">Remove</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" id="addLocationBtn" class="btn add-btn">Add Location</button>
            </div>
            
            <div>
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </form>
    </div>
    
    <script>
        // Function to add a new row to a table body.
        function addRow(tableId, inputName) {
            var tableBody = document.getElementById(tableId).getElementsByTagName('tbody')[0];
            var newRow = document.createElement('tr');
            newRow.innerHTML = '<td><input type="text" name="' + inputName + '[]" style="width: 100%;"></td>' +
                               '<td><button type="button" class="btn remove-row">Remove</button></td>';
            tableBody.appendChild(newRow);
        }
        
        document.getElementById('addTypeBtn').addEventListener('click', function(){
            addRow('typesTable', 'types');
        });
        document.getElementById('addLocationBtn').addEventListener('click', function(){
            addRow('locationsTable', 'locations');
        });
        
        // Event delegation for remove buttons.
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-row')) {
                var row = e.target.closest('tr');
                row.parentNode.removeChild(row);
            }
        });
    </script>
</body>
</html>
