<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="assets/images/Logo/YBS_Web_Logo.svg"> <!-- Updated path -->
    <style>
        #mobile-menu { 
            transition: max-height 0.3s ease-in-out; 
            overflow: hidden; 
        }
    </style>
</head>
<body>
    <?php
    $current_page = strtolower(basename($_SERVER['PHP_SELF']));
    $search_action = '';
    $search_placeholder = '';
    $search_query_name = 'search';
    $search_query_val = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
    $hidden_inputs = '';

    if ($current_page == 'index.php') {
        $search_action = 'index.php';
        $search_placeholder = 'Search by Township Name';
    } elseif ($current_page == 'buslist.php') {
        $search_action = 'BusList.php';
        $search_placeholder = 'Search by Bus Number';
        $search_query_name = 'bus_search';
        $search_query_val = isset($_GET['bus_search']) ? htmlspecialchars($_GET['bus_search']) : '';
    } elseif ($current_page == 'gatelist.php') {
        $search_action = 'GateList.php';
        $search_placeholder = 'Search by Gate Name or Road';
    } elseif ($current_page == 'township_details.php') {
        $search_action = 'township_details.php';
        $search_placeholder = 'Search by Gate Name';
        $t_id = isset($_GET['TownshipID']) ? htmlspecialchars($_GET['TownshipID']) : '';
        $hidden_inputs = '<input type="hidden" name="TownshipID" value="'.$t_id.'">';
    }
    ?>
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div id="main-header-content" class="flex items-center justify-between h-16">
                <div class="flex-shrink-0 flex items-center">
                    <a href="index.php" class="flex items-center">
                        <img src="assets/images/Logo/Icon.svg" alt="YBS Icon" class="h-10 w-10 mr-2">
                        <h1 class="text-2xl font-bold text-gray-800">YBS Hub</h1>
                    </a>
                </div>
                <div class="hidden md:flex space-x-6">
                    <a href="index.php" class="text-gray-600 hover:text-blue-600 flex items-center transition-colors duration-200 px-3 py-2 rounded-md hover:bg-blue-50">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                    <a href="BusList.php" class="text-gray-600 hover:text-blue-600 flex items-center transition-colors duration-200 px-3 py-2 rounded-md hover:bg-blue-50">
                        <i class="fas fa-bus mr-2"></i> Bus
                    </a>
                    <a href="GateList.php" class="text-gray-600 hover:text-blue-600 flex items-center transition-colors duration-200 px-3 py-2 rounded-md hover:bg-blue-50"> 
                        <i class="fas fa-map-marker-alt mr-2"></i> Gate
                    </a>
                    <a href="Destination.php" class="text-gray-600 hover:text-blue-600 flex items-center transition-colors duration-200 px-3 py-2 rounded-md hover:bg-blue-50">
                        <i class="fas fa-route mr-2"></i> Destination
                    </a>
                    <a href="About_Us.php" class="text-gray-600 hover:text-blue-600 flex items-center transition-colors duration-200 px-3 py-2 rounded-md hover:bg-blue-50">
                        <i class="fas fa-info-circle mr-2"></i> About Us
                    </a>
                </div>
                <div class="flex items-center md:hidden space-x-3">
                    <?php if ($search_action !== ''): ?>
                    <button id="mobile-search-button" class="p-2 text-gray-600 hover:text-gray-800 focus:outline-none">
                        <i class="fas fa-search text-xl"></i>
                    </button>
                    <?php endif; ?>
                    <button id="mobile-menu-button" class="p-2 text-gray-600 hover:text-gray-800 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Search Overlay -->
            <?php if ($search_action !== ''): ?>
            <div id="mobile-search-overlay" class="hidden absolute top-full left-0 w-full bg-white z-40 flex flex-row flex-nowrap items-center justify-between px-4 py-3 shadow-md border-t border-gray-100 md:hidden">
                <form method="GET" action="<?php echo $search_action; ?>" class="flex-1 flex flex-row items-center bg-gray-100 rounded-full px-4 py-2 m-0 min-w-0">
                    <?php echo $hidden_inputs; ?>
                    <input type="text" name="<?php echo $search_query_name; ?>" placeholder="<?php echo $search_placeholder; ?>" class="w-full bg-transparent focus:outline-none text-gray-800 min-w-0" value="<?php echo $search_query_val; ?>">
                    <button type="submit" class="ml-2 text-gray-500 flex-shrink-0">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <button type="button" id="close-search-button" class="flex-shrink-0 ml-3 text-gray-500 hover:text-gray-800 focus:outline-none bg-gray-200 rounded-full w-10 h-10 flex items-center justify-center m-0">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endif; ?>

            <div id="mobile-menu" class="md:hidden bg-white shadow-lg absolute left-0 right-0 max-h-0">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="index.php" class="block text-gray-600 hover:text-blue-600 px-3 py-2 flex items-center hover:bg-blue-50 rounded-md transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                    <a href="BusList.php" class="block text-gray-600 hover:text-blue-600 px-3 py-2 flex items-center hover:bg-blue-50 rounded-md transition-colors duration-200">
                        <i class="fas fa-bus mr-2"></i> Bus
                    </a>
                    <a href="GateList.php" class="block text-gray-600 hover:text-blue-600 px-3 py-2 flex items-center hover:bg-blue-50 rounded-md transition-colors duration-200">
                        <i class="fas fa-map-marker-alt mr-2"></i> Gate
                    </a>
                    <a href="Destination.php" class="block text-gray-600 hover:text-blue-600 px-3 py-2 flex items-center hover:bg-blue-50 rounded-md transition-colors duration-200">
                        <i class="fas fa-route mr-2"></i> Destination 
                    </a>
                    <a href="About_Us.php" class="block text-gray-600 hover:text-blue-600 px-3 py-2 flex items-center hover:bg-blue-50 rounded-md transition-colors duration-200">
                        <i class="fas fa-info-circle mr-2"></i> About Us
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const menuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const searchButton = document.getElementById('mobile-search-button');
            const closeSearchButton = document.getElementById('close-search-button');
            const searchOverlay = document.getElementById('mobile-search-overlay');
            const searchInput = searchOverlay ? searchOverlay.querySelector('input[type="text"]') : null;

            if (searchButton && searchOverlay && closeSearchButton) {
                searchButton.addEventListener("click", () => {
                    // Close mobile menu if it's open
                    if (mobileMenu && !mobileMenu.classList.contains('max-h-0')) {
                        mobileMenu.classList.remove('max-h-96');
                        mobileMenu.classList.add('max-h-0');
                    }
                    
                    searchOverlay.classList.remove('hidden');
                    if (searchInput) {
                        setTimeout(() => searchInput.focus(), 100);
                    }
                });

                closeSearchButton.addEventListener("click", () => {
                    searchOverlay.classList.add('hidden');
                });
            }

            if (menuButton && mobileMenu) {
                menuButton.addEventListener("click", () => {
                    // Close search overlay if it's open
                    if (searchOverlay && !searchOverlay.classList.contains('hidden')) {
                        searchOverlay.classList.add('hidden');
                    }

                    if (mobileMenu.classList.contains('max-h-0')) {
                        mobileMenu.classList.remove('max-h-0');
                        mobileMenu.classList.add('max-h-96');
                    } else {
                        mobileMenu.classList.remove('max-h-96');
                        mobileMenu.classList.add('max-h-0');
                    }
                });

                // Close menu when clicking outside
                document.addEventListener("click", (event) => {
                    if (!menuButton.contains(event.target) && !mobileMenu.contains(event.target)) {
                        mobileMenu.classList.remove('max-h-96');
                        mobileMenu.classList.add('max-h-0');
                    }
                });
            }
        });
    </script>
    <?php include_once(__DIR__ . '/popup_ads.php'); ?>
</body>
</html>