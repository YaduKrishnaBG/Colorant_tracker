<!DOCTYPE html>
<html>
<head>
    <title>Colorant Usage Chart</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            margin: 40px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        #chartContainer {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        canvas {
            width: 100% !important;
            height: auto !important;
        }
        .control-panel {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        button {
            border: none;
            color: white;
            padding: 12px 24px;
            text-align: center;
            font-size: 16px;
            margin: 5px;
            cursor: pointer;
            border-radius: 5px;
        }
        .btn-refresh {
            background-color: #e74c3c; /* red-ish */
        }
        .btn-refresh:hover {
            background-color: #c0392b; /* darker red */
        }
        .btn-report {
            background-color: #3498db; /* blue */
        }
        .btn-report:hover {
            background-color: #2980b9; /* darker blue */
        }
    </style>
</head>
<body>
    <h1>Colorant Usage Chart</h1>

    <!-- Example control panel -->
    <div class="control-panel">
        <button id="refreshDataBtn" class="btn-refresh">Refresh Data</button>
        
        <!-- For example, if you have a report page -->
        <button class="btn-report" onclick="window.location.href='report_requirements.php'">
            View Report
        </button>
    </div>

    <div id="chartContainer">
        <canvas id="requirementsChart"></canvas>
    </div>

    <!-- Include Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let requirementsChart;

        // Function to fetch data from the modified get_data_requirements.php
        function fetchRequirementsData() {
            fetch('get_data_requirements.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error from server:', data.error);
                        return;
                    }

                    // Update chart data
                    requirementsChart.data.labels = data.colorants;
                    requirementsChart.data.datasets[0].data = data.totals;

                    requirementsChart.update();
                })
                .catch(error => console.error('Error fetching chart data:', error));
        }

        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('requirementsChart').getContext('2d');

            requirementsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [], // will be filled by the fetch
                    datasets: [{
                        label: 'Total Colorant Usage',
                        data: [],
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',   // Red bars
                        borderColor: 'rgba(255, 99, 132, 1)',         // Red border
                        borderWidth: 1,
                        borderRadius: 5,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Colorant',
                                font: { size: 14, weight: 'bold' },
                                color: '#555'
                            },
                            grid: { display: false },
                            ticks: { font: { size: 12 } },
                            categoryPercentage: 0.6,
                            barPercentage: 0.8
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Weight Used',
                                font: { size: 14, weight: 'bold' },
                                color: '#555'
                            },
                            grid: {
                                color: '#e0e0e0',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                font: { size: 12 },
                                callback: function(value) {
                                    return value;
                                }
                            }
                        }
                    }
                }
            });

            // Fetch data initially
            fetchRequirementsData();

            // Refresh button
            document.getElementById('refreshDataBtn').addEventListener('click', () => {
                fetchRequirementsData();
            });
        });
    </script>
</body>
</html>
