<?php
// column_toggle.php - Column Toggle Settings
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Column Toggle Settings</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { font-size: 20px; margin-bottom: 10px; }
    .toggle-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .toggle-item {
      flex: 1 1 45%;
      min-width: 150px;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      background: #f9f9f9;
      text-align: center;
    }
    label { cursor: pointer; }
    button {
      margin-top: 20px;
      padding: 8px 12px;
      cursor: pointer;
    }
    .button-group {
      display: flex;
      justify-content: space-around;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <h1>Toggle Columns</h1>
  <p>Select columns to display. Unchecked columns will be hidden.</p>
  <div class="toggle-container">
    <div class="toggle-item">
      <label><input type="checkbox" data-col="0"> IP Address</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="1"> Subnet</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="2"> Status</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="3"> Assigned To</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="4"> Owner</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="5"> Description</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="6"> Type</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="7"> Location</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="8"> Company</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="9"> Created At</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="10"> Last Updated</label>
    </div>
    <div class="toggle-item">
      <label><input type="checkbox" data-col="11"> Actions</label>
    </div>
  </div>
  <div class="button-group">
    <button type="button" id="saveBtn">Save</button>
    <button type="button" id="restoreBtn">Restore Default View</button>
  </div>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // On load, set checkboxes based on saved hidden columns in local storage
      const savedHidden = localStorage.getItem("ipTableHiddenColumns");
      let hiddenColumns = savedHidden ? JSON.parse(savedHidden) : [];
      document.querySelectorAll("input[data-col]").forEach(function(checkbox) {
          const colIndex = parseInt(checkbox.getAttribute("data-col"));
          checkbox.checked = !hiddenColumns.includes(colIndex);
      });

      document.getElementById("saveBtn").addEventListener("click", function() {
          let newHidden = [];
          document.querySelectorAll("input[data-col]").forEach(function(checkbox) {
              const colIndex = parseInt(checkbox.getAttribute("data-col"));
              if (!checkbox.checked) {
                  newHidden.push(colIndex);
              }
          });
          localStorage.setItem("ipTableHiddenColumns", JSON.stringify(newHidden));
          if (window.opener) {
              window.opener.location.reload();
              window.close();
          } else {
              location.reload();
          }
      });

      document.getElementById("restoreBtn").addEventListener("click", function() {
          localStorage.removeItem("ipTableHiddenColumns");
          if (window.opener) {
              window.opener.location.reload();
              window.close();
          } else {
              location.reload();
          }
      });
    });
  </script>
</body>
</html>
