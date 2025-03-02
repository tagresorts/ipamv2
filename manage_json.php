<?php
session_start();
include 'config.php';

// Check if user is admin and logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$jsonFile = 'data/dropdown_options.json';
$data = [];

// Read existing data
if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
}

// Initialize default structure if file doesn't exist
if (!isset($data['types']) || !isset($data['locations'])) {
    $data = [
        'types' => [],
        'locations' => []
    ];
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new entry
    if (isset($_POST['add'])) {
        if (!empty($_POST['new_type'])) {
            $data['types'][] = htmlspecialchars(trim($_POST['new_type']));
        }
        if (!empty($_POST['new_location'])) {
            $data['locations'][] = htmlspecialchars(trim($_POST['new_location']));
        }
    }

    // Edit entry
    if (isset($_POST['edit'])) {
        $section = $_POST['section'];
        $oldValue = $_POST['old_value'];
        $newValue = htmlspecialchars(trim($_POST['new_value']));

        if (!empty($newValue) && in_array($section, ['types', 'locations'])) {
            $index = array_search($oldValue, $data[$section]);
            if ($index !== false) {
                $data[$section][$index] = $newValue;
            }
        }
    }

    // Delete entry
    if (isset($_POST['delete'])) {
        $section = $_POST['section'];
        // Use old_value since that is what the form sends
        $value = $_POST['old_value'];

        if (in_array($section, ['types', 'locations'])) {
            $data[$section] = array_filter($data[$section], function($item) use ($value) {
                return $item !== $value;
            });
        }
    }

    // Save changes
    file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
    $success = "Changes saved successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hardware Data Manager</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        input[type="text"] { padding: 5px; width: 200px; }
        .button { padding: 5px 10px; margin: 0 5px; cursor: pointer; }
        .success { color: green; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <div class="nav-links">
                <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Add New Entries Form -->
        <div class="section">
            <h2>Add New Entry</h2>
            <form method="POST">
                <label>New Type:</label>
                <input type="text" name="new_type">

                <label>New Location:</label>
                <input type="text" name="new_location">

                <button type="submit" name="add" class="button">Add Entries</button>
            </form>
        </div>

        <!-- Edit Types -->
        <div class="section">
            <h2>Hardware Types</h2>
            <table>
                <tr>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($data['types'] as $type): ?>
                <tr>
                    <form method="POST">
                        <td>
                            <input type="text" name="new_value" value="<?= htmlspecialchars($type) ?>">
                        </td>
                        <td>
                            <input type="hidden" name="old_value" value="<?= htmlspecialchars($type) ?>">
                            <input type="hidden" name="section" value="types">
                            <button type="submit" name="edit" class="button">Update</button>
                            <button type="submit" name="delete" class="button"
                                onclick="return confirm('Are you sure you want to delete this type?')">Delete</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Edit Locations -->
        <div class="section">
            <h2>Locations</h2>
            <table>
                <tr>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($data['locations'] as $location): ?>
                <tr>
                    <form method="POST">
                        <td>
                            <input type="text" name="new_value" value="<?= htmlspecialchars($location) ?>">
                        </td>
                        <td>
                            <input type="hidden" name="old_value" value="<?= htmlspecialchars($location) ?>">
                            <input type="hidden" name="section" value="locations">
                            <button type="submit" name="edit" class="button">Update</button>
                            <button type="submit" name="delete" class="button"
                                onclick="return confirm('Are you sure you want to delete this location?')">Delete</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
