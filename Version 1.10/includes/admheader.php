<?php
// Auto backup trigger (Once per day)
$todayDate = date('Y-m-d');
$autoBackupDir = __DIR__ . '/backups/auto/';
$backupExists = false;

if (is_dir($autoBackupDir)) {
    $files = glob($autoBackupDir . "*_backup_{$todayDate}*.sql");
    if (!empty($files)) {
        $backupExists = true;
    }
}

if (!$backupExists) {
    // We haven't backed up today. Let's do it quietly.
    $isAutoBackupTriggered = true;
    $_GET['action'] = 'auto';
    ob_start(); // Prevent any accidental output
    include(__DIR__ . '/db_backup.php');
    ob_end_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yangon Bus Service</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Gradient background for header */
        header {
            background: linear-gradient(90deg, #1f2937 0%, #374151 100%);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }
        /* Hover effect for nav links */
        nav a {
            transition: all 0.3s ease;
        }
        nav a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
        /* Mobile menu animation */
        #mobile-menu {
            transform: translateY(-100%);
            transition: transform 0.3s ease-in-out;
        }
        #mobile-menu.show {
            transform: translateY(0);
        }
        /* Focus styles for accessibility */
        nav a:focus {
            outline: 2px solid #60a5fa;
            outline-offset: 2px;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <header class="text-white shadow-2xl fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo Section -->
                <div class="flex items-center space-x-3">
                    <img src="../assets/images/BusLogo.png" alt="Logo" class="w-12 h-12 bg-white rounded-full p-1 shadow-md transform hover:scale-105 transition duration-300">
                    <span class="text-xl font-extrabold tracking-tight hidden md:block bg-clip-text text-transparent bg-gradient-to-r from-blue-300 to-blue-500">Yangon Bus Service</span>
                </div>

                <!-- Hamburger Menu Button (Mobile) -->
                <button id="mobile-menu-btn" class="md:hidden text-2xl focus:outline-none hover:text-blue-300 transition duration-200 focus:ring-2 focus:ring-blue-300 rounded">
                    <i class="fas fa-bars" id="menu-icon"></i>
                </button>

                <!-- Navigation Items (Desktop) -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="px-4 py-2 rounded-lg bg-gray-800/50 hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center space-x-2 font-medium">
                        <i class="fas fa-home"></i><span>Home</span>
                    </a>
                    <a href="GateEntry.php" class="px-4 py-2 rounded-lg bg-gray-800/50 hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center space-x-2 font-medium">
                        <i class="fas fa-map-marker-alt"></i><span>Bus Gates</span>
                    </a>
                    <a href="TownshipEntry.php" class="px-4 py-2 rounded-lg bg-gray-800/50 hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center space-x-2 font-medium">
                        <i class="fas fa-city"></i><span>Township</span>
                    </a>
                    <a href="BusEntry.php" class="px-4 py-2 rounded-lg bg-gray-800/50 hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center space-x-2 font-medium">
                        <i class="fas fa-bus"></i><span>Buses</span>
                    </a>
                    <a href="AdManager.php" class="px-4 py-2 rounded-lg bg-gray-800/50 hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center space-x-2 font-medium">
                        <i class="fas fa-ad"></i><span>Ad Manager</span>
                    </a>
                    <a href="adminprofile.php" class="px-4 py-2 rounded-lg bg-gray-800/50 hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center space-x-2 font-medium">
                        <i class="fas fa-user"></i><span>Profile</span>
                    </a>
                    <a href="logout.php" class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-700 rounded-lg hover:from-red-600 hover:to-red-800 transition duration-200 flex items-center space-x-2 font-medium shadow-md">
                        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                    </a>
                </nav>
            </div>

            <!-- Mobile Menu (Hidden by Default) -->
            <div id="mobile-menu" class="md:hidden bg-gray-800 text-white absolute left-0 right-0 top-16 shadow-lg hidden">
                <nav class="flex flex-col items-center space-y-2 py-4">
                    <a href="index.php" class="w-3/4 text-center px-4 py-2 bg-gray-700/50 rounded-lg hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-home"></i><span>Home</span>
                    </a>
                    <a href="GateEntry.php" class="w-3/4 text-center px-4 py-2 bg-gray-700/50 rounded-lg hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-map-marker-alt"></i><span>Bus Gates</span>
                    </a>
                    <a href="TownshipEntry.php" class="w-3/4 text-center px-4 py-2 bg-gray-700/50 rounded-lg hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-city"></i><span>Township</span>
                    </a>
                    <a href="BusEntry.php" class="w-3/4 text-center px-4 py-2 bg-gray-700/50 rounded-lg hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-bus"></i><span>Buses</span>
                    </a>
                    <a href="AdManager.php" class="w-3/4 text-center px-4 py-2 bg-gray-700/50 rounded-lg hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-ad"></i><span>Ad Manager</span>
                    </a>
                    <a href="adminprofile.php" class="w-3/4 text-center px-4 py-2 bg-gray-700/50 rounded-lg hover:bg-gray-600 hover:text-blue-200 transition duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-user"></i><span>Profile</span>
                    </a>
                    <a href="logout.php" class="w-3/4 text-center px-4 py-2 bg-gradient-to-r from-red-500 to-red-700 rounded-lg hover:from-red-600 hover:to-red-800 transition duration-200 flex items-center justify-center space-x-2 shadow-md">
                        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const menuIcon = document.getElementById('menu-icon');

            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
                mobileMenu.classList.toggle('show');
                // Toggle between hamburger and close icon
                menuIcon.classList.toggle('fa-bars');
                menuIcon.classList.toggle('fa-times');
            });

            // Close menu when clicking outside
            document.addEventListener('click', (event) => {
                if (!menuBtn.contains(event.target) && !mobileMenu.contains(event.target)) {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.classList.remove('show');
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            });

            // Close menu when a link is clicked
            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.classList.remove('show');
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                });
            });
        });
    </script>
</body>
</html>