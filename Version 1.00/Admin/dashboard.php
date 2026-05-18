<?php
// Start session for admin access
session_start();
include('../config/database.php');
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Ensure connection is established
if (!isset($connect) || !$connect) {
    die("Database connection not established. Please check 'connect.php'.");
}

// Fetch detailed statistics
try {
    // Total Routes
    $queryRoutes = "SELECT COUNT(*) AS totalRoutes FROM route";
    $totalRoutes = mysqli_fetch_assoc(mysqli_query($connect, $queryRoutes))['totalRoutes'] ?? 0;

    // Total Buses
    $queryBuses = "SELECT COUNT(*) AS totalBuses FROM bus";
    $totalBuses = mysqli_fetch_assoc(mysqli_query($connect, $queryBuses))['totalBuses'] ?? 0;

    // Total Townships
    $queryTownships = "SELECT COUNT(*) AS totalTownships FROM township";
    $totalTownships = mysqli_fetch_assoc(mysqli_query($connect, $queryTownships))['totalTownships'] ?? 0;

    // Total Admins
    $queryAdmins = "SELECT COUNT(*) AS totalAdmins FROM admin";
    $totalAdmins = mysqli_fetch_assoc(mysqli_query($connect, $queryAdmins))['totalAdmins'] ?? 0;

    // Total Gates
    $queryGates = "SELECT COUNT(*) AS totalGates FROM gate";
    $totalGates = mysqli_fetch_assoc(mysqli_query($connect, $queryGates))['totalGates'] ?? 0;

    // Buses with incomplete routes (no routes or missing one of Forward/Reverse, excluding Single)
    $queryBusesWithIncompleteRoutes = "
        SELECT bus.BusID, bus.BusNo
        FROM bus
        LEFT JOIN route ON bus.BusID = route.BusID
        GROUP BY bus.BusID, bus.BusNo
        HAVING COUNT(CASE WHEN route.Direction = 'Single' THEN 1 END) = 0
            AND (COUNT(CASE WHEN route.Direction IN ('Forward', 'Reverse') THEN 1 END) = 0
                OR COUNT(CASE WHEN route.Direction IN ('Forward', 'Reverse') THEN 1 END) = 1)
    ";
    $busesWithIncompleteRoutes = mysqli_query($connect, $queryBusesWithIncompleteRoutes);
} catch (Exception $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Yangon Bus Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Custom scrollbar for tables */
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900 flex flex-col min-h-screen selection:bg-indigo-200 selection:text-indigo-900">
    <?php include('../includes/admheader.php'); ?>
    
    <main class="flex-grow pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-8 animate-fade-in-up">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 drop-shadow-sm">
                        Dashboard Overview
                    </h1>
                    <p class="text-gray-500 mt-2 text-lg">Welcome back, monitor your system stats and operations.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 bg-white px-5 py-3 rounded-xl shadow-sm border border-gray-100">
                        <i class="fas fa-calendar-alt text-indigo-500"></i>
                        <span class="text-gray-700 font-bold"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <a href="db_backup.php?action=manual" target="_blank" class="flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-5 py-3 rounded-xl shadow-md transition-all duration-300 transform hover:-translate-y-1">
                        <i class="fas fa-download"></i>
                        <span class="font-bold">Manual Backup</span>
                    </a>
                </div>
            </div>
            
            <!-- Overview Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                <?php
                    $stats = [
                        ['title' => 'Total Routes', 'count' => $totalRoutes, 'icon' => 'fa-route', 'color' => 'blue', 'gradient' => 'from-blue-500 to-blue-600', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
                        ['title' => 'Total Buses', 'count' => $totalBuses, 'icon' => 'fa-bus', 'color' => 'indigo', 'gradient' => 'from-indigo-500 to-indigo-600', 'bg' => 'bg-indigo-50', 'text' => 'text-indigo-600'],
                        ['title' => 'Townships', 'count' => $totalTownships, 'icon' => 'fa-city', 'color' => 'purple', 'gradient' => 'from-purple-500 to-purple-600', 'bg' => 'bg-purple-50', 'text' => 'text-purple-600'],
                        ['title' => 'Total Gates', 'count' => $totalGates, 'icon' => 'fa-door-open', 'color' => 'emerald', 'gradient' => 'from-emerald-500 to-emerald-600', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
                        ['title' => 'Administrators', 'count' => $totalAdmins, 'icon' => 'fa-user-shield', 'color' => 'rose', 'gradient' => 'from-rose-500 to-rose-600', 'bg' => 'bg-rose-50', 'text' => 'text-rose-600']
                    ];
                    
                    $delay = 0;
                    foreach ($stats as $stat) {
                        echo "<div class='stat-card bg-white p-6 rounded-2xl shadow-md border border-gray-100 relative overflow-hidden group' style='animation-delay: {$delay}ms;'>";
                        echo "<div class='absolute right-0 top-0 w-24 h-24 bg-gradient-to-br {$stat['gradient']} opacity-10 rounded-bl-full z-0 group-hover:scale-110 transition-transform duration-500'></div>";
                        echo "<div class='relative z-10 flex flex-col h-full justify-between'>";
                        echo "<div class='flex justify-between items-start mb-4'>";
                        echo "<div class='p-3 rounded-xl {$stat['bg']} {$stat['text']} shadow-sm'>";
                        echo "<i class='fas {$stat['icon']} text-xl'></i>";
                        echo "</div>";
                        echo "</div>";
                        echo "<div>";
                        echo "<h3 class='text-sm font-bold text-gray-500 uppercase tracking-wider mb-1'>{$stat['title']}</h3>";
                        echo "<p class='text-3xl font-extrabold text-gray-800'>{$stat['count']}</p>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                        $delay += 100;
                    }
                ?>
            </div>
            
            <!-- Buses With Incomplete Routes Report Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 transform transition-all duration-300 mt-10">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                            <div class="bg-amber-100 p-2 rounded-lg text-amber-600 shadow-sm">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            Action Required: Incomplete Routes
                        </h2>
                        <p class="text-sm text-gray-500 mt-1 sm:ml-11">Buses missing either Forward or Reverse directions.</p>
                    </div>
                    <?php if (mysqli_num_rows($busesWithIncompleteRoutes) > 0): ?>
                        <span class="bg-red-100 text-red-700 py-1.5 px-4 rounded-full text-sm font-bold tracking-wide shadow-sm flex items-center gap-2 self-start sm:self-auto">
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                            <?php echo mysqli_num_rows($busesWithIncompleteRoutes); ?> Issues
                        </span>
                    <?php else: ?>
                        <span class="bg-emerald-100 text-emerald-700 py-1.5 px-4 rounded-full text-sm font-bold tracking-wide shadow-sm flex items-center gap-2 self-start sm:self-auto">
                            <i class="fas fa-check-circle"></i> All Clear
                        </span>
                    <?php endif; ?>
                </div>

                <div class="p-0">
                    <?php if (mysqli_num_rows($busesWithIncompleteRoutes) > 0): ?>
                        <!-- Table Layout for Desktop -->
                        <div class="hidden md:block overflow-x-auto custom-scrollbar">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50/80">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bus Details</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Current Directions</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-50">
                                    <?php 
                                    mysqli_data_seek($busesWithIncompleteRoutes, 0);
                                    while ($row = mysqli_fetch_assoc($busesWithIncompleteRoutes)) :
                                        $busID = $row['BusID'];
                                        $checkRoutesQuery = "SELECT Direction FROM route WHERE BusID = ? GROUP BY Direction";
                                        $stmt = mysqli_prepare($connect, $checkRoutesQuery);
                                        mysqli_stmt_bind_param($stmt, "s", $busID);
                                        mysqli_stmt_execute($stmt);
                                        $existingDirections = mysqli_stmt_get_result($stmt);
                                        $directions = [];
                                        while ($dir = mysqli_fetch_assoc($existingDirections)) {
                                            $directions[] = $dir['Direction'];
                                        }
                                        mysqli_stmt_close($stmt);

                                        $showAssignLink = empty($directions) || (count($directions) == 1 && !in_array('Single', $directions));
                                        $suggestedDirection = '';
                                        if (empty($directions)) {
                                            $suggestedDirection = 'Forward';
                                        } elseif (count($directions) == 1 && $directions[0] === 'Forward') {
                                            $suggestedDirection = 'Reverse';
                                        } elseif (count($directions) == 1 && $directions[0] === 'Reverse') {
                                            $suggestedDirection = 'Forward';
                                        }
                                        ?>
                                        <tr class="hover:bg-amber-50/30 transition-colors duration-200 group">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-gray-100 rounded-lg text-gray-500 group-hover:bg-amber-100 group-hover:text-amber-600 transition-colors">
                                                        <i class="fas fa-bus"></i>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-bold text-gray-900 group-hover:text-amber-600 transition-colors">Bus <?php echo htmlspecialchars($row['BusNo']); ?></div>
                                                        <div class="text-xs font-medium text-gray-500">ID: <?php echo htmlspecialchars($row['BusID']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if (empty($directions)): ?>
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-red-50 text-red-700 border border-red-100 shadow-sm">None</span>
                                                <?php else: ?>
                                                    <div class="flex gap-2">
                                                        <?php foreach ($directions as $dir): ?>
                                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-100 shadow-sm">
                                                                <?php echo htmlspecialchars($dir); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <?php if ($showAssignLink): ?>
                                                    <a href="RouteEntry.php?BusID=<?php echo urlencode($row['BusID']); ?>&BusNo=<?php echo urlencode($row['BusNo']); ?>&SuggestedDirection=<?php echo urlencode($suggestedDirection); ?>" 
                                                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg text-sm font-bold hover:from-emerald-600 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                                                        <i class="fas fa-plus mr-2"></i> Assign <?php echo htmlspecialchars($suggestedDirection); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-3 py-1 text-sm font-bold text-gray-500 bg-gray-100 rounded-lg">
                                                        <i class="fas fa-check-circle mr-1.5 text-emerald-500"></i> Complete
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Card Layout for Mobile -->
                        <div class="block md:hidden px-4 py-4 space-y-4 bg-gray-50/50">
                            <?php 
                            mysqli_data_seek($busesWithIncompleteRoutes, 0);
                            while ($row = mysqli_fetch_assoc($busesWithIncompleteRoutes)) :
                                $busID = $row['BusID'];
                                $checkRoutesQuery = "SELECT Direction FROM route WHERE BusID = ? GROUP BY Direction";
                                $stmt = mysqli_prepare($connect, $checkRoutesQuery);
                                mysqli_stmt_bind_param($stmt, "s", $busID);
                                mysqli_stmt_execute($stmt);
                                $existingDirections = mysqli_stmt_get_result($stmt);
                                $directions = [];
                                while ($dir = mysqli_fetch_assoc($existingDirections)) {
                                    $directions[] = $dir['Direction'];
                                }
                                mysqli_stmt_close($stmt);

                                $showAssignLink = empty($directions) || (count($directions) == 1 && !in_array('Single', $directions));
                                $suggestedDirection = '';
                                if (empty($directions)) {
                                    $suggestedDirection = 'Forward';
                                } elseif (count($directions) == 1 && $directions[0] === 'Forward') {
                                    $suggestedDirection = 'Reverse';
                                } elseif (count($directions) == 1 && $directions[0] === 'Reverse') {
                                    $suggestedDirection = 'Forward';
                                }
                                ?>
                                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                                                <i class="fas fa-bus"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-900">Bus <?php echo htmlspecialchars($row['BusNo']); ?></h3>
                                                <p class="text-xs font-medium text-gray-500">ID: <?php echo htmlspecialchars($row['BusID']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1.5">Current Routes:</span>
                                        <?php if (empty($directions)): ?>
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded bg-red-50 text-red-700 border border-red-100 shadow-sm">None</span>
                                        <?php else: ?>
                                            <div class="flex flex-wrap gap-1.5">
                                                <?php foreach ($directions as $dir): ?>
                                                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded bg-blue-50 text-blue-700 border border-blue-100 shadow-sm">
                                                        <?php echo htmlspecialchars($dir); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="pt-3 border-t border-gray-100">
                                        <?php if ($showAssignLink): ?>
                                            <a href="RouteEntry.php?BusID=<?php echo urlencode($row['BusID']); ?>&BusNo=<?php echo urlencode($row['BusNo']); ?>&SuggestedDirection=<?php echo urlencode($suggestedDirection); ?>" 
                                               class="w-full flex justify-center items-center px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all">
                                                <i class="fas fa-plus mr-2"></i> Assign <?php echo htmlspecialchars($suggestedDirection); ?>
                                            </a>
                                        <?php else: ?>
                                            <div class="w-full text-center px-4 py-2.5 bg-gray-50 text-gray-500 rounded-lg text-sm font-bold">
                                                <i class="fas fa-check-circle mr-1.5 text-emerald-500"></i> Complete
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-50 mb-4 border-4 border-white shadow-sm">
                                <i class="fas fa-check-circle text-4xl text-emerald-500"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">All Systems Normal</h3>
                            <p class="text-gray-500 text-base">All buses have complete forward and reverse routes assigned.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </main>

    <?php include('../includes/admfooter.php'); ?>
</body>
</html>