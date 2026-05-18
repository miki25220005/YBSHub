<?php
session_start();
include('config/database.php');
if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}
include('includes/maintenance_check.php'); // Include the maintenance check function

// Check for active maintenance
checkMaintenance($connect);

// Include centralized analytics tracking
if (file_exists('core/analytics.php')) {
    include_once('core/analytics.php');
}
if (function_exists('logStat')) {
    logStat($connect, 'page_view', 'Township Details');
}

// Get Township ID from URL
$townshipID = isset($_GET['TownshipID']) ? intval($_GET['TownshipID']) : 0;

// Handle Search Query
$searchQuery = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : '';

// Fetch Township Info
$townshipQuery = "
    SELECT TownshipName, 
           (SELECT COUNT(*) FROM gate WHERE gate.TownshipID = township.TownshipID) AS TotalGates
    FROM township 
    WHERE TownshipID = ?";
$stmt = mysqli_prepare($connect, $townshipQuery);
mysqli_stmt_bind_param($stmt, "i", $townshipID);
$stmt->execute();
$townshipResult = $stmt->get_result();
$township = $townshipResult->fetch_assoc();

// Fetch Gates and Buses Arrived per Gate
$gatesQuery = "
    SELECT 
        gate.GateID, 
        gate.GateName, 
        gate.Road
    FROM gate
    WHERE gate.TownshipID = ?
      AND (? = '' OR gate.GateName LIKE ?) 
    ORDER BY gate.GateName ASC";

// Prepare and bind parameters
$stmt = $connect->prepare($gatesQuery);
$searchTerm = "%$searchQuery%";
$stmt->bind_param("iss", $townshipID, $searchQuery, $searchTerm);
$stmt->execute();
$gatesResult = $stmt->get_result();
$gates = $gatesResult->fetch_all(MYSQLI_ASSOC);

// Fetch buses for each gate
$gateBuses = [];
foreach ($gates as $gate) {
    $gateID = $gate['GateID'];
    $busesQuery = "
        SELECT DISTINCT bus.BusID, bus.BusNo, bus.Color
        FROM route_gate
        JOIN route ON route_gate.RouteID = route.RouteID
        JOIN bus ON route.BusID = bus.BusID
        WHERE route_gate.GateID = ?
        ORDER BY CAST(bus.BusNo AS UNSIGNED) ASC
    ";
    $stmt = mysqli_prepare($connect, $busesQuery);
    mysqli_stmt_bind_param($stmt, "s", $gateID);
    mysqli_stmt_execute($stmt);
    $busesResult = mysqli_stmt_get_result($stmt);
    $gateBuses[$gateID] = mysqli_fetch_all($busesResult, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($township['TownshipName'] ?? 'Township'); ?> - YBS Hub</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/Logo/web_logo.png">
</head>
<body class="bg-gray-100 min-h-screen">

<!-- Navbar -->
<?php include('includes/header.php'); ?>

    <!-- Search & Township Info Container -->
    <div class="max-w-7xl mx-auto px-4 mt-8">
        <!-- Search Bar -->
        <div class="flex items-center justify-between">
            <form method="GET" action="township_details.php" class="w-full md:w-2/3 relative hidden md:block">
                <input type="hidden" name="TownshipID" value="<?php echo htmlspecialchars($townshipID); ?>"> 
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search by Gate Name" 
                    class="w-full p-2 border rounded-lg"
                    value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </form>

            <!-- Township Info -->
            <div class="hidden md:block text-right w-1/3">
                <h2 class="text-xl font-semibold">
                <i class="fa-solid fa-city text-blue-500 mr-2"></i>
                    Township Name - <?php echo htmlspecialchars($township['TownshipName'] ?? 'Unknown'); ?>
                </h2>

                <p class="text-gray-600 mt-2">
                    Total Gates in Township - <?php echo htmlspecialchars($township['TotalGates'] ?? '0'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Mobile View (Show Township Info Below Search on Small Screens) -->
    <div class="max-w-7xl mx-auto px-4 mt-4 md:hidden">
        <h2 class="text-xl font-semibold flex items-center">
            <i class="fa-solid fa-city text-blue-500 mr-2"></i> <!-- City Icon -->
            Township Name - <?php echo htmlspecialchars($township['TownshipName'] ?? 'Unknown'); ?>
        </h2>
        <p class="text-gray-600 mt-2">
            Total Gates in Township - <?php echo htmlspecialchars($township['TotalGates'] ?? '0'); ?>
        </p>
    </div>

<!-- Gates Grid -->
<div class="max-w-7xl mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8" style="grid-auto-rows: minmax(0, auto);">
        <?php if (!empty($gates)): ?>
            <?php $counter = 1; ?>
            <?php foreach ($gates as $gate): ?>
                <a href="gate_details.php?GateID=<?php echo htmlspecialchars($gate['GateID']); ?>" class="block">
                    <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow flex flex-col h-full">
                        <!-- Gate Header -->
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-center mb-2 flex items-center justify-center">
                                <img src="assets/images/SVG/bus_gate.svg" alt="Gate Icon" class="w-7 h-7 mr-2">
                                <?php echo htmlspecialchars($gate['GateName']); ?>
                            </h3>
                            <p class="text-gray-500 text-center text-sm">
                                Road: <?php echo htmlspecialchars($gate['Road'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <!-- Buses Arrived with SVG -->
                        <div class="flex-1 flex flex-col">
                            <div class="text-center flex items-center justify-center gap-2 mb-2">
                                <img src="assets/images/SVG/bus-arrived.svg" alt="Bus Arrived Icon" class="w-8 h-7">
                                <p class="text-sm text-gray-600"><i>Buses Arrived:</i></p>
                            </div>
                            <div class="flex flex-wrap justify-center gap-2 mt-1 overflow-y-auto" style="max-height: 120px;">
                                <?php if (!empty($gateBuses[$gate['GateID']])): ?>
                                    <?php foreach ($gateBuses[$gate['GateID']] as $bus): ?>
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white text-sm font-bold"
                                              style="background-color: <?php echo htmlspecialchars($bus['Color']); ?>; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">
                                            <?php echo htmlspecialchars($bus['BusNo']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500">No buses arrived.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center col-span-full text-gray-600 flex flex-col items-center">
                <i class="fas fa-exclamation-circle text-red-500 text-3xl mb-2"></i> <!-- Error Icon -->
                <p>No gates found in this township.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>

</body>
</html>