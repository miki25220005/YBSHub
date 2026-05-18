<?php
// api/get_nearest_gate.php
header('Content-Type: application/json');

include('../config/database.php');

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($lat === null || $lng === null || !is_finite($lat) || !is_finite($lng)) {
    echo json_encode(['success' => false, 'error' => 'Invalid coordinates provided.']);
    exit;
}

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['success' => false, 'error' => 'Coordinates are out of valid range.']);
    exit;
}

mysqli_set_charset($connect, "utf8mb4");

// Haversine formula to calculate distance in kilometers.
// Cast/trim latitude and longitude because some rows may contain padded strings.
$query = "
    SELECT GateID, GateName, Road,
    (
        6371 * acos(
            cos(radians(?)) *
            cos(radians(CAST(TRIM(Latitude) AS DECIMAL(10,7)))) *
            cos(radians(CAST(TRIM(Longitude) AS DECIMAL(10,7))) - radians(?)) +
            sin(radians(?)) *
            sin(radians(CAST(TRIM(Latitude) AS DECIMAL(10,7))))
        )
    ) AS distance
    FROM gate
    WHERE Latitude IS NOT NULL
      AND Longitude IS NOT NULL
      AND TRIM(Latitude) <> ''
      AND TRIM(Longitude) <> ''
    ORDER BY distance ASC
    LIMIT 1
";

$stmt = mysqli_prepare($connect, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ddd", $lat, $lng, $lat);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode([
            'success' => true,
            'gateID' => $row['GateID'],
            'gateName' => $row['GateName'],
            'road' => $row['Road'],
            'distance' => round($row['distance'], 3)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No gates found nearby.']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database query failed.']);
}
?>
