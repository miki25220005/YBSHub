<?php
ob_start(); // Buffer any stray output (warnings, notices)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Do not output errors directly to the buffer

header('Content-Type: application/json');

if (!file_exists('../config/database.php')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection file not found.']);
    exit;
}
include('../config/database.php');

$fromSearch = isset($_GET['from']) ? trim(mysqli_real_escape_string($connect, $_GET['from'])) : '';
$toSearch = isset($_GET['to']) ? trim(mysqli_real_escape_string($connect, $_GET['to'])) : '';

if (empty($fromSearch) || empty($toSearch)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Please provide both origin and destination.']);
    exit;
}

$directResults = [];
$indirectResults = [];

// Step 1: Find all possible GateIDs for the 'from' and 'to' searches
$fromGates = [];
$toGates = [];

$gateSearchQuery = "SELECT GateID, GateName FROM gate WHERE LOWER(GateName) LIKE LOWER(?) OR LOWER(Road) LIKE LOWER(?)";
if ($stmt = mysqli_prepare($connect, $gateSearchQuery)) {
    $fromPattern = "%$fromSearch%";
    $toPattern = "%$toSearch%";
    
    // Search for 'from' gates
    mysqli_stmt_bind_param($stmt, "ss", $fromPattern, $fromPattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $fromGates[] = $row['GateID'];
    }

    // Search for 'to' gates
    mysqli_stmt_bind_param($stmt, "ss", $toPattern, $toPattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $toGates[] = $row['GateID'];
    }
    mysqli_stmt_close($stmt);
}

if (!empty($fromGates) && !empty($toGates)) {
    // Direct Routes Query
    // Added rg_from.Position < rg_to.Position for directional validation
    $directQuery = "
        SELECT DISTINCT
            b.BusID, 
            b.BusNo, 
            b.Color, 
            b.CardQR, 
            r_from.Notes,
            r_from.Direction AS FromDirection,
            r_to.Direction AS ToDirection,
            r_from.RouteID AS FromRouteID,
            r_to.RouteID AS ToRouteID,
            rg_from.GateID AS FromGateID,
            g_from.GateName AS FromGateName,
            g_from.Latitude AS FromLat,
            g_from.Longitude AS FromLng,
            rg_from.Position AS FromPosition,
            rg_to.GateID AS ToGateID,
            g_to.GateName AS ToGateName,
            g_to.Latitude AS ToLat,
            g_to.Longitude AS ToLng,
            rg_to.Position AS ToPosition,
            IF(r_from.RouteID != r_to.RouteID, 1, 0) AS IsCrossTerminus
        FROM bus b
        JOIN route r_from ON b.BusID = r_from.BusID
        JOIN route r_to ON b.BusID = r_to.BusID
        JOIN route_gate rg_from ON r_from.RouteID = rg_from.RouteID
        JOIN gate g_from ON rg_from.GateID = g_from.GateID
        JOIN route_gate rg_to ON r_to.RouteID = rg_to.RouteID
        JOIN gate g_to ON rg_to.GateID = g_to.GateID
        WHERE rg_from.GateID IN (" . implode(',', array_fill(0, count($fromGates), '?')) . ")
        AND rg_to.GateID IN (" . implode(',', array_fill(0, count($toGates), '?')) . ")
        AND (
            (r_from.RouteID = r_to.RouteID AND rg_from.Position < rg_to.Position)
            OR
            (r_from.RouteID != r_to.RouteID AND r_from.Direction != r_to.Direction)
        )
        ORDER BY IsCrossTerminus ASC, CAST(b.BusNo AS UNSIGNED) ASC
        LIMIT 5
    ";

    if ($stmt = mysqli_prepare($connect, $directQuery)) {
        $types = str_repeat('s', count($fromGates) + count($toGates));
        $params = array_merge($fromGates, $toGates);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result !== false) {
            $directResults = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        mysqli_stmt_close($stmt);
    }

    // Indirect Routes Query (if no direct routes are found)
    if (empty($directResults)) {
        mysqli_query($connect, "SET SQL_BIG_SELECTS=1");
        
        $indirectQuery = "
            SELECT DISTINCT
                b1.BusID AS FirstBusID,
                b1.BusNo AS FirstBusNo,
                b1.Color AS FirstBusColor,
                b1.CardQR AS FirstCardQR,
                r1_from.Notes AS FirstNotes,
                r1_from.Direction AS FirstDirection,
                r1_from.RouteID AS FirstRouteID,
                r1_to.RouteID AS FirstToRouteID,
                g_from.GateID AS FromGateID,
                g_from.GateName AS FromGateName,
                g_from.Latitude AS FromLat,
                g_from.Longitude AS FromLng,
                rg_from.Position AS FromPosition,
                g_intermediate.GateID AS IntermediateGateID,
                g_intermediate.GateName AS IntermediateGateName,
                g_intermediate.Latitude AS IntermediateLat,
                g_intermediate.Longitude AS IntermediateLng,
                rg_intermediate.Position AS IntermediateFirstPosition,
                b2.BusID AS SecondBusID,
                b2.BusNo AS SecondBusNo,
                b2.Color AS SecondBusColor,
                b2.CardQR AS SecondCardQR,
                r2_from.Notes AS SecondNotes,
                r2_from.Direction AS SecondDirection,
                r2_from.RouteID AS SecondRouteID,
                r2_to.RouteID AS SecondToRouteID,
                rg_to.Position AS IntermediateSecondPosition,
                g_to.GateID AS ToGateID,
                g_to.GateName AS ToGateName,
                g_to.Latitude AS ToLat,
                g_to.Longitude AS ToLng,
                rg_final.Position AS ToPosition,
                IF(r1_from.RouteID != r1_to.RouteID, 1, 0) AS Leg1Cross,
                IF(r2_from.RouteID != r2_to.RouteID, 1, 0) AS Leg2Cross
            FROM bus b1
            JOIN route r1_from ON b1.BusID = r1_from.BusID
            JOIN route_gate rg_from ON r1_from.RouteID = rg_from.RouteID
            JOIN gate g_from ON rg_from.GateID = g_from.GateID
            JOIN route r1_to ON b1.BusID = r1_to.BusID
            JOIN route_gate rg_intermediate ON r1_to.RouteID = rg_intermediate.RouteID
            JOIN gate g_intermediate ON rg_intermediate.GateID = g_intermediate.GateID
            JOIN route_gate rg_to ON rg_to.GateID = g_intermediate.GateID
            JOIN route r2_from ON rg_to.RouteID = r2_from.RouteID
            JOIN bus b2 ON r2_from.BusID = b2.BusID
            JOIN route r2_to ON b2.BusID = r2_to.BusID
            JOIN route_gate rg_final ON r2_to.RouteID = rg_final.RouteID
            JOIN gate g_to ON rg_final.GateID = g_to.GateID
            WHERE g_from.GateID IN (" . implode(',', array_fill(0, count($fromGates), '?')) . ")
            AND g_to.GateID IN (" . implode(',', array_fill(0, count($toGates), '?')) . ")
            AND (
                (r1_from.RouteID = r1_to.RouteID AND rg_from.Position < rg_intermediate.Position)
                OR (r1_from.RouteID != r1_to.RouteID AND r1_from.Direction != r1_to.Direction)
            )
            AND (
                (r2_from.RouteID = r2_to.RouteID AND rg_to.Position < rg_final.Position)
                OR (r2_from.RouteID != r2_to.RouteID AND r2_from.Direction != r2_to.Direction)
            )
            AND g_from.GateID != g_intermediate.GateID
            AND g_intermediate.GateID != g_to.GateID
            AND b1.BusID != b2.BusID
            ORDER BY Leg1Cross ASC, Leg2Cross ASC, CAST(b1.BusNo AS UNSIGNED) ASC, CAST(b2.BusNo AS UNSIGNED) ASC
            LIMIT 100
        ";

        if ($stmt = mysqli_prepare($connect, $indirectQuery)) {
            $types = str_repeat('s', count($fromGates) + count($toGates));
            $params = array_merge($fromGates, $toGates);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result !== false) {
                $rawIndirectResults = mysqli_fetch_all($result, MYSQLI_ASSOC);
                
                // Deduplicate by FirstBusID and SecondBusID combination
                $seenCombos = [];
                foreach ($rawIndirectResults as $route) {
                    $comboKey = $route['FirstBusID'] . '-' . $route['SecondBusID'];
                    if (!isset($seenCombos[$comboKey])) {
                        $seenCombos[$comboKey] = true;
                        $indirectResults[] = $route;
                        
                        // Limit to 5 unique indirect routes
                        if (count($indirectResults) >= 5) {
                            break;
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Helper function to fetch intermediate gates
function getIntermediateGates($connect, $fromRouteID, $toRouteID, $startPos, $endPos, $isCrossTerminus) {
    $gates = [];
    if (!$isCrossTerminus) {
        $query = "
            SELECT g.GateName, g.Latitude, g.Longitude 
            FROM route_gate rg
            JOIN gate g ON rg.GateID = g.GateID
            WHERE rg.RouteID = ? AND rg.Position > ? AND rg.Position < ?
            ORDER BY rg.Position ASC
        ";
        if ($stmt = mysqli_prepare($connect, $query)) {
            mysqli_stmt_bind_param($stmt, "sii", $fromRouteID, $startPos, $endPos);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) $gates[] = ['GateName' => $row['GateName'], 'Lat' => $row['Latitude'], 'Lng' => $row['Longitude']];
            mysqli_stmt_close($stmt);
        }
    } else {
        // Cross terminus: Fetch from startPos to end of fromRoute, then start of toRoute to endPos
        $query1 = "
            SELECT g.GateName, g.Latitude, g.Longitude 
            FROM route_gate rg
            JOIN gate g ON rg.GateID = g.GateID
            WHERE rg.RouteID = ? AND rg.Position > ?
            ORDER BY rg.Position ASC
        ";
        if ($stmt1 = mysqli_prepare($connect, $query1)) {
            mysqli_stmt_bind_param($stmt1, "si", $fromRouteID, $startPos);
            mysqli_stmt_execute($stmt1);
            $result1 = mysqli_stmt_get_result($stmt1);
            while ($row = mysqli_fetch_assoc($result1)) {
                $gates[] = ['GateName' => $row['GateName'], 'Lat' => $row['Latitude'], 'Lng' => $row['Longitude']];
            }
            mysqli_stmt_close($stmt1);
        }

        $query2 = "
            SELECT g.GateName, g.Latitude, g.Longitude 
            FROM route_gate rg
            JOIN gate g ON rg.GateID = g.GateID
            WHERE rg.RouteID = ? AND rg.Position < ?
            ORDER BY rg.Position ASC
        ";
        if ($stmt2 = mysqli_prepare($connect, $query2)) {
            mysqli_stmt_bind_param($stmt2, "si", $toRouteID, $endPos);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            while ($row = mysqli_fetch_assoc($result2)) {
                // Avoid duplicating the terminus gate
                if (empty($gates) || end($gates)['GateName'] !== $row['GateName']) {
                    $gates[] = ['GateName' => $row['GateName'], 'Lat' => $row['Latitude'], 'Lng' => $row['Longitude']];
                }
            }
            mysqli_stmt_close($stmt2);
        }
    }
    return $gates;
}

// Fetch intermediate stops for direct routes
foreach ($directResults as &$route) {
    $route['IntermediateStops'] = getIntermediateGates($connect, $route['FromRouteID'], $route['ToRouteID'], $route['FromPosition'], $route['ToPosition'], $route['IsCrossTerminus']);
    $route['Type'] = 'Direct';
}

// Fetch intermediate stops for indirect routes
foreach ($indirectResults as &$route) {
    $route['FirstLegStops'] = getIntermediateGates($connect, $route['FirstRouteID'], $route['FirstToRouteID'], $route['FromPosition'], $route['IntermediateFirstPosition'], $route['Leg1Cross']);
    $route['SecondLegStops'] = getIntermediateGates($connect, $route['SecondRouteID'], $route['SecondToRouteID'], $route['IntermediateSecondPosition'], $route['ToPosition'], $route['Leg2Cross']);
    $route['Type'] = 'Indirect';
}

$bufferedOutput = ob_get_clean();

echo json_encode([
    'success' => true,
    'direct' => $directResults ?? [],
    'indirect' => $indirectResults ?? [],
    'debug_output' => $bufferedOutput
]);
?>
