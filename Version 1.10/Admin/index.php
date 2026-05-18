<?php
// Start session for admin access
session_start();
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don't have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

include('../config/database.php');
include('logout_function.php');

// Ensure the connection is established
if (!isset($connect) || !$connect) {
    die("Database connection not established. Please check 'connect.php'.");
}

// Fetch the logged-in admin's name
$adminName = $_SESSION['AdminName'] ?? 'Admin';

// Fetch dashboard data
try {
    // Total Routes
    $queryRoutes = "SELECT COUNT(*) AS totalRoutes FROM route";
    $resultRoutes = mysqli_query($connect, $queryRoutes);
    $totalRoutes = mysqli_fetch_assoc($resultRoutes)['totalRoutes'] ?? 0;

    // Total Buses
    $queryBuses = "SELECT COUNT(*) AS totalBuses FROM bus";
    $resultBuses = mysqli_query($connect, $queryBuses);
    $totalBuses = mysqli_fetch_assoc($resultBuses)['totalBuses'] ?? 0;

    // Total Townships
    $queryTownships = "SELECT COUNT(*) AS totalTownships FROM township";
    $resultTownships = mysqli_query($connect, $queryTownships);
    $totalTownships = mysqli_fetch_assoc($resultTownships)['totalTownships'] ?? 0;

    // Total Admins
    $queryAdmins = "SELECT COUNT(*) AS totalAdmins FROM admin";
    $resultAdmins = mysqli_query($connect, $queryAdmins);
    $totalAdmins = mysqli_fetch_assoc($resultAdmins)['totalAdmins'] ?? 0;
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-home-container {
            font-family: 'Outfit', sans-serif;
        }
        
        /* Animated Gradient Background - Light Mode */
        .admin-bg {
            background: linear-gradient(-45deg, #f8fafc, #e2e8f0, #f8fafc, #cbd5e1);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }
        
        /* Animated Gradient Background - Dark Mode */

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Glassmorphism Panels */
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
            border-radius: 1.5rem;
        }

        /* Hover animations */
        .hover-lift {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .hover-lift:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            border-color: rgba(96, 165, 250, 0.4);
        }

        /* Icon Wrapper Glow */
        .icon-glow {
            transition: all 0.3s ease;
        }
        .hover-lift:hover .icon-glow {
            transform: scale(1.15) rotate(5deg);
            filter: drop-shadow(0 0 12px currentColor);
        }

        /* Sleek Button Styles */
        .btn-sleek {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            color: #334155;
        }
        
        .btn-sleek::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.05), transparent);
            transition: all 0.5s ease;
        }
        
        .btn-sleek:hover::before {
            left: 100%;
        }
        .btn-sleek:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 0, 0, 0.1);
        }

        /* Color-specific glow effects */
        .glow-blue { color: #3b82f6; }
        .glow-green { color: #22c55e; }
        .glow-purple { color: #a855f7; }
        .glow-red { color: #ef4444; }
        .glow-yellow { color: #eab308; }
        .glow-indigo { color: #6366f1; }

        .text-gradient {
            background: linear-gradient(to right, #3b82f6, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="admin-bg flex flex-col min-h-screen transition-colors duration-300">
    <?php include('../includes/admheader.php'); ?>
    
    <main class="flex-grow pt-28 pb-12 px-4 sm:px-6 lg:px-8 admin-home-container">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <!-- Hero / Welcome Section -->
            <div class="glass-panel p-8 md:p-12 relative overflow-hidden flex flex-col md:flex-row items-center justify-between">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse" style="animation-delay: 2s;"></div>
                
                <div class="relative z-10 text-center md:text-left mb-6 md:mb-0">
                    <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-4 tracking-tight">
                        Welcome back, <br/><span class="text-gradient"><?php echo htmlspecialchars($adminName); ?></span>
                    </h1>
                    <p class="text-lg text-slate-600 max-w-xl font-light">
                        Monitor system performance, manage transit routes, and oversee operations from your central command dashboard.
                    </p>
                </div>
                
                <div class="relative z-10 flex space-x-4">
                    <a href="dashboard.php" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-2xl shadow-[0_0_20px_rgba(79,70,229,0.4)] transition-all transform hover:scale-105 flex items-center">
                        <i class="fas fa-chart-pie mr-3 text-xl"></i> Full Analytics
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Routes -->
                <div class="glass-panel p-6 hover-lift flex items-center justify-between group cursor-default">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1 uppercase tracking-wider">Total Routes</p>
                        <h3 class="text-4xl font-bold text-slate-900"><?php echo $totalRoutes; ?></h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-500/10 flex items-center justify-center border border-blue-500/20">
                        <i class="fas fa-road text-2xl glow-blue icon-glow"></i>
                    </div>
                </div>
                <!-- Buses -->
                <div class="glass-panel p-6 hover-lift flex items-center justify-between group cursor-default">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1 uppercase tracking-wider">Total Buses</p>
                        <h3 class="text-4xl font-bold text-slate-900"><?php echo $totalBuses; ?></h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-green-500/10 flex items-center justify-center border border-green-500/20">
                        <i class="fas fa-bus text-2xl glow-green icon-glow"></i>
                    </div>
                </div>
                <!-- Townships -->
                <div class="glass-panel p-6 hover-lift flex items-center justify-between group cursor-default">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1 uppercase tracking-wider">Townships</p>
                        <h3 class="text-4xl font-bold text-slate-900"><?php echo $totalTownships; ?></h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-purple-500/10 flex items-center justify-center border border-purple-500/20">
                        <i class="fas fa-city text-2xl glow-purple icon-glow"></i>
                    </div>
                </div>
                <!-- Admins -->
                <div class="glass-panel p-6 hover-lift flex items-center justify-between group cursor-default">
                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-1 uppercase tracking-wider">System Admins</p>
                        <h3 class="text-4xl font-bold text-slate-900"><?php echo $totalAdmins; ?></h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-red-500/10 flex items-center justify-center border border-red-500/20">
                        <i class="fas fa-user-shield text-2xl glow-red icon-glow"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-panel p-8">
                <h2 class="text-2xl font-bold text-slate-800 mb-6 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-3"></i> Quick Operations
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <a href="RouteEntry.php" class="btn-sleek p-4 flex flex-col items-center justify-center text-center group">
                        <i class="fas fa-route text-2xl mb-3 glow-blue group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium">Routes</span>
                    </a>
                    <a href="BusEntry.php" class="btn-sleek p-4 flex flex-col items-center justify-center text-center group">
                        <i class="fas fa-bus-alt text-2xl mb-3 glow-green group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium">Buses</span>
                    </a>
                    <a href="admin_mgm.php" class="btn-sleek p-4 flex flex-col items-center justify-center text-center group">
                        <i class="fas fa-users-cog text-2xl mb-3 glow-red group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium">Admins</span>
                    </a>
                    <a href="../stats.php" class="btn-sleek p-4 flex flex-col items-center justify-center text-center group">
                        <i class="fas fa-chart-line text-2xl mb-3 glow-purple group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium">Analytics</span>
                    </a>
                    <a href="feedbackcheck.php" class="btn-sleek p-4 flex flex-col items-center justify-center text-center group">
                        <i class="fas fa-comment-dots text-2xl mb-3 glow-yellow group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium">Feedback</span>
                    </a>
                    <a href="admin_maintenance.php" class="btn-sleek p-4 flex flex-col items-center justify-center text-center group">
                        <i class="fas fa-tools text-2xl mb-3 glow-indigo group-hover:scale-110 transition-transform"></i>
                        <span class="text-sm font-medium">Maintenance</span>
                    </a>
                </div>
            </div>

        </div>
    </main>
    
    <?php include('../includes/admfooter.php'); ?>
</body>
</html>