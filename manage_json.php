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
    // Add new entry: add both values side by side even if one is empty
    if (isset($_POST['add'])) {
        $newType = isset($_POST['new_type']) ? htmlspecialchars(trim($_POST['new_type'])) : "";
        $newLocation = isset($_POST['new_location']) ? htmlspecialchars(trim($_POST['new_location'])) : "";
        $data['types'][] = $newType;
        $data['locations'][] = $newLocation;
    }

    // Separate update for individual fields
    if (isset($_POST['update'])) {
        $index = $_POST['index'];
        $field = $_POST['field']; // 'type' or 'location'
        $newValue = htmlspecialchars(trim($_POST['new_value']));
        if ($field === 'type' && isset($data['types'][$index])) {
            $data['types'][$index] = $newValue;
        } elseif ($field === 'location' && isset($data['locations'][$index])) {
            $data['locations'][$index] = $newValue;
        }
    }

    // Separate delete for individual fields: remove the entry from its list
    if (isset($_POST['delete'])) {
        $index = $_POST['index'];
        $field = $_POST['field'];
        if ($field === 'type' && isset($data['types'][$index])) {
            unset($data['types'][$index]);
            $data['types'] = array_values($data['types']);
        } elseif ($field === 'location' && isset($data['locations'][$index])) {
            unset($data['locations'][$index]);
            $data['locations'] = array_values($data['locations']);
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
        /* Custom styles for this page */
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: var(--card-bg); }
        input[type="text"] { padding: 5px; width: 100%; box-sizing: border-box; }
        .button { padding: 5px 10px; margin: 0 5px; cursor: pointer; }
        .success { color: green; margin-bottom: 15px; }
        /* New entry form flex layout */
        .new-entry-form {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }
        .new-entry-form .field {
            flex: 1;
            min-width: 200px;
        }
        .new-entry-form .button-container {
            margin-left: auto;
        }
        /* Flex container for the two independent tables */
        .tables-container { display: flex; flex-wrap: wrap; gap: 20px; }
        .table-wrapper { flex: 1; min-width: 300px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    </style>
</head>
<body>
    <!-- Navigation using the .nav class from style.css -->
    <div class="nav">
        <div class="navbar-container">
            <div class="nav-links">
                <a href="dashboard.php" class="nav-btn">← Back to Dashboard</a>
                <a href="manage_ip.php?action=add" class="nav-btn">Manage IP</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Add New Entry Form -->
        <div class="section">
            <h2>Add New Entry</h2>
            <form method="POST" class="new-entry-form">
                <div class="field">
                    <label>New Hardware Type:</label>
                    <input type="text" name="new_type">
                </div>
                <div class="field">
                    <label>New Location:</label>
                    <input type="text" name="new_location">
                </div>
                <div class="button-container">
                    <button type="submit" name="add" class="button">Add Entry</button>
                </div>
            </form>
        </div>

        <!-- Independent Tables for Hardware Types and Locations -->
        <div class="section tables-container">
            <!-- Hardware Types Table -->
            <div class="table-wrapper">
                <h2>Hardware Types</h2>
                <table>
                    <tr>
                        <th>Hardware Type</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($data['types'] as $index => $type): ?>
                    <tr>
                        <td>
                            <form method="POST" style="display: inline-block; width: 100%;">
                                <input type="text" name="new_value" value="<?= htmlspecialchars($type) ?>">
                                <input type="hidden" name="index" value="<?= $index ?>">
                                <input type="hidden" name="field" value="type">
                        </td>
                        <td>
                                <button type="submit" name="update" class="button">Update</button>
                                <button type="submit" name="delete" class="button" onclick="return confirm('Are you sure you want to delete this hardware type?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Locations Table -->
            <div class="table-wrapper">
                <h2>Locations</h2>
                <table>
                    <tr>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($data['locations'] as $index => $location): ?>
                    <tr>
                        <td>
                            <form method="POST" style="display: inline-block; width: 100%;">
                                <input type="text" name="new_value" value="<?= htmlspecialchars($location) ?>">
                                <input type="hidden" name="index" value="<?= $index ?>">
                                <input type="hidden" name="field" value="location">
                        </td>
                        <td>
                                <button type="submit" name="update" class="button">Update</button>
                                <button type="submit" name="delete" class="button" onclick="return confirm('Are you sure you want to delete this location?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
