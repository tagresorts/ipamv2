// Only initialize charts if the current view is not set to "ip"
if (localStorage.getItem("currentView") !== "ip") {
    // Ensure the Chart.js library is loaded before initializing charts.
    if (typeof Chart !== "undefined") {
        // Register the ChartDataLabels plugin (for Chart.js v3+)
        if (typeof ChartDataLabels !== "undefined") {
            Chart.register(ChartDataLabels);
        }

        // Common datalabels options to reduce overcrowding
        const commonDatalabels = {
            color: '#fff',
            // Only display labels for slices representing more than 5% of the total
            display: function(context) {
                const dataset = context.chart.data.datasets[0];
                const total = dataset.data.reduce((a, b) => a + b, 0);
                const value = dataset.data[context.dataIndex];
                const percentage = (value / total) * 100;
                return percentage > 5; // Only show label if greater than 5%
            },
            formatter: (value, context) => {
                const dataset = context.chart.data.datasets[0];
                const total = dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value * 100) / total).toFixed(1) + "%";
                return context.chart.data.labels[context.dataIndex] + "\n" + percentage;
            },
            font: {
                size: 10
            },
            padding: 4
        };

        // Initialize Type Distribution Chart
        const ctxType = document.getElementById('typeChart').getContext('2d');
        new Chart(ctxType, {
            type: 'pie',
            data: typeChartData,
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'IP Distribution by Type' },
                    datalabels: commonDatalabels
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
                    title: { display: true, text: 'IP Distribution by Company' },
                    datalabels: commonDatalabels
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
                    title: { display: true, text: 'IP Distribution by Location' },
                    datalabels: commonDatalabels
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
                    title: { display: true, text: 'IP Distribution by Subnet' },
                    datalabels: commonDatalabels
                }
            }
        });
    }
}
