<?php
session_start();
include 'config.php';

// Only administrators can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Function to check if a company can be deleted.
// It checks dependent tables (ip_addresses, subnets, user_companies) to ensure safe deletion.
function canDeleteCompany($company_id, $pdo) {
    // Check in ip_addresses table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ip_addresses WHERE company_id = ?");
    $stmt->execute([$company_id]);
    if ($stmt->fetchColumn() > 0) return false;

    // Check in subnets table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subnets WHERE company_id = ?");
    $stmt->execute([$company_id]);
    if ($stmt->fetchColumn() > 0) return false;

    // Check in user_companies table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_companies WHERE company_id = ?");
    $stmt->execute([$company_id]);
    if ($stmt->fetchColumn() > 0) return false;

    return true;
}

// Handle company creation and editing
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name']);
    $company_description = trim($_POST['company_description']);
    $enrollment_options = trim($_POST['enrollment_options']);
    $status = $_POST['status'] ?? 'Active';
    $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : null;

    if (empty($company_name)) {
        $error = "Company name is required.";
    } else {
        if ($company_id) {
            // Update existing company
            $stmt = $pdo->prepare("UPDATE companies SET company_name = ?, description = ?, enrollment_options = ?, status = ? WHERE company_id = ?");
            $stmt->execute([$company_name, $company_description, $enrollment_options, $status, $company_id]);
        } else {
            // Insert new company
            $stmt = $pdo->prepare("INSERT INTO companies (company_name, description, enrollment_options, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$company_name, $company_description, $enrollment_options, $status]);
        }
        header("Location: manage_companies.php");
        exit;
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $del_company_id = intval($_GET['delete']);
    if (canDeleteCompany($del_company_id, $pdo)) {
        $deleteStmt = $pdo->prepare("DELETE FROM companies WHERE company_id = ?");
        $deleteStmt->execute([$del_company_id]);
    } else {
        $error = "Cannot delete company in use.";
    }
}

// Fetch companies for display
$companies = $pdo->query("SELECT * FROM companies ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Companies - IP Management System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Basic table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        /* Button styling */
        .btn {
            padding: 6px 12px;
            background-color: #007BFF;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 5px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .disabled {
            background-color: #ccc;
            pointer-events: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert.error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Companies</h1>
        <!-- Navigation Buttons -->
        <div style="margin-bottom: 15px;">
            <a href="dashboard.php" class="btn">Return Home</a>
        </div>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <!-- Company Form for Add/Edit -->
        <form method="POST">
            <input type="hidden" name="company_id" id="company_id" value="">
            <div class="form-group">
                <label for="company_name">Company Name:</label>
                <input type="text" name="company_name" id="company_name" required>
            </div>
            <div class="form-group">
                <label for="company_description">Description:</label>
                <input type="text" name="company_description" id="company_description">
            </div>
            <div class="form-group">
                <label for="enrollment_options">Enrollment Options:</label>
                <textarea name="enrollment_options" id="enrollment_options" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn">Save Company</button>
            <button type="button" class="btn" onclick="resetForm()">Clear Form</button>
        </form>
        <h2>Existing Companies</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Company Name</th>
                    <th>Description</th>
                    <th>Enrollment Options</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $company): ?>
                    <tr>
                        <td><?= htmlspecialchars($company['company_id']) ?></td>
                        <td><?= htmlspecialchars($company['company_name']) ?></td>
                        <td><?= htmlspecialchars($company['description'] ?? '') ?></td>
                        <td><?= htmlspecialchars($company['enrollment_options'] ?? '') ?></td>
                        <td><?= htmlspecialchars($company['status']) ?></td>
                        <td><?= htmlspecialchars($company['created_at']) ?></td>
                        <td><?= htmlspecialchars($company['updated_at'] ?? '') ?></td>
                        <td>
                            <button class="btn" onclick="editCompany('<?= $company['company_id'] ?>', '<?= addslashes($company['company_name']) ?>', '<?= addslashes($company['description'] ?? '') ?>', '<?= addslashes($company['enrollment_options'] ?? '') ?>', '<?= $company['status'] ?>')">Edit</button>
                            <?php if (canDeleteCompany($company['company_id'], $pdo) && $company['status'] !== 'Active'): ?>
                                <a href="?delete=<?= $company['company_id'] ?>" class="btn" onclick="return confirm('Are you sure you want to delete this company?');">Delete</a>
                            <?php else: ?>
                                <span class="btn disabled">Delete</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        function editCompany(id, name, description, enrollment_options, status) {
            document.getElementById('company_id').value = id;
            document.getElementById('company_name').value = name;
            document.getElementById('company_description').value = description;
            document.getElementById('enrollment_options').value = enrollment_options;
            document.getElementById('status').value = status;
        }
        function resetForm() {
            document.getElementById('company_id').value = '';
            document.getElementById('company_name').value = '';
            document.getElementById('company_description').value = '';
            document.getElementById('enrollment_options').value = '';
            document.getElementById('status').value = 'Active';
        }
    </script>
</body>
</html>
