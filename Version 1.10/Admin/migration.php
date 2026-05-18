<?php
$connect = mysqli_connect('localhost', 'root', '', 'YBSHub');

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Check if column exists, if not add it
$checkColQuery = "SHOW COLUMNS FROM route_gate LIKE 'Position'";
$result = mysqli_query($connect, $checkColQuery);

if (mysqli_num_rows($result) == 0) {
    echo "Adding Position column...\n";
    $alterQuery = "ALTER TABLE route_gate ADD COLUMN Position INT DEFAULT 0";
    if (mysqli_query($connect, $alterQuery)) {
        echo "Position column added successfully.\n";
    } else {
        die("Error adding column: " . mysqli_error($connect));
    }
} else {
    echo "Position column already exists.\n";
}

// 2. Populate positions for each route to preserve implicit order
echo "Populating gate positions...\n";
$routesQuery = "SELECT DISTINCT RouteID FROM route_gate";
$routesResult = mysqli_query($connect, $routesQuery);

while ($routeRow = mysqli_fetch_assoc($routesResult)) {
    $routeID = $routeRow['RouteID'];
    
    // Fetch gates for this route in their natural order (or we can just fetch them)
    $gatesQuery = "SELECT GateID FROM route_gate WHERE RouteID = '$routeID'";
    $gatesResult = mysqli_query($connect, $gatesQuery);
    
    $pos = 1;
    while ($gateRow = mysqli_fetch_assoc($gatesResult)) {
        $gateID = $gateRow['GateID'];
        // Note: if a route visits the same gate multiple times, this simple UPDATE will update both to the SAME position, which is a flaw.
        // However, without a primary key or a way to uniquely identify rows, we might run into issues.
        // Let's assume gates are unique per route, or we limit 1.
        $updateQuery = "UPDATE route_gate SET Position = $pos WHERE RouteID = '$routeID' AND GateID = '$gateID' AND Position = 0 LIMIT 1";
        mysqli_query($connect, $updateQuery);
        $pos++;
    }
}

echo "Migration completed.\n";
?>
