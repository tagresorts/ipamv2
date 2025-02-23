<?php
session_start();
include 'config.php';

// Ensure the user is logged in (optionally restrict to admin if desired)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uploadError = "";
$uploadMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a file is uploaded and there is no error
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $csvFile = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($csvFile, "r")) !== false) {
            // Assume first row is header
            $header = fgetcsv($handle, 1000, ",");
            // Normalize header: lower-case and trim spaces
            $header = array_map(function($col) {
                return strtolower(trim($col));
            }, $header);
            
            // Expected header columns: ip_address, subnet, status, description, assigned_to, owner, type, location
            $requiredColumns = ["ip_address", "subnet", "status", "description", "assigned_to", "owner", "type", "location"];
            $missingColumns = array_diff($requiredColumns, $header);
            if (count($missingColumns) > 0) {
                $uploadError = "CSV file is missing required columns: " . implode(", ", $missingColumns);
            } else {
                $rowCount = 0;
                $insertCount = 0;
                $errorRows = [];
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $rowCount++;
                    // Create associative array for the row
                    $row = array_combine($header, $data);
                    
                    // Validate required fields
                    if (empty($row['ip_address']) || empty($row['subnet'])) {
                        $errorRows[] = $rowCount;
                        continue;
                    }
                    
                    $ip_address = trim($row['ip_address']);
                    $subnetName = trim($row['subnet']);
                    $status = !empty($row['status']) ? trim($row['status']) : "Available";
                    $description = !empty($row['description']) ? trim($row['description']) : "";
                    $assigned_to = !empty($row['assigned_to']) ? trim($row['assigned_to']) : "";
                    $owner = !empty($row['owner']) ? trim($row['owner']) : "";
                    $type = !empty($row['type']) ? trim($row['type']) : "Unknown";
                    $location = !empty($row['location']) ? trim($row['location']) : "Not Specified";
                    
                    // Look up subnet_id based on the subnet name
                    $stmt = $pdo->prepare("SELECT id FROM subnets WHERE subnet = ?");
                    $stmt->execute([$subnetName]);
                    $subnetRow = $stmt->fetch();
                    if ($subnetRow) {
                        $subnet_id = $subnetRow['id'];
                    } else {
                        // If subnet not found, skip this row
                        $errorRows[] = $rowCount;
                        continue;
                    }
                    
                    // Insert the IP record
                    $insertStmt = $pdo->prepare("
                        INSERT INTO ips 
                        (ip_address, subnet_id, status, description, assigned_to, owner, type, location, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    try {
                        $insertStmt->execute([$ip_address, $subnet_id, $status, $description, $assigned_to, $owner, $type, $location, $_SESSION['user_id']]);
                        $insertCount++;
                    } catch (PDOException $e) {
                        $errorRows[] = $rowCount;
                        continue;
                    }
                }
                fclose($handle);
                $uploadMessage = "Bulk upload complete. Processed {$rowCount} rows; inserted {$insertCount} records.";
                if (!empty($errorRows)) {
                    $uploadMessage .= " Errors on rows: " . implode(", ", $errorRows) . ".";
                }
            }
        } else {
            $uploadError = "Could not open the CSV file.";
        }
    } else {
        $uploadError = "No file uploaded or file upload error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Upload - Ryan's IPAM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <style>
      .container {
          width: 90%;
          max-width: 800px;
          margin: 20px auto;
      }
      .form-container {
          padding: 20px;
          background-color: #fff;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      .form-group {
          margin-bottom: 20px;
      }
      .btn {
          padding: 10px 20px;
          background-color: var(--primary-color);
          color: #fff;
          border: none;
          border-radius: 4px;
          cursor: pointer;
      }
    </style>
</head>
<body>
<div class="nav">
    <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
</div>
<div class="container">
    <h1>Bulk Upload IP Addresses</h1>
    <!-- Download CSV Template Button -->
    <div style="margin-bottom: 20px;">
        <a href="download_template.php" class="btn">Download CSV Template</a>
    </div>
    <?php if ($uploadError): ?>
        <div class="alert error"><?= htmlspecialchars($uploadError) ?></div>
    <?php endif; ?>
    <?php if ($uploadMessage): ?>
        <div class="alert success"><?= htmlspecialchars($uploadMessage) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="form-container">
        <div class="form-group">
            <label for="csv_file">Select CSV File:</label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn">Upload CSV</button>
        </div>
    </form>
    <p>CSV Format: The first row should contain the headers: <strong>ip_address, subnet, status, description, assigned_to, owner, type, location</strong></p>
</div>
</body>
</html>
