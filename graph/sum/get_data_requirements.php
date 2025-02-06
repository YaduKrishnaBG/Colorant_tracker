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

// Revised query to get total usage by colorant only
$sql = "
    SELECT 
        colorant,
        SUM(required_quantity) AS total_required
    FROM colorant_requirements
    GROUP BY colorant
    ORDER BY colorant ASC
";

$result = $conn->query($sql);

// Prepare arrays for JSON response
$colorants = [];
$totals    = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // X-axis: colorant name
        $colorants[] = $row['colorant'];

        // Y-axis: the total amount used (rounded)
        $totals[] = round((float) $row['total_required'], 2);
    }
}

// Return JSON
echo json_encode([
    "colorants" => $colorants,
    "totals"    => $totals
]);

$conn->close();
?>
