// Only initialize charts if the current view is not set to "ip"
if (localStorage.getItem("currentView") !== "ip") {
    // Ensure the Chart.js library is loaded before initializing charts.
    if (typeof Chart !== "undefined") {
        // Register the ChartDataLabels plugin (for Chart.js v3+)
        if (typeof ChartDataLabels !== "undefined") {
            Chart.register(ChartDataLabels);
        }

        // Common datalabels options for pie charts
        const pieDatalabels = {
            color: '#fff',
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
            font: { size: 10 },
            padding: 4
        };

        // Common datalabels options for bar charts
        const barDatalabels = {
            color: '#000',
            anchor: 'end',
            align: 'top',
            formatter: (value) => value,
            font: { size: 11 }
        };

        // Initialize Type Distribution Chart (Bar)
        const ctxType = document.getElementById('typeChart').getContext('2d');
        new Chart(ctxType, {
            type: 'bar',
            data: {
                labels: typeChartData.labels,
                datasets: [{
                    label: 'Number of IPs',
                    data: typeChartData.datasets[0].data,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(255, 205, 86, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { 
                        display: true, 
                        text: 'IP Distribution by Type',
                        padding: 20
                    },
                    datalabels: barDatalabels
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of IPs'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Type'
                        }
                    }
                }
            }
        });

        // Initialize Company Distribution Chart (Pie - unchanged)
        const ctxCompany = document.getElementById('companyChart').getContext('2d');
        new Chart(ctxCompany, {
            type: 'pie',
            data: companyChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'IP Distribution by Company' },
                    datalabels: pieDatalabels
                }
            }
        });

        // Initialize Location Distribution Chart (Bar)
        const ctxLocation = document.getElementById('locationChart').getContext('2d');
        new Chart(ctxLocation, {
            type: 'bar',
            data: {
                labels: locationChartData.labels,
                datasets: [{
                    label: 'Number of IPs',
                    data: locationChartData.datasets[0].data,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(255, 205, 86, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { 
                        display: true, 
                        text: 'IP Distribution by Location',
                        padding: 20
                    },
                    datalabels: barDatalabels
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of IPs'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Location'
                        }
                    }
                }
            }
        });

        // Initialize Subnet Distribution Chart (Pie - unchanged)
        const ctxSubnet = document.getElementById('subnetChart').getContext('2d');
        new Chart(ctxSubnet, {
            type: 'pie',
            data: subnetChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'IP Distribution by Subnet' },
                    datalabels: pieDatalabels
                }
            }
        });
    }
}
