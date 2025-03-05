<?php
session_start();
include 'config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Retrieve allowed company IDs for the current user
$stmt = $pdo->prepare("SELECT company_id FROM user_companies WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userCompanies = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($userCompanies)) {
    $userCompanies = [0];
}

$uploadError = "";
$uploadMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded without error
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0) {
        $csvFile = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($csvFile, "r")) !== false) {
            // Read and normalize the header row
            $header = fgetcsv($handle, 1000, ",");
            if ($header) {
                $header = array_map('trim', $header);
                $header = array_map('strtolower', $header);
            }
            // Required columns for multi‑tenant upload
            $requiredCols = [
                "ip_address", "subnet", "status", "description",
                "assigned_to", "owner", "type", "location", "company_id"
            ];
            $missing = array_diff($requiredCols, $header ?? []);
            if (!empty($missing)) {
                $uploadError = "CSV file is missing required columns: " . implode(", ", $missing);
            } else {
                $rowCount = 0;
                $insertCount = 0;
                $errorRows = [];
                // Process each subsequent row
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $rowCount++;
                    $row = array_combine($header, $data);

                    // Basic validation: ip_address, subnet and company_id are required
                    if (empty($row['ip_address']) || empty($row['subnet']) || empty($row['company_id'])) {
                        $errorRows[] = $rowCount;
                        continue;
                    }

                    $ip_address  = trim($row['ip_address']);
                    $subnetName  = trim($row['subnet']);
                    $status      = !empty($row['status'])      ? trim($row['status'])      : "Available";
                    $description = !empty($row['description']) ? trim($row['description']) : "";
                    $assigned_to = !empty($row['assigned_to']) ? trim($row['assigned_to']) : "";
                    $owner       = !empty($row['owner'])       ? trim($row['owner'])       : "";
                    $type        = !empty($row['type'])        ? trim($row['type'])        : "Unknown";
                    $location    = !empty($row['location'])    ? trim($row['location'])    : "Not Specified";
                    $company_id  = trim($row['company_id']);

                    // Verify that the company_id is allowed for the current user
                    if (!in_array($company_id, $userCompanies)) {
                        $errorRows[] = $rowCount;
                        continue;
                    }

                    // Look up subnet_id using both the subnet name and company_id
                    $stmtSub = $pdo->prepare("SELECT id FROM subnets WHERE subnet = ? AND company_id = ?");
                    $stmtSub->execute([$subnetName, $company_id]);
                    $subnetData = $stmtSub->fetch(PDO::FETCH_ASSOC);
                    if (!$subnetData) {
                        $errorRows[] = $rowCount;
                        continue;
                    }
                    $subnet_id = $subnetData['id'];

                    // Insert the IP record
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO ips 
                        (ip_address, subnet_id, status, description, assigned_to, owner, type, location, created_by, company_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    try {
                        $stmtInsert->execute([
                            $ip_address, $subnet_id, $status, $description,
                            $assigned_to, $owner, $type, $location,
                            $_SESSION['user_id'], $company_id
                        ]);
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
    <title>Bulk Upload - IP Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <!-- External Stylesheet -->
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
      <p>CSV Format: The first row should contain the headers: <strong>ip_address, subnet, status, description, assigned_to, owner, type, location, company_id</strong></p>
  </div>
</body>
</html>
