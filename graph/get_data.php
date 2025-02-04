<?php
header('Content-Type: application/json');

// 1. Connect to the database
$servername = "10.0.60.30";
$username   = "colorant";
$password   = "C0l0r@nt*66";
$dbname     = "colorant_tracker";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check for a successful connection
if ($conn->connect_error) {
    echo json_encode([
        "error" => "Connection failed: " . $conn->connect_error
    ]);
    exit();
}

// 2. Check for date filters from GET parameters
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate   = isset($_GET['endDate'])   ? $_GET['endDate']   : '';

// If both start and end dates are provided, use them in the WHERE clause
// Otherwise, fall back to current month/year as default
if (!empty($startDate) && !empty($endDate)) {
    // Use the provided date range
    $sql = "
        SELECT colorant_name, SUM(weight) AS total_used
        FROM colorant_usage
        WHERE entry_date >= '$startDate 00:00:00'
          AND entry_date <= '$endDate 23:59:59'
        GROUP BY colorant_name
        ORDER BY colorant_name
    ";
} else {
    // Default: current month & year
    $currentMonth = date('m');
    $currentYear  = date('Y');
    $sql = "
        SELECT colorant_name, SUM(weight) AS total_used
        FROM colorant_usage
        WHERE MONTH(entry_date) = '$currentMonth'
          AND YEAR(entry_date)  = '$currentYear'
        GROUP BY colorant_name
        ORDER BY colorant_name
    ";
}

$result = $conn->query($sql);

$labels  = [];
$amounts = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[]  = $row['colorant_name'];
        $amounts[] = round((float) $row['total_used'], 2);
    }
}

// 3. Return JSON
echo json_encode([
    "labels"  => $labels,
    "amounts" => $amounts
]);

$conn->close();
?>
