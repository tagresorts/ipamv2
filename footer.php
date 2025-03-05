<?php
// footer.php
?>
  <!-- Footer Scripts -->
  <script src="column_modal.js"></script>
  <script>
    // Pass PHP chart data to JavaScript as global variables
    var typeChartData = <?php echo json_encode($typeChartData); ?>;
    var companyChartData = <?php echo json_encode($companyChartData); ?>;
    var locationChartData = <?php echo json_encode($locationChartData); ?>;
    var subnetChartData = <?php echo json_encode($subnetChartData); ?>;
  </script>
  <script src="graphs.js"></script>
</body>
</html>
