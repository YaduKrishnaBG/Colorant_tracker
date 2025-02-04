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
        /* Button styling */
        button {
            background-color: #4CAF50; /* Green */
            border: none;
            color: white;
            padding: 12px 24px;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            margin: 5px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #45a049;
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
        label {
            font-weight: bold;
            margin: 0 5px;
        }
        input[type="date"] {
            padding: 5px;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <h1>Colorant Usage Chart</h1>

    <!-- Date range filters -->
    <div>
        <label for="startDate">Start Date:</label>
        <input type="date" id="startDate" />
        <label for="endDate">End Date:</label>
        <input type="date" id="endDate" />
        <!-- Button to apply the date filter -->
        <button id="filterBtn">Apply Filter</button>
        <!-- Button to refresh the chart data with no filters (or reset filters) -->
        <button id="refreshBtn">This Month</button>
    </div>

    <!-- Chart Container -->
    <div id="chartContainer">
        <canvas id="usageChart"></canvas>
    </div>

    <!-- Include Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let usageChart;

        // Function to fetch data from get_data.php with optional start/end dates
        function fetchDataAndUpdateChart(startDate = '', endDate = '') {
            // Build query parameters
            const params = new URLSearchParams();
            if (startDate) params.append('startDate', startDate);
            if (endDate)   params.append('endDate',   endDate);

            // Fetch data with query params
            fetch('get_data.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error from server:', data.error);
                        return;
                    }

                    usageChart.data.labels = data.labels;
                    usageChart.data.datasets[0].data = data.amounts;
                    usageChart.update();
                })
                .catch(error => console.error('Error fetching chart data:', error));
        }

        // Initialize chart when page loads
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('usageChart').getContext('2d');

            usageChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Colorant Usage (lbs)',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
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
                                    return `${context.dataset.label}: ${context.raw} lbs`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Colorants',
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
                                text: 'Usage (lbs)',
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
                                    return value + ' lbs';
                                }
                            }
                        }
                    }
                }
            });

            // Fetch data initially for the current month (default)
            fetchDataAndUpdateChart();

            // "Apply Filter" button: fetch data with chosen date range
            document.getElementById('filterBtn').addEventListener('click', () => {
                const startDate = document.getElementById('startDate').value;
                const endDate   = document.getElementById('endDate').value;
                fetchDataAndUpdateChart(startDate, endDate);
            });

            // "This Month" button: reset to current month data
            document.getElementById('refreshBtn').addEventListener('click', () => {
                // Clear date inputs (optional)
                document.getElementById('startDate').value = '';
                document.getElementById('endDate').value   = '';

                // Fetch data for the current month
                fetchDataAndUpdateChart();
            });
        });
    </script>
</body>
</html>
