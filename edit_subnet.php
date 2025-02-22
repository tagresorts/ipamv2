<?php
session_start();
include 'config.php';

// Restrict access to admin users only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: subnets.php");
    exit;
}

// Check for the subnet id in GET parameters
if (!isset($_GET['id'])) {
    header("Location: subnets.php");
    exit;
}

$id = $_GET['id'];

// Retrieve the subnet record
$stmt = $pdo->prepare("SELECT * FROM subnets WHERE id = ?");
$stmt->execute([$id]);
$subnet = $stmt->fetch();

if (!$subnet) {
    echo "Subnet not found.";
    exit;
}

// Handle form submission to update the record
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_subnet = $_POST['subnet'];
    $description = $_POST['description'];
    $vlan_id = $_POST['vlan_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE subnets SET subnet = ?, description = ?, vlan_id = ? WHERE id = ?");
        $stmt->execute([$new_subnet, $description, $vlan_id, $id]);
        header("Location: subnets.php");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Subnet - IP Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Fluid container for editing */
        .container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
        }
        .form-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="subnets.php" class="nav-btn">← Back to Subnet Management</a>
    </div>
    <div class="container">
        <h1>Edit Subnet</h1>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="form-container">
            <div class="form-group">
                <label for="subnet">Subnet (CIDR):</label>
                <input type="text" id="subnet" name="subnet" value="<?= htmlspecialchars($subnet['subnet']) ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <input type="text" id="description" name="description" value="<?= htmlspecialchars($subnet['description']) ?>">
            </div>
            <div class="form-group">
                <label for="vlan_id">VLAN ID:</label>
                <input type="text" id="vlan_id" name="vlan_id" value="<?= htmlspecialchars($subnet['vlan_id']) ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Save Changes</button>
                <a href="subnets.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
