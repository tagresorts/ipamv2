<?php
session_start();
include 'config.php';
// Redirect logged-in users away from login page
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            // Set first_name for multi-tenancy display
            $_SESSION['first_name'] = $user['first_name'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "A system error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IP Management System - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-blur {
            background-image: url('ipam.jpg');
            background-size: cover;
            background-position: center;
            filter: blur(4px);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
    </style>
</head>
<body class="relative bg-gray-100 flex items-center justify-center min-h-screen overflow-hidden">
    <div class="bg-blur opacity-50"></div>

    <div class="relative w-full max-w-md p-8 space-y-8 bg-white/80 backdrop-blur-lg shadow-xl rounded-xl">
        <div class="text-center">
            <img src="ryan_logo.png" alt="Ryan's IPAM Logo" class="mx-auto h-20 w-auto mb-6">
            <h1 class="text-3xl font-bold text-gray-800">IP Management System</h1>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autocomplete="username"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Log In
                </button>
            </div>
        </form>

        <div class="text-center text-sm text-gray-500 mt-4">
            &copy; <?= date('Y'); ?> Ryan's IPAM v1.0.5
        </div>
    </div>
</body>
</html>
