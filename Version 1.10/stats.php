<?php
session_start();
include('config/database.php');
if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}

// Include centralized analytics tracking (optional for admin page, but good for consistency)
if (file_exists('core/analytics.php')) {
    include_once('core/analytics.php');
}

// Default time period
$timePeriod = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$currentDate = new DateTime('now', new DateTimeZone('Europe/London')); // BST, updated to 11:26 AM BST on Friday, June 13, 2025
$startDate = clone $currentDate;

// Adjust start date based on period
switch ($timePeriod) {
    case 'daily':
        $startDate->modify('-1 day');
        $dateFormat = '%Y-%m-%d';
        $groupBy = 'DATE(timestamp)';
        break;
    case 'yearly':
        $startDate->modify('-1 year');
        $dateFormat = '%Y';
        $groupBy = 'YEAR(timestamp)';
        break;
    case 'monthly':
    default:
        $startDate->modify('-1 month');
        $dateFormat = '%Y-%m';
        $groupBy = 'DATE_FORMAT(timestamp, "%Y-%m")';
        break;
}

// Fetch page visit stats
$pageVisitsQuery = "
    SELECT $groupBy as period, action_value as page_name, COUNT(*) as visit_count
    FROM website_stats
    WHERE action_type = 'page_view' 
    AND timestamp >= ?
    GROUP BY period, action_value
    ORDER BY visit_count DESC";
$stmt = mysqli_prepare($connect, $pageVisitsQuery);
$startDateStr = $startDate->format('Y-m-d H:i:s');
mysqli_stmt_bind_param($stmt, "s", $startDateStr);
mysqli_stmt_execute($stmt);
$pageVisitsResult = mysqli_stmt_get_result($stmt);

$pageNames = [];
$visitCounts = [];
while ($row = mysqli_fetch_assoc($pageVisitsResult)) {
    $pageNames[] = $row['page_name'] . ' (' . $row['period'] . ')';
    $visitCounts[] = $row['visit_count'];
}
mysqli_stmt_close($stmt);

// Fetch most searched townships (match with township table)
$townshipSearchQuery = "
    SELECT 
        t.TownshipName as township_name, 
        COUNT(*) as search_count,
        t.TownshipID
    FROM website_stats ws
    JOIN township t ON ws.action_value LIKE CONCAT('%', t.TownshipName, '%')
    WHERE ws.action_type = 'search' 
    AND ws.action_value != ''
    AND ws.timestamp >= ?
    GROUP BY t.TownshipID, t.TownshipName"; // Added GROUP BY
$stmt = mysqli_prepare($connect, $townshipSearchQuery);
$startDateStr = $startDate->format('Y-m-d H:i:s');
mysqli_stmt_bind_param($stmt, "s", $startDateStr);
mysqli_stmt_execute($stmt);
$townshipSearchResult = mysqli_stmt_get_result($stmt);

$townshipNames = [];
$townshipSearchCounts = [];
$townshipIDs = [];
while ($row = mysqli_fetch_assoc($townshipSearchResult)) {
    $townshipNames[] = $row['township_name'];
    $townshipSearchCounts[] = $row['search_count'];
    $townshipIDs[] = $row['TownshipID'];
}
mysqli_stmt_close($stmt);

// Fetch most searched bus numbers (match with bus table)
$busSearchQuery = "
    SELECT 
        b.BusNo as bus_number, 
        COUNT(*) as search_count,
        b.BusID
    FROM website_stats ws
    JOIN bus b ON ws.action_value LIKE CONCAT('%', b.BusNo, '%')
    WHERE ws.action_type = 'search_bus' 
    AND ws.action_value != ''
    AND ws.timestamp >= ?
    GROUP BY b.BusID, b.BusNo"; // Added GROUP BY
$stmt = mysqli_prepare($connect, $busSearchQuery);
$startDateStr = $startDate->format('Y-m-d H:i:s');
mysqli_stmt_bind_param($stmt, "s", $startDateStr);
mysqli_stmt_execute($stmt);
$busSearchResult = mysqli_stmt_get_result($stmt);

$busNumbers = [];
$busSearchCounts = [];
$busIDs = [];
while ($row = mysqli_fetch_assoc($busSearchResult)) {
    $busNumbers[] = $row['bus_number'];
    $busSearchCounts[] = $row['search_count'];
    $busIDs[] = $row['BusID'];
}
mysqli_stmt_close($stmt);

// Fetch unique visitors (count distinct session_id)
$uniqueVisitsQuery = "
    SELECT $groupBy as period, COUNT(DISTINCT session_id) as unique_visitors
    FROM website_stats
    WHERE session_id IS NOT NULL 
    AND timestamp >= ?
    GROUP BY period
    ORDER BY period ASC";
$stmt = mysqli_prepare($connect, $uniqueVisitsQuery);
mysqli_stmt_bind_param($stmt, "s", $startDateStr);
mysqli_stmt_execute($stmt);
$uniqueVisitsResult = mysqli_stmt_get_result($stmt);

$uniquePeriods = [];
$uniqueCounts = [];
while ($row = mysqli_fetch_assoc($uniqueVisitsResult)) {
    $uniquePeriods[] = $row['period'];
    $uniqueCounts[] = $row['unique_visitors'];
}
mysqli_stmt_close($stmt);

// Fetch traffic sources (referrers)
$referrerQuery = "
    SELECT 
        CASE 
            WHEN referrer LIKE '%facebook.com%' THEN 'Facebook'
            WHEN referrer LIKE '%google.com%' THEN 'Google'
            WHEN referrer = '' OR referrer IS NULL THEN 'Direct / Unknown'
            ELSE SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1)
        END as source,
        COUNT(*) as visit_count
    FROM website_stats
    WHERE action_type = 'page_view'
    AND timestamp >= ?
    GROUP BY source
    ORDER BY visit_count DESC
    LIMIT 10";
$stmt = mysqli_prepare($connect, $referrerQuery);
mysqli_stmt_bind_param($stmt, "s", $startDateStr);
mysqli_stmt_execute($stmt);
$referrerResult = mysqli_stmt_get_result($stmt);

$referrers = [];
while ($row = mysqli_fetch_assoc($referrerResult)) {
    $referrers[] = $row;
}
mysqli_stmt_close($stmt);

// Fetch device usage stats with case-insensitive handling
$deviceUsageQuery = "
    SELECT LOWER(device_type) as device_type, COUNT(*) as usage_count
    FROM website_stats
    WHERE device_type IS NOT NULL
    AND timestamp >= ?
    GROUP BY LOWER(device_type)";
$stmt = mysqli_prepare($connect, $deviceUsageQuery);
$startDateStr = $startDate->format('Y-m-d H:i:s');
mysqli_stmt_bind_param($stmt, "s", $startDateStr);
mysqli_stmt_execute($stmt);
$deviceUsageResult = mysqli_stmt_get_result($stmt);

// Debug: Output raw data to check device types
$debugDeviceTypes = [];
while ($row = mysqli_fetch_assoc($deviceUsageResult)) {
    $debugDeviceTypes[] = $row['device_type'];
}
error_log("Debug Device Types: " . json_encode($debugDeviceTypes)); // Log to server error log
mysqli_stmt_close($stmt);

// Reset and fetch again for display
$stmt = mysqli_prepare($connect, $deviceUsageQuery);
mysqli_stmt_bind_param($stmt, "s", $startDateStr);
mysqli_stmt_execute($stmt);
$deviceUsageResult = mysqli_stmt_get_result($stmt);

$deviceTypes = [];
$deviceUsageCounts = [];
while ($row = mysqli_fetch_assoc($deviceUsageResult)) {
    $deviceTypes[] = ucfirst($row['device_type']); // Capitalize for display consistency
    $deviceUsageCounts[] = $row['usage_count'];
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Statistics - YBS Hub Guide</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        nav { background-color: white; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .hidden { display: none !important; }
        @media (min-width: 768px) { nav .desktop-nav { display: flex !important; } }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #e5e7eb; padding: 0.5rem; text-align: left; }
        th { background-color: #f9fafb; }
        .chart-container { position: relative; height: 400px; width: 100%; margin-top: 1rem; }
    </style>
</head>
<body class="bg-gray-100">
    <div id="app">
        <!-- Navbar -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="relative flex items-center justify-between h-16">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold">YBS Hub</h1>
                    </div>
                    <button id="mobile-menu-button" class="md:hidden p-2">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="hidden md:flex items-center space-x-4 desktop-nav">
                        <a href="index.php" class="text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-home mr-2"></i> Home</a>
                        <a href="BusList.php" class="text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-bus mr-2"></i> Bus</a>
                        <a href="index.php" class="text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-city mr-2"></i> Township</a>
                        <a href="GateList.php" class="text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-door-open mr-2"></i> Gate</a>
                        <a href="Destination.php" class="text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-map-marker-alt mr-2"></i> Destination</a>
                        <a href="About_Us.php" class="text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-info-circle mr-2"></i> About Us</a>
                        <a href="stats.php" class="text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-chart-bar mr-2"></i> Statistics</a>
                        <a href="Admin/AdminLogin.php" class="text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-user-tie mr-2"></i> Admin</a>
                    </div>
                </div>
            </div>
            <div id="mobile-menu" class="hidden md:hidden bg-white shadow-lg absolute top-16 left-0 w-full z-50">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="index.php" class="block text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-home mr-2"></i> Home</a>
                    <a href="BusList.php" class="block text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-bus mr-2"></i> Bus</a>
                    <a href="index.php" class="block text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-city mr-2"></i> Township</a>
                    <a href="GateList.php" class="block text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-door-open mr-2"></i> Gate</a>
                    <a href="Destination.php" class="block text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-map-marker-alt mr-2"></i> Destination</a>
                    <a href="About_Us.php" class="block text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-info-circle mr-2"></i> About Us</a>
                    <a href="stats.php" class="block text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-chart-bar mr-2"></i> Statistics</a>
                    <a href="Admin/AdminLogin.php" class="block text-gray-700 hover:text-gray-900 px-3 py-2 flex items-center"><i class="fas fa-user-tie mr-2"></i> Admin</a>
                </div>
            </div>
        </nav>

        <!-- Statistics Content -->
        <div class="max-w-7xl mx-auto px-4 py-6">
            <h1 class="text-3xl font-bold mb-6 text-center">Website Analysis & Statistics</h1>
            <p class="text-sm text-gray-500 text-center mb-6">Generated on: <?php echo $currentDate->format('l, F d, Y, h:i A T'); ?></p>

            <!-- Time Period Selection -->
            <div class="mb-6 text-center">
                <label for="time-period" class="mr-2">Select Time Period:</label>
                <select id="time-period" name="period" class="p-2 border rounded-lg" onchange="window.location.href='stats.php?period='+this.value">
                    <option value="daily" <?php echo $timePeriod === 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="monthly" <?php echo $timePeriod === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="yearly" <?php echo $timePeriod === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                </select>
            </div>

            <!-- Unique Visitors Chart -->
            <div class="bg-white rounded-lg shadow-lg mb-8 p-6">
                <h2 class="text-xl font-semibold mb-4">Unique Visitors (<?php echo ucfirst($timePeriod); ?>)</h2>
                <div class="chart-container">
                    <canvas id="uniqueVisitsChart"></canvas>
                </div>
            </div>

            <!-- Page Visits Chart -->
            <div class="bg-white rounded-lg shadow-lg mb-8 p-6">
                <h2 class="text-xl font-semibold mb-4">Total Visitor Counts by Page (<?php echo ucfirst($timePeriod); ?>)</h2>
                <div class="chart-container">
                    <canvas id="pageVisitsChart"></canvas>
                </div>
                <?php if (empty($pageNames) || empty($visitCounts)): ?>
                    <p class="mt-4 text-sm text-gray-600">No data available for this period.</p>
                <?php else: ?>
                    <table class="mt-4">
                        <thead>
                            <tr>
                                <th>Page Name (Period)</th>
                                <th>Visit Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < count($pageNames); $i++): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pageNames[$i]); ?></td>
                                    <td><?php echo htmlspecialchars($visitCounts[$i]); ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <p class="mt-4 text-sm text-gray-600">Note: Data is aggregated for the last <?php echo $timePeriod === 'daily' ? '24 hours' : ($timePeriod === 'monthly' ? '30 days' : '1 year'); ?>.</p>
            </div>

            <!-- Most Searched Townships -->
            <div class="bg-white rounded-lg shadow-lg mb-8 p-6">
                <h2 class="text-xl font-semibold mb-4">Top 5 Most Searched Townships (<?php echo ucfirst($timePeriod); ?>)</h2>
                <ul class="list-disc pl-5">
                    <?php foreach ($townshipNames as $index => $name): ?>
                        <li class="mb-2"><?php echo htmlspecialchars($name); ?> - <?php echo $townshipSearchCounts[$index]; ?> searches 
                            <a href="TownshipDetail.php?TownshipID=<?php echo $townshipIDs[$index]; ?>" class="text-blue-500 hover:underline ml-2">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Most Searched Bus Numbers -->
            <div class="bg-white rounded-lg shadow-lg mb-8 p-6">
                <h2 class="text-xl font-semibold mb-4">Top 5 Most Searched Bus Numbers (<?php echo ucfirst($timePeriod); ?>)</h2>
                <ul class="list-disc pl-5">
                    <?php foreach ($busNumbers as $index => $number): ?>
                        <li class="mb-2"><?php echo htmlspecialchars($number); ?> - <?php echo $busSearchCounts[$index]; ?> searches 
                            <a href="bus_details.php?BusID=<?php echo $busIDs[$index]; ?>" class="text-blue-500 hover:underline ml-2">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Traffic Sources (Referrers) -->
            <div class="bg-white rounded-lg shadow-lg mb-8 p-6">
                <h2 class="text-xl font-semibold mb-4">Traffic Sources (Top 10)</h2>
                <?php if (empty($referrers)): ?>
                    <p class="mt-4 text-sm text-gray-600">No data available for this period.</p>
                <?php else: ?>
                    <table class="mt-4">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Visits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referrers as $ref): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ref['source']); ?></td>
                                    <td><?php echo htmlspecialchars($ref['visit_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Device Usage Chart -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Device Usage (<?php echo ucfirst($timePeriod); ?>)</h2>
                <div class="chart-container">
                    <canvas id="deviceUsageChart"></canvas>
                </div>
                <?php if (empty($deviceTypes) || empty($deviceUsageCounts)): ?>
                    <p class="mt-4 text-sm text-gray-600">No data available for this period.</p>
                <?php else: ?>
                    <table class="mt-4">
                        <thead>
                            <tr>
                                <th>Device Type</th>
                                <th>Usage Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < count($deviceTypes); $i++): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($deviceTypes[$i]); ?></td>
                                    <td><?php echo htmlspecialchars($deviceUsageCounts[$i]); ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Optional: Add a simple reload on period change (handled by URL change)
        document.getElementById('time-period').addEventListener('change', function() {
            window.location.href = 'stats.php?period=' + this.value;
        });

        // Render Unique Visitors Chart
        const uniqueVisitsCtx = document.getElementById('uniqueVisitsChart').getContext('2d');
        new Chart(uniqueVisitsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($uniquePeriods); ?>,
                datasets: [{
                    label: 'Unique Visitors',
                    data: <?php echo json_encode($uniqueCounts); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Unique Visitors' } },
                    x: { title: { display: true, text: 'Period' } }
                }
            }
        });

        // Render Page Visits Chart
        const pageVisitsCtx = document.getElementById('pageVisitsChart').getContext('2d');
        new Chart(pageVisitsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($pageNames); ?>,
                datasets: [{
                    label: 'Page Visits',
                    data: <?php echo json_encode($visitCounts); ?>,
                    backgroundColor: '#36b5eb',
                    borderColor: '#1f8fc2',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Visit Count', color: '#000000' } },
                    x: { title: { display: true, text: 'Page Name (Period)', color: '#000000' } }
                },
                plugins: { legend: { labels: { color: '#000000' } } }
            }
        });

        // Render Device Usage Chart
        const deviceUsageCtx = document.getElementById('deviceUsageChart').getContext('2d');
        new Chart(deviceUsageCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($deviceTypes); ?>,
                datasets: [{
                    data: <?php echo json_encode($deviceUsageCounts); ?>,
                    backgroundColor: ['#36b5eb', '#ff6384', '#ffcd56', '#4bc0c0', '#9966ff'],
                    borderColor: ['#1f8fc2', '#ff4d6a', '#ffaa33', '#33a3a3', '#663399'],
                    borderWidth: 1
                }]
            },
            options: {
                plugins: { legend: { labels: { color: '#000000' } } }
            }
        });
    </script>
</body>
</html>