<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

include('../config/database.php'); // Reuse your database connection

// Set UTF-8 encoding
mysqli_set_charset($connect, "utf8mb4");

// Get the search term from the AJAX request
$term = isset($_GET['term']) ? trim(mysqli_real_escape_string($connect, $_GET['term'])) : '';

// Query to fetch gate names matching the search term
$query = "
    SELECT DISTINCT GateName, Road
    FROM gate 
    WHERE LOWER(GateName) LIKE LOWER(?) OR LOWER(Road) LIKE LOWER(?)
    LIMIT 15
";
$pattern = "%$term%";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "ss", $pattern, $pattern);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$suggestions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $road = !empty($row['Road']) ? " (" . $row['Road'] . ")" : "";
    $suggestions[] = [
        'label' => $row['GateName'] . $road,
        'value' => $row['GateName']
    ];
}
mysqli_stmt_close($stmt);

ob_end_clean();
// Return JSON response
header('Content-Type: application/json');
echo json_encode($suggestions);
?>