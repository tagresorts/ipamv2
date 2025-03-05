<?php
session_start();
include 'config.php';

// Only allow administrators
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Determine action: default 'list'
$action = $_GET['action'] ?? 'list';

$error = '';
$success = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        die("CSRF token validation failed");
    }
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_user') {
            // Add new user
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $first_name = trim($_POST['first_name']);
            $middle_name = trim($_POST['middle_name']);
            $last_name = trim($_POST['last_name']);
            $password_input = $_POST['password'];
            $role = trim($_POST['role']);
            $active = isset($_POST['active']) ? 1 : 0;
            
            // Basic duplicate check
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Username or email already exists.";
            } elseif (strlen($password_input) < 6) {
                $error = "Password must be at least 6 characters long.";
            } elseif (empty($first_name) || empty($last_name)) {
                $error = "First name and last name are required.";
            } else {
                $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, first_name, middle_name, last_name, password_hash, role, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $first_name, $middle_name, $last_name, $password_hash, $role, $active])) {
                    $user_id = $pdo->lastInsertId();
                    // Process company assignments (always a multi‑select)
                    $companiesAssigned = [];
                    if (isset($_POST['companies']) && is_array($_POST['companies'])) {
                        $companiesAssigned = $_POST['companies'];
                    }
                    foreach ($companiesAssigned as $comp_id) {
                        $stmtAssign = $pdo->prepare("INSERT INTO user_companies (user_id, company_id, role) VALUES (?, ?, ?)");
                        // Default assignment role is 'viewer'
                        $stmtAssign->execute([$user_id, $comp_id, 'viewer']);
                    }
                    header("Location: admin_users.php?action=list&success=" . urlencode("User added successfully."));
                    exit;
                } else {
                    $error = "Failed to add user.";
                }
            }
        } elseif ($_POST['action'] === 'edit_user') {
            // Edit existing user
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            if (!$user_id) {
                $error = "Invalid user ID.";
            } else {
                $role = trim($_POST['role']);
                $active = isset($_POST['active']) ? 1 : 0;
                $email = trim($_POST['email']);
                $first_name = trim($_POST['first_name']);
                $middle_name = trim($_POST['middle_name']);
                $last_name = trim($_POST['last_name']);
                if (empty($first_name) || empty($last_name)) {
                    $error = "First name and last name are required.";
                } else {
                    if (!empty($_POST['new_password'])) {
                        $new_password = $_POST['new_password'];
                        if (strlen($new_password) < 6) {
                            $error = "Password must be at least 6 characters long.";
                        } else {
                            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET role = ?, email = ?, first_name = ?, middle_name = ?, last_name = ?, active = ?, password_hash = ? WHERE id = ?");
                            $stmt->execute([$role, $email, $first_name, $middle_name, $last_name, $active, $password_hash, $user_id]);
                        }
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET role = ?, email = ?, first_name = ?, middle_name = ?, last_name = ?, active = ? WHERE id = ?");
                        $stmt->execute([$role, $email, $first_name, $middle_name, $last_name, $active, $user_id]);
                    }
                    // Update company assignments:
                    if (isset($_POST['companies'])) {
                        // Delete existing assignments for this user
                        $stmtDel = $pdo->prepare("DELETE FROM user_companies WHERE user_id = ?");
                        $stmtDel->execute([$user_id]);
                        $companiesAssigned = [];
                        if (isset($_POST['companies']) && is_array($_POST['companies'])) {
                            $companiesAssigned = $_POST['companies'];
                        }
                        foreach ($companiesAssigned as $comp_id) {
                            $stmtAssign = $pdo->prepare("INSERT INTO user_companies (user_id, company_id, role) VALUES (?, ?, ?)");
                            $stmtAssign->execute([$user_id, $comp_id, 'viewer']);
                        }
                    }
                    header("Location: admin_users.php?action=list&success=" . urlencode("User updated successfully."));
                    exit;
                }
            }
        } elseif ($_POST['action'] === 'delete_user') {
            // Delete user
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            if (!$user_id) {
                $error = "Invalid user ID.";
            } elseif ($user_id == $_SESSION['user_id']) {
                $error = "You cannot delete your own account.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    header("Location: admin_users.php?action=list&success=" . urlencode("User deleted successfully."));
                    exit;
                } else {
                    $error = "Failed to delete user.";
                }
            }
        }
    }
}

// If editing, fetch the user record for editing along with assigned companies
if ($action === 'edit_user' && isset($_GET['id'])) {
    $user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$user_id) {
        header("Location: admin_users.php?error=" . urlencode("Invalid ID"));
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $edit_user = $stmt->fetch();
    if (!$edit_user) {
        header("Location: admin_users.php?error=" . urlencode("User not found"));
        exit;
    }
    // Fetch assigned companies for this user
    $stmtAssign = $pdo->prepare("SELECT company_id FROM user_companies WHERE user_id = ?");
    $stmtAssign->execute([$user_id]);
    $assignedCompanies = $stmtAssign->fetchAll(PDO::FETCH_COLUMN);
}

// For listing users (default action)
if ($action === 'list') {
    $users = $pdo->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
}

// Fetch companies for assignment (used in add/edit forms)
$companies = $pdo->query("SELECT * FROM companies ORDER BY company_name ASC")->fetchAll();

// Check for success message in GET parameters
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - IP Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="style.css">
  <style>
    .form-group { margin-bottom: 15px; }
    label { display: block; font-weight: bold; margin-bottom: 5px; }
    input[type="text"], input[type="email"], input[type="password"], select, textarea {
        width: 100%; padding: 8px; box-sizing: border-box;
    }
    /* Optional: additional styling for nav buttons 
    .nav-btn.btn {
      background-color: #0078d7;
      color: #fff;
      padding: 8px 12px;
      border-radius: 4px;
      text-decoration: none;
      margin-left: 10px; */
    }
  </style>
</head>
<body>
  <div class="nav">
    <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
    <a href="admin_users.php?action=add" class="nav-btn btn">Add New User</a>
    <a href="admin_users.php?action=list" class="nav-btn btn">Users List</a>
  </div>
  <div class="container">
    <?php if (!empty($error)): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
      <h2>Existing Users</h2>
      <table>
        <tr>
          <th>Username</th>
          <th>Email</th>
          <th>First Name</th>
          <th>Middle Name</th>
          <th>Last Name</th>
          <th>Role</th>
          <th>Active</th>
          <th>Last Login</th>
          <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['first_name']) ?></td>
          <td><?= htmlspecialchars($user['middle_name']) ?></td>
          <td><?= htmlspecialchars($user['last_name']) ?></td>
          <td><?= htmlspecialchars($user['role']) ?></td>
          <td><?= $user['active'] ? 'Yes' : 'No' ?></td>
          <td><?= $user['last_login'] ? htmlspecialchars($user['last_login']) : 'Never' ?></td>
          <td>
            <a href="admin_users.php?action=edit_user&id=<?= $user['id'] ?>" class="btn">Edit</a>
            <?php if ($user['id'] != $_SESSION['user_id']): ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
              <button type="submit" class="btn" onclick="return confirm('Delete this user?')">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>

    <?php elseif ($action === 'add'): ?>
      <h2>Add New User</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="action" value="add_user">
        <div class="form-group">
          <label for="username">Username (not editable after creation):</label>
          <input type="text" name="username" id="username" placeholder="Username" required>
        </div>
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" name="email" id="email" placeholder="Email" required>
        </div>
        <div class="form-group">
          <label for="first_name">First Name:</label>
          <input type="text" name="first_name" id="first_name" placeholder="First Name" required>
        </div>
        <div class="form-group">
          <label for="middle_name">Middle Name:</label>
          <input type="text" name="middle_name" id="middle_name" placeholder="Middle Name">
        </div>
        <div class="form-group">
          <label for="last_name">Last Name:</label>
          <input type="text" name="last_name" id="last_name" placeholder="Last Name" required>
        </div>
        <div class="form-group">
          <label for="password">Password (min 6 characters):</label>
          <input type="password" name="password" id="password" placeholder="Password" required>
        </div>
        <div class="form-group">
          <label for="role">Role:</label>
          <select name="role" id="role">
            <option value="admin">Admin</option>
            <option value="user">User</option>
            <option value="guest">Guest</option>
          </select>
        </div>
        <div class="form-group">
          <label for="active">Active:</label>
          <input type="checkbox" name="active" id="active" checked>
        </div>
        <!-- Always use multi‑select for companies -->
        <div class="form-group">
          <label for="companies">Assign Companies:</label>
          <select name="companies[]" id="companies" multiple>
            <?php foreach ($companies as $company): ?>
              <option value="<?= $company['company_id'] ?>"><?= htmlspecialchars($company['company_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn">Add User</button>
        <a href="admin_users.php?action=list" class="btn">Cancel</a>
      </form>

    <?php elseif ($action === 'edit_user' && isset($edit_user)): ?>
      <h2>Edit User: <?= htmlspecialchars($edit_user['username']) ?></h2>
      <?php
        // Fetch assigned companies for this user if not already done
        if (!isset($assignedCompanies)) {
            $stmtAssign = $pdo->prepare("SELECT company_id FROM user_companies WHERE user_id = ?");
            $stmtAssign->execute([$edit_user['id']]);
            $assignedCompanies = $stmtAssign->fetchAll(PDO::FETCH_COLUMN);
        }
      ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($edit_user['id']) ?>">
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" name="email" id="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
        </div>
        <div class="form-group">
          <label for="first_name">First Name:</label>
          <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($edit_user['first_name']) ?>" required>
        </div>
        <div class="form-group">
          <label for="middle_name">Middle Name:</label>
          <input type="text" name="middle_name" id="middle_name" value="<?= htmlspecialchars($edit_user['middle_name']) ?>">
        </div>
        <div class="form-group">
          <label for="last_name">Last Name:</label>
          <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($edit_user['last_name']) ?>" required>
        </div>
        <div class="form-group">
          <label for="role">Role:</label>
          <select name="role" id="role">
            <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="user" <?= $edit_user['role'] === 'user' ? 'selected' : '' ?>>User</option>
            <option value="guest" <?= $edit_user['role'] === 'guest' ? 'selected' : '' ?>>Guest</option>
          </select>
        </div>
        <div class="form-group">
          <label for="active">Active:</label>
          <input type="checkbox" name="active" id="active" <?= $edit_user['active'] ? 'checked' : '' ?>>
        </div>
        <div class="form-group">
          <label for="new_password">New Password (leave blank to keep current):</label>
          <input type="password" name="new_password" id="new_password">
        </div>
        <!-- Always use multi‑select for companies -->
        <div class="form-group">
          <label for="companies_edit">Assign Companies:</label>
          <select name="companies[]" id="companies_edit" multiple>
            <?php foreach ($companies as $company): ?>
              <option value="<?= $company['company_id'] ?>" <?= (in_array($company['company_id'], $assignedCompanies)) ? 'selected' : '' ?>>
                <?= htmlspecialchars($company['company_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn">Save Changes</button>
        <a href="admin_users.php?action=list" class="btn">Cancel</a>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
