<?php
session_start();
include 'config.php';

// Only admin users should have access to the scheduler manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

/**
 * Returns an array of current crontab lines.
 */
function getCrontabLines() {
    exec('crontab -l 2>&1', $lines, $ret);
    if ($ret !== 0) {
        $lines = [];
    }
    return $lines;
}

/**
 * Saves an array of lines as the new crontab.
 */
function saveCrontabLines($lines) {
    $newCrontab = implode(PHP_EOL, $lines) . PHP_EOL;
    $tmpFile = tempnam(sys_get_temp_dir(), 'cron');
    file_put_contents($tmpFile, $newCrontab);
    exec("crontab $tmpFile 2>&1", $output, $ret);
    unlink($tmpFile);
    return $ret === 0;
}

// Full path to the backup script
$backupScript = __DIR__ . '/backup.sh';
// Marker to identify IPAM scheduler entries
$marker = "# IPAM_SCHEDULER";

// Handle form submissions for adding, editing, or deleting schedules
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $crontab = getCrontabLines();
    $managed = [];
    $others = [];
    foreach ($crontab as $line) {
        if (strpos($line, $marker) !== false) {
            $managed[] = $line;
        } else {
            $others[] = $line;
        }
    }
    
    if ($action === 'add') {
        // New schedule fields: minute, hour, dom, month, dow, description
        $minute = trim($_POST['minute']);
        $hour = trim($_POST['hour']);
        $dom = trim($_POST['dom']);
        $month = trim($_POST['month']);
        $dow = trim($_POST['dow']);
        $description = trim($_POST['description']);
        $newLine = "$minute $hour $dom $month $dow $backupScript $marker";
        if (!empty($description)) {
            $newLine .= " # $description";
        }
        $managed[] = $newLine;
        $newCrontab = array_merge($others, $managed);
        $msg = saveCrontabLines($newCrontab) ? "Schedule added successfully." : "Failed to update crontab.";
    } elseif ($action === 'delete') {
        $index = intval($_POST['index']);
        if (isset($managed[$index])) {
            unset($managed[$index]);
            $managed = array_values($managed);
            $newCrontab = array_merge($others, $managed);
            $msg = saveCrontabLines($newCrontab) ? "Schedule deleted successfully." : "Failed to update crontab.";
        }
    } elseif ($action === 'edit') {
        $index = intval($_POST['index']);
        $minute = trim($_POST['minute']);
        $hour = trim($_POST['hour']);
        $dom = trim($_POST['dom']);
        $month = trim($_POST['month']);
        $dow = trim($_POST['dow']);
        $description = trim($_POST['description']);
        $newLine = "$minute $hour $dom $month $dow $backupScript $marker";
        if (!empty($description)) {
            $newLine .= " # $description";
        }
        if (isset($managed[$index])) {
            $managed[$index] = $newLine;
            $newCrontab = array_merge($others, $managed);
            $msg = saveCrontabLines($newCrontab) ? "Schedule updated successfully." : "Failed to update crontab.";
        }
    }
    header("Location: scheduler_manager.php?msg=" . urlencode($msg));
    exit;
}

// On GET, read current managed schedules
$crontab = getCrontabLines();
$managed = [];
foreach ($crontab as $line) {
    if (strpos($line, $marker) !== false) {
        $managed[] = $line;
    }
}
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Scheduler Manager - IPAM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <style>
    /* Additional styles for Scheduler Manager */
    .scheduler-container {
        max-width: 800px;
        margin: 20px auto;
        padding: 15px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .scheduler-container h2 {
        margin-top: 0;
    }
    .back-to-home {
        margin-bottom: 10px;
    }
    .back-to-home a {
        text-decoration: none;
        background-color: #0056b3;
        color: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    .back-to-home a:hover {
        background-color: #003f8f;
    }
    table.scheduler-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    table.scheduler-table th,
    table.scheduler-table td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }
    form.scheduler-form input[type="text"] {
        width: 60px;
    }
    .msg {
        color: green;
    }
    /* Consistent button styling */
    .nav-btn {
        background-color: #0056b3;
        color: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s, box-shadow 0.2s;
        box-shadow: inset 0 2px 2px rgba(255, 255, 255, 0.2);
    }
    .nav-btn:hover {
        background-color: #003f8f;
        box-shadow: inset 0 2px 2px rgba(0, 0, 0, 0.2);
    }
  </style>
</head>
<body>
  <div class="scheduler-container">
    <div class="back-to-home">
      <a href="dashboard.php" class="nav-btn">← Back to Home</a>
    </div>
    <h2>Backup Scheduler Manager</h2>
    <?php if ($msg): ?>
      <p class="msg"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <h3>Current Schedules</h3>
    <?php if (count($managed) > 0): ?>
      <table class="scheduler-table">
        <tr>
          <th>#</th>
          <th>Cron Expression</th>
          <th>Description</th>
          <th>Actions</th>
        </tr>
        <?php foreach ($managed as $index => $line): 
            $parts = preg_split('/\s+/', $line, 7);
            $cronExpr = implode(' ', array_slice($parts, 0, 5));
            $desc = "";
            if (strpos($line, "#", strpos($line, $marker)) !== false) {
                $desc = trim(substr($line, strpos($line, "#", strpos($line, $marker)) + 1));
            }
        ?>
        <tr>
          <td><?= $index ?></td>
          <td><?= htmlspecialchars($cronExpr) ?></td>
          <td><?= htmlspecialchars($desc) ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="index" value="<?= $index ?>">
              <button type="submit" class="nav-btn">Delete</button>
            </form>
            <button onclick="editSchedule(<?= $index ?>, '<?= htmlspecialchars($cronExpr, ENT_QUOTES) ?>', '<?= htmlspecialchars($desc, ENT_QUOTES) ?>')" class="nav-btn">Edit</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>No schedules found.</p>
    <?php endif; ?>

    <h3>Add New Schedule</h3>
    <form method="POST" class="scheduler-form">
      <input type="hidden" name="action" value="add">
      <label>Minute: <input type="text" name="minute" value="0"></label>
      <label>Hour: <input type="text" name="hour" value="2"></label>
      <label>Day of Month: <input type="text" name="dom" value="*"></label>
      <label>Month: <input type="text" name="month" value="*"></label>
      <label>Day of Week: <input type="text" name="dow" value="*"></label>
      <label>Description: <input type="text" name="description" value="Daily Backup"></label>
      <button type="submit" class="nav-btn">Add Schedule</button>
    </form>

    <!-- Hidden form for editing schedule -->
    <div id="editFormContainer" style="display:none; margin-top:20px;">
      <h3>Edit Schedule</h3>
      <form method="POST" class="scheduler-form" id="editForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="index" id="editIndex">
        <label>Minute: <input type="text" name="minute" id="editMinute"></label>
        <label>Hour: <input type="text" name="hour" id="editHour"></label>
        <label>Day of Month: <input type="text" name="dom" id="editDom"></label>
        <label>Month: <input type="text" name="month" id="editMonth"></label>
        <label>Day of Week: <input type="text" name="dow" id="editDow"></label>
        <label>Description: <input type="text" name="description" id="editDescription"></label>
        <button type="submit" class="nav-btn">Save Changes</button>
        <button type="button" class="nav-btn" onclick="hideEditForm()">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    function editSchedule(index, cronExpr, description) {
      var fields = cronExpr.split(" ");
      if (fields.length < 5) return;
      document.getElementById('editIndex').value = index;
      document.getElementById('editMinute').value = fields[0];
      document.getElementById('editHour').value = fields[1];
      document.getElementById('editDom').value = fields[2];
      document.getElementById('editMonth').value = fields[3];
      document.getElementById('editDow').value = fields[4];
      document.getElementById('editDescription').value = description;
      document.getElementById('editFormContainer').style.display = 'block';
    }
    function hideEditForm() {
      document.getElementById('editFormContainer').style.display = 'none';
    }
  </script>
</body>
</html>
