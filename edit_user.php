<?php
session_start();
include 'config.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

// Get user to edit
$user_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $role = $_POST['role'];
  $new_password = $_POST['new_password'];

  if (!empty($new_password)) {
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET role = ?, password_hash = ? WHERE id = ?");
    $stmt->execute([$role, $password_hash, $user_id]);
  } else {
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $user_id]);
  }
  
  header("Location: admin_users.php");
  exit;
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
    <form method="POST">
      <div class="form-group">
        <label for="role">Role:</label>
        <select name="role" id="role">
          <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
          <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
          <option value="guest" <?= $user['role'] === 'guest' ? 'selected' : '' ?>>Guest</option>
        </select>
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
