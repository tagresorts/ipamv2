<?php
session_start();
include 'config.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

// Handle user actions (add/edit/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_user'])) {
    // Add new user
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $role]);
  } elseif (isset($_POST['edit_user'])) {
    // Edit existing user
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    if (!empty($_POST['new_password'])) {
      $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("UPDATE users SET role = ?, password_hash = ? WHERE id = ?");
      $stmt->execute([$role, $password, $user_id]);
    } else {
      $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
      $stmt->execute([$role, $user_id]);
    }
  }
}

// Handle deletion if requested via GET
if (isset($_GET['delete'])) {
  $user_id = $_GET['delete'];
  $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users")->fetchAll();
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
    <h2>Add New User</h2>
    <form method="POST">
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" placeholder="Username" required>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
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
      <button type="submit" name="add_user" class="btn">Add User</button>
    </form>

    <h2>Existing Users</h2>
    <table>
      <tr>
        <th>Username</th>
        <th>Role</th>
        <th>Actions</th>
      </tr>
      <?php foreach ($users as $user): ?>
      <tr>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= htmlspecialchars($user['role']) ?></td>
        <td>
          <a href="edit_user.php?id=<?= $user['id'] ?>">Edit</a>
          <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</body>
</html>
