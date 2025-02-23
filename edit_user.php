<?php
session_start();

// Enable error reporting for debugging (remove this in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Validate and retrieve user ID from GET parameters
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: admin_users.php?error=invalid_id");
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Retrieve the user record
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: admin_users.php?error=user_not_found");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        die("CSRF token validation failed");
    }
    
    // Retrieve and sanitize input fields
    $email       = trim($_POST['email']);
    $first_name  = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name   = trim($_POST['last_name']);
    $role        = trim($_POST['role']);
    $active      = isset($_POST['active']) ? 1 : 0;
    
    // Validate mandatory fields
    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required.";
    } else {
        // Update with new password if provided, otherwise update other fields only
        if (!empty($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            if (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET email = ?, first_name = ?, middle_name = ?, last_name = ?, role = ?, active = ?, password_hash = ? WHERE id = ?");
                if ($stmt->execute([$email, $first_name, $middle_name, $last_name, $role, $active, $password_hash, $user_id])) {
                    $success = "User updated successfully.";
                } else {
                    $error = "Failed to update user.";
                }
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, first_name = ?, middle_name = ?, last_name = ?, role = ?, active = ? WHERE id = ?");
            if ($stmt->execute([$email, $first_name, $middle_name, $last_name, $role, $active, $user_id])) {
                $success = "User updated successfully.";
            } else {
                $error = "Failed to update user.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User - IP Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="nav">
    <h1>Edit User: <?= htmlspecialchars($user['username']) ?></h1>
    <a href="admin_users.php">Back to User Management</a>
  </div>
  <div class="container">
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST">
      <!-- CSRF Token -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <!-- Hidden user ID -->
      <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
      
      <div class="form-group">
        <label for="username">Username (not editable):</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" disabled>
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
      </div>
      <div class="form-group">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
      </div>
      <div class="form-group">
        <label for="middle_name">Middle Name:</label>
        <input type="text" id="middle_name" name="middle_name" value="<?= htmlspecialchars($user['middle_name']) ?>">
      </div>
      <div class="form-group">
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
      </div>
      <div class="form-group">
        <label for="role">Role:</label>
        <select name="role" id="role">
          <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
          <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
          <option value="guest" <?= $user['role'] === 'guest' ? 'selected' : '' ?>>Guest</option>
        </select>
      </div>
      <div class="form-group">
        <label for="active">Active:</label>
        <input type="checkbox" id="active" name="active" <?= $user['active'] ? 'checked' : '' ?>>
      </div>
      <div class="form-group">
        <label for="new_password">New Password (leave blank to keep current):</label>
        <input type="password" id="new_password" name="new_password">
      </div>
      <button type="submit" class="btn">Save Changes</button>
      <a href="admin_users.php" class="btn">Cancel</a>
    </form>
  </div>
</body>
</html>
