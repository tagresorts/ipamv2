<?php
session_start();
include 'config.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$success = '';
$error = '';

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        die("CSRF token validation failed");
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        // Add new user
        $username    = trim($_POST['username']);
        $email       = trim($_POST['email']);
        $first_name  = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name   = trim($_POST['last_name']);
        $password_input = $_POST['password'];
        $role        = trim($_POST['role']);
        $active      = isset($_POST['active']) ? 1 : 0;
        
        // Basic duplicate check for username or email
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
                $success = "User added successfully.";
            } else {
                $error = "Failed to add user.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        // Edit existing user
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$user_id) {
            $error = "Invalid user ID.";
        } else {
            $role       = trim($_POST['role']);
            $active     = isset($_POST['active']) ? 1 : 0;
            $email      = trim($_POST['email']);
            $first_name = trim($_POST['first_name']);
            $middle_name = trim($_POST['middle_name']);
            $last_name  = trim($_POST['last_name']);
            
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
                        $success = "User updated successfully.";
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET role = ?, email = ?, first_name = ?, middle_name = ?, last_name = ?, active = ? WHERE id = ?");
                    $stmt->execute([$role, $email, $first_name, $middle_name, $last_name, $active, $user_id]);
                    $success = "User updated successfully.";
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        // Delete user (via POST)
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$user_id) {
            $error = "Invalid user ID.";
        } elseif ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $success = "User deleted successfully.";
            } else {
                $error = "Failed to delete user.";
            }
        }
    }
}

// Fetch all users (ordered by username)
$users = $pdo->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
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
</head>
<body>
  <div class="nav">
    <h1>User Management</h1>
    <a href="dashboard.php">Back to Dashboard</a>
  </div>
  <div class="container">
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <h2>Add New User</h2>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="add_user">
      <div class="form-group">
        <label for="username">Username:</label>
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
      <button type="submit" class="btn">Add User</button>
    </form>

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
          <a href="edit_user.php?id=<?= $user['id'] ?>">Edit</a>
          <?php if ($user['id'] != $_SESSION['user_id']): ?>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
            <button type="submit" onclick="return confirm('Delete this user?')">Delete</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</body>
</html>
