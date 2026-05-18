<?php
session_start();
include('config/database.php');
if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}
include('includes/maintenance_check.php'); // Include the maintenance check function

// Check for active maintenance
checkMaintenance($connect);

// Function to convert Myanmar numerals to English numerals
function convertMyanmarToEnglishNumerals($input) {
    $myanmarNumerals = ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'];
    $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($myanmarNumerals, $englishNumerals, $input);
}

// Include centralized analytics tracking
if (file_exists('core/analytics.php')) {
    include_once('core/analytics.php');
}
if (function_exists('logStat')) {
    logStat($connect, 'page_view', 'BusList.php');
}

// Bus search logic
$busSearchQuery = isset($_GET['bus_search']) ? mysqli_real_escape_string($connect, trim($_GET['bus_search'])) : '';

// Convert Myanmar numerals to English numerals in the search query
$busSearchQueryConverted = convertMyanmarToEnglishNumerals($busSearchQuery);

if ($busSearchQuery) {
    if (function_exists('logStat')) {
        logStat($connect, 'search_bus', $busSearchQuery);
    }
}

// Query for bus list with search, showing Forward notes for Forward/Reverse buses, Single notes for Single route buses
$query = "
    SELECT 
        bus.BusID, 
        bus.BusNo, 
        bus.Path, 
        bus.CardQR, 
        bus.Color,
        COALESCE(
            (SELECT Notes FROM route r2 
             WHERE r2.BusID = bus.BusID 
             AND r2.Direction = 'Forward' 
             LIMIT 1),
            (SELECT Notes FROM route r3 
             WHERE r3.BusID = bus.BusID 
             AND r3.Direction = 'Single' 
             LIMIT 1)
        ) AS Notes
    FROM bus
    WHERE LOWER(bus.BusNo) LIKE LOWER(?)
       OR LOWER(bus.Path) LIKE LOWER(?)
    ORDER BY CAST(bus.BusNo AS UNSIGNED) ASC";
$stmt = mysqli_prepare($connect, $query);
$searchParam = "%$busSearchQueryConverted%";
mysqli_stmt_bind_param($stmt, "ss", $searchParam, $searchParam);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$buses = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $buses[] = $row;
    }
} else {
    die("Error fetching data: " . mysqli_error($connect));
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View the complete list of Yangon Bus Service (YBS) routes. Find detailed bus numbers, paths, and public transport information in Yangon.">
    <meta name="keywords" content="YBS Bus List, Yangon Bus Service, YBS routes, Yangon Public Transport, Yangon Bus">
    <title>YBS Hub - Yangon Bus Service List</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="assets/images/Logo/YBS_Web_Logo.svg">
    <style>
        /* Minimal focus style for accessibility */  
        input:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php 
    include('includes/header.php');
    ?>

    <!-- Search Section -->
    <div class="hidden md:block max-w-7xl mx-auto px-4 mt-8 sticky top-16 z-40 bg-gray-100 py-3 rounded-b-lg shadow-sm border-b border-gray-200">
        <div class="flex items-center">
            <form method="GET" action="BusList.php" class="w-full relative">
                <input 
                    type="text" 
                    name="bus_search" 
                    placeholder="Search by Bus Number" 
                    class="w-full p-2 border rounded-lg"
                    value="<?php echo htmlspecialchars($busSearchQuery); ?>">
                <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Bus List Grid -->
<div class="max-w-7xl mx-auto px-4 mt-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" style="grid-auto-rows: minmax(0, auto);">
        <?php if (!empty($buses)): ?>
            <?php foreach ($buses as $bus): ?>
                <a href="bus_details.php?BusID=<?php echo htmlspecialchars($bus['BusID']); ?>" class="block">
                    <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow flex flex-col h-full">
                        <div class="text-center mb-4">
                            <div class="w-20 h-20 mx-auto border-2 rounded-lg flex items-center justify-center text-xl font-bold text-white"
                                 style="background-color: <?php echo htmlspecialchars($bus['Color']); ?>; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">
                                <?php echo htmlspecialchars($bus['BusNo']); ?>
                            </div>
                        </div>
                        <div class="space-y-2 flex-1">
                            <div class="font-medium text-center overflow-y-auto" style="max-height: 100px;">
                                <b><?php echo htmlspecialchars($bus['Notes'] ?? 'No Route Available'); ?></b>
                            </div>
                            <?php
                            $cardQR = $bus['CardQR'] ?? 'N/A';
                            $iconClass = '';
                            $textColor = '';
                            $bgColor = '';
                            if ($cardQR === 'Yes') {
                                $iconClass = 'fas fa-check-circle';
                                $textColor = 'text-green-600';
                                $bgColor = 'bg-green-100';
                            } elseif ($cardQR === 'No') {
                                $iconClass = 'fas fa-times-circle';
                                $textColor = 'text-red-600';
                                $bgColor = 'bg-red-100';
                            } else {
                                $iconClass = 'fas fa-question-circle';
                                $textColor = 'text-gray-600';
                                $bgColor = 'bg-gray-100';
                            }
                            ?>
                            <p class="text-sm text-center">
                                <span class="inline-flex items-center px-2 py-1 rounded-full <?php echo $bgColor; ?> <?php echo $textColor; ?>">
                                    <i class="<?php echo $iconClass; ?> mr-1"></i>
                                    Card/QR: <?php echo htmlspecialchars($cardQR); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center col-span-full text-gray-600">No buses found.</p>
        <?php endif; ?>
    </div>
</div>

    <?php include('includes/footer.php'); ?>
</body>
</html>