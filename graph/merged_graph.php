<?php
// merged_graph.php

// Database configuration
define('DB_SERVER', '10.0.60.30');
define('DB_USERNAME', 'colorant');
define('DB_PASSWORD', '**********');
define('DB_NAME', 'colorant_tracker');

// Establish a database connection
function get_db_connection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . htmlspecialchars($conn->connect_error));
    }
    return $conn;
}

// Fetch aggregated usage data for each machine and colorant from the past two days or today
function fetch_colorant_usage($conn, $time_frame) {
    $usageDataMap = [];
    
    // Determine the date condition based on the selected time frame
    if ($time_frame === 'today') {
        $date_condition = "entry_date >= CURDATE()";
    } else {
        $date_condition = "entry_date >= CURDATE() - INTERVAL 2 DAY";
    }
    
    $query = "
        SELECT 
            machine_id,
            colorant_name,
            SUM(weight) AS total_usage
        FROM colorant_usage
        WHERE $date_condition
        GROUP BY machine_id, colorant_name
    ";
    
    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            // Create a unique key combining machine and colorant
            $key = $row['machine_id'] . "-" . $row['colorant_name'];
            $usageDataMap[$key] = [
                'machine_id'     => $row['machine_id'],
                'colorant'       => $row['colorant_name'],
                'total_usage'    => floatval($row['total_usage']),
                'total_required' => 0 // Initialize required value to zero
            ];
        }
        $result->free();
    } else {
        die("Error fetching usage data: " . htmlspecialchars($conn->error));
    }
    
    return $usageDataMap;
}

// Fetch aggregated requirements data (summing duplicate rows) grouped by work center and colorant
function fetch_colorant_requirements($conn, &$usageDataMap) {
    $query = "
        SELECT 
            work_center_code,
            colorant,
            SUM(required_quantity) AS total_required
        FROM colorant_requirements
        GROUP BY work_center_code, colorant
    ";
    
    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            // Build the key (using work_center_code as the machine ID)
            $key = $row['work_center_code'] . "-" . $row['colorant'];
            if (isset($usageDataMap[$key])) {
                $usageDataMap[$key]['total_required'] = floatval($row['total_required']);
            } else {
                $usageDataMap[$key] = [
                    'machine_id'     => $row['work_center_code'],
                    'colorant'       => $row['colorant'],
                    'total_usage'    => 0,
                    'total_required' => floatval($row['total_required'])
                ];
            }
        }
        $result->free();
    } else {
        die("Error fetching requirements data: " . htmlspecialchars($conn->error));
    }
}

// Prepare arrays for Chart.js (labels, usage values, and requirement values)
function prepare_chart_data($usageDataMap) {
    $labels      = [];
    $usageValues = [];
    $reqValues   = [];
    
    // Sort by key (machine-colorant) for consistency
    ksort($usageDataMap);
    
    foreach ($usageDataMap as $data) {
        // Format label as "Machine - Colorant"
        $labels[]      = "{$data['machine_id']} - {$data['colorant']}";
        $usageValues[] = $data['total_usage'];
        $reqValues[]   = $data['total_required'];
    }
    
    return [
        'labels'             => $labels,
        'usageValues'        => $usageValues,
        'requirementValues'  => $reqValues
    ];
}

// ---------------------
// Main Execution Flow
// ---------------------
$conn = get_db_connection();

// Get the selected time frame from GET parameters (default: past two days)
$time_frame = (isset($_GET['time_frame']) && $_GET['time_frame'] === 'today') ? 'today' : 'past_two_days';

// Fetch and merge data from both tables
$usageDataMap = fetch_colorant_usage($conn, $time_frame);
fetch_colorant_requirements($conn, $usageDataMap);
$chartData = prepare_chart_data($usageDataMap);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Colorant Usage vs. Requirements</title>
    <!-- Responsive Meta Tag -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <!-- CSS Styling -->
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .button-group {
            text-align: center;
            margin-bottom: 20px;
        }
        .button-group a,
        .button-group button {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px 10px;
            text-decoration: none;
            color: #fff;
            background-color: #3498db;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-size: 16px;
            cursor: pointer;
            border: none;
        }
        .button-group a.active,
        .button-group button.active,
        .button-group a:hover,
        .button-group button:hover {
            background-color: #2980b9;
        }
        canvas {
            width: 100% !important;
            height: auto !important;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            h2 {
                font-size: 1.5em;
            }
            .button-group a,
            .button-group button {
                padding: 8px 16px;
                margin: 5px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Colorant Usage vs. Requirements</h2>
    
    <!-- Time Frame and Pairs Toggle Buttons -->
    <div class="button-group">
        <a href="?time_frame=today" class="<?php echo ($time_frame === 'today') ? 'active' : ''; ?>">Today</a>
        <a href="?time_frame=past_two_days" class="<?php echo ($time_frame === 'past_two_days') ? 'active' : ''; ?>">Past Two Days</a>
        <button id="togglePairs">Show Only Pairs</button>
    </div>
    
    <!-- Group Filter Buttons -->
    <div class="button-group" id="groupFilterButtons">
        <button id="btnAllGroups" class="active">All Groups</button>
        <button id="btnBM">BM Machines</button>
        <button id="btnIM1">IM Machines (101-114)</button>
        <button id="btnIM2">IM Machines (115-129)</button>
    </div>
    
    <!-- Canvas element for Chart.js -->
    <canvas id="colorantChart"></canvas>
</div>

<script>
// Extract PHP data into JavaScript variables
const chartData = <?php echo json_encode($chartData); ?>;
const { labels, usageValues, requirementValues } = chartData;

// Build an array of objects for easier filtering.
// Each object contains { label, usage, req } where label is in "Machine - Colorant" format.
const originalData = [];
for (let i = 0; i < labels.length; i++) {
    originalData.push({
        label: labels[i],
        usage: usageValues[i],
        req: requirementValues[i]
    });
}

// Define chart colors for each dataset
const colors = {
    usage: 'rgba(54, 162, 235, 0.7)',
    requirement: 'rgba(255, 99, 132, 0.7)'
};

// Get the canvas context and create the Chart.js bar chart
const ctx = document.getElementById('colorantChart').getContext('2d');
const colorantChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels, // initial full data
        datasets: [
            {
                label: 'Usage (lbs)',
                data: usageValues, // initial full data
                backgroundColor: colors.usage,
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Requirement (lbs)',
                data: requirementValues, // initial full data
                backgroundColor: colors.requirement,
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        if (context.parsed.y !== null) {
                            label += context.parsed.y + ' lbs';
                        }
                        return label;
                    }
                }
            },
            legend: {
                position: 'top',
                labels: { font: { size: 14 } }
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Machine & Colorant',
                    font: { size: 16, weight: 'bold' }
                },
                ticks: {
                    maxRotation: 90,
                    minRotation: 45,
                    autoSkip: false
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Weight (lbs)',
                    font: { size: 16, weight: 'bold' }
                },
                ticks: {
                    callback: function(value) {
                        return value + ' lbs';
                    }
                }
            }
        }
    }
});

// Filtering variables
// groupFilter options: "all", "BM", "IM101-114", "IM115-129"
let groupFilter = "all";
// Toggle for showing only pairs (both usage and requirement nonzero)
let showingPairsOnly = false;

// Function to update the chart based on active filters (group and pairs)
function updateChart() {
    let filteredData = originalData.slice();

    // Apply group filter if not "all"
    if (groupFilter !== "all") {
        filteredData = filteredData.filter(item => {
            const machine = item.label.split(" - ")[0];
            if (groupFilter === "BM") {
                return machine.startsWith("BM");
            } else if (groupFilter === "IM101-114") {
                if (machine.startsWith("IM")) {
                    const num = parseInt(machine.substring(2));
                    return num >= 101 && num <= 114;
                }
                return false;
            } else if (groupFilter === "IM115-129") {
                if (machine.startsWith("IM")) {
                    const num = parseInt(machine.substring(2));
                    return num >= 115 && num <= 129;
                }
                return false;
            }
            return true;
        });
    }

    // If "Show Only Pairs" is active, filter out entries where either usage or requirement is zero.
    if (showingPairsOnly) {
        filteredData = filteredData.filter(item => item.usage > 0 && item.req > 0);
    }

    // Build new arrays from the filtered data
    const newLabels = filteredData.map(item => item.label);
    const newUsageValues = filteredData.map(item => item.usage);
    const newRequirementValues = filteredData.map(item => item.req);

    // Update the chart data and refresh the chart
    colorantChart.data.labels = newLabels;
    colorantChart.data.datasets[0].data = newUsageValues;
    colorantChart.data.datasets[1].data = newRequirementValues;
    colorantChart.update();
}

// Event listeners for group filter buttons
document.getElementById('btnAllGroups').addEventListener('click', function() {
    groupFilter = "all";
    // Update active styling for group buttons
    this.classList.add("active");
    document.getElementById('btnBM').classList.remove("active");
    document.getElementById('btnIM1').classList.remove("active");
    document.getElementById('btnIM2').classList.remove("active");
    updateChart();
});
document.getElementById('btnBM').addEventListener('click', function() {
    groupFilter = "BM";
    this.classList.add("active");
    document.getElementById('btnAllGroups').classList.remove("active");
    document.getElementById('btnIM1').classList.remove("active");
    document.getElementById('btnIM2').classList.remove("active");
    updateChart();
});
document.getElementById('btnIM1').addEventListener('click', function() {
    groupFilter = "IM101-114";
    this.classList.add("active");
    document.getElementById('btnAllGroups').classList.remove("active");
    document.getElementById('btnBM').classList.remove("active");
    document.getElementById('btnIM2').classList.remove("active");
    updateChart();
});
document.getElementById('btnIM2').addEventListener('click', function() {
    groupFilter = "IM115-129";
    this.classList.add("active");
    document.getElementById('btnAllGroups').classList.remove("active");
    document.getElementById('btnBM').classList.remove("active");
    document.getElementById('btnIM1').classList.remove("active");
    updateChart();
});

// Event listener for the "Show Only Pairs" toggle button
document.getElementById('togglePairs').addEventListener('click', function() {
    showingPairsOnly = !showingPairsOnly;
    // Change the button text accordingly
    this.textContent = showingPairsOnly ? "Show All (Including Unpaired)" : "Show Only Pairs";
    updateChart();
});
</script>
</body>
</html>
