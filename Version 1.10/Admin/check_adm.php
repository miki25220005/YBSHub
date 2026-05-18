<?php
session_start();
include('../config/database.php');
include('../includes/admheader.php');

if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

if (!isset($_GET['AdminID'])) {
    echo "<script>window.alert('Invalid Admin ID.')</script>";
    echo "<script>window.location='admin_mgm.php';</script>";
    exit();
}

$adminID = intval($_GET['AdminID']);
$queryAdmin = "SELECT AdminName FROM admin WHERE AdminID = $adminID";
$resultAdmin = mysqli_query($connect, $queryAdmin);

if (!$resultAdmin || mysqli_num_rows($resultAdmin) === 0) {
    echo "<script>window.alert('Admin not found.')</script>";
    echo "<script>window.location='admin_mgm.php';</script>";
    exit();
}

$adminName = mysqli_fetch_assoc($resultAdmin)['AdminName'];

$queryRoutes = "
    SELECT route.RouteID, bus.BusNo
    FROM route
    LEFT JOIN bus ON route.BusID = bus.BusID
    WHERE route.AdminID = $adminID
";
$resultRoutes = mysqli_query($connect, $queryRoutes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routes by <?php echo htmlspecialchars($adminName); ?> - Yangon Bus Service</title>
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
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900 flex flex-col min-h-screen selection:bg-indigo-200 selection:text-indigo-900">

    <main class="flex-grow pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto space-y-8 animate-fade-in-up">
            
            <!-- Page Header -->
            <div class="text-center space-y-3">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-indigo-100 to-blue-200 border border-white text-indigo-700 font-bold text-2xl shadow-md mb-2">
                    <?php echo strtoupper(substr(htmlspecialchars($adminName), 0, 1)); ?>
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 drop-shadow-sm">
                    Routes by <?php echo htmlspecialchars($adminName); ?>
                </h1>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                    A complete list of all routes assigned or created by this administrator.
                </p>
            </div>

            <!-- Routes Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 transform transition-all hover:shadow-2xl duration-300">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                        <div class="bg-indigo-100 p-2 rounded-lg text-indigo-600 shadow-sm">
                            <i class="fas fa-route"></i>
                        </div>
                        Assigned Routes
                    </h2>
                    <span class="bg-indigo-50 border border-indigo-100 text-indigo-700 py-1 px-3 rounded-full text-xs font-bold tracking-wide shadow-sm">
                        <?php echo mysqli_num_rows($resultRoutes); ?> Routes
                    </span>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Route ID</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bus Number</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <?php if (mysqli_num_rows($resultRoutes) > 0): ?>
                                <?php while ($route = mysqli_fetch_assoc($resultRoutes)): ?>
                                    <tr class="hover:bg-indigo-50/40 transition-colors duration-200 group">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-gray-100 p-2 rounded text-gray-400 group-hover:bg-indigo-100 group-hover:text-indigo-500 transition-colors">
                                                    <i class="fas fa-map-signs"></i>
                                                </div>
                                                <span class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                                    <?php echo htmlspecialchars($route['RouteID']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="bg-gray-100 p-2 rounded text-gray-400 group-hover:bg-blue-100 group-hover:text-blue-500 transition-colors">
                                                    <i class="fas fa-bus"></i>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700">
                                                    <?php echo htmlspecialchars($route['BusNo']); ?>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="px-6 py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                            <i class="fas fa-route text-2xl text-gray-400"></i>
                                        </div>
                                        <p class="text-gray-600 text-base font-semibold">No routes found.</p>
                                        <p class="text-gray-400 text-sm mt-1">This admin hasn't added any routes yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Back Button -->
            <div class="text-center pt-4">
                <a href="admin_mgm.php" class="inline-flex items-center px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Admin Management
                </a>
            </div>
            
        </div>
    </main>

    <!-- Include Footer -->
    <?php include('../includes/admfooter.php'); ?>

</body>
</html>

