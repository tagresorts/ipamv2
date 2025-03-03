// Only initialize charts if the current view is not set to "ip"
if (localStorage.getItem("currentView") !== "ip") {
    // Ensure the Chart.js library is loaded before initializing charts.
    if (typeof Chart !== "undefined") {
      // Initialize Type Distribution Chart
      const ctxType = document.getElementById('typeChart').getContext('2d');
      new Chart(ctxType, {
          type: 'pie',
          data: typeChartData,
          options: {
              responsive: true,
              plugins: {
                  legend: { position: 'bottom' },
                  title: { display: true, text: 'IP Distribution by Type' }
              }
          }
      });

      // Initialize Company Distribution Chart
      const ctxCompany = document.getElementById('companyChart').getContext('2d');
      new Chart(ctxCompany, {
          type: 'pie',
          data: companyChartData,
          options: {
              responsive: true,
              plugins: {
                  legend: { position: 'bottom' },
                  title: { display: true, text: 'IP Distribution by Company' }
              }
          }
      });

      // Initialize Location Distribution Chart
      const ctxLocation = document.getElementById('locationChart').getContext('2d');
      new Chart(ctxLocation, {
          type: 'pie',
          data: locationChartData,
          options: {
              responsive: true,
              plugins: {
                  legend: { position: 'bottom' },
                  title: { display: true, text: 'IP Distribution by Location' }
              }
          }
      });

      // Initialize Subnet Distribution Chart
      const ctxSubnet = document.getElementById('subnetChart').getContext('2d');
      new Chart(ctxSubnet, {
          type: 'pie',
          data: subnetChartData,
          options: {
              responsive: true,
              plugins: {
                  legend: { position: 'bottom' },
                  title: { display: true, text: 'IP Distribution by Subnet' }
              }
          }
      });
    }
}
