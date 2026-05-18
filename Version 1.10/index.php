<?php
session_start();
include('config/database.php');
if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}
include('includes/maintenance_check.php'); 

// Check for active maintenance
checkMaintenance($connect);

// Include centralized analytics tracking
if (file_exists('core/analytics.php')) {
    include_once('core/analytics.php');
}
if (function_exists('logStat')) {
    logStat($connect, 'page_view', 'Home');
}

// Search logic
$searchQuery = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : '';
if ($searchQuery) {
    if (function_exists('logStat')) {
        logStat($connect, 'search', $searchQuery);
    }
}

$query = "
    SELECT 
        township.TownshipID, 
        township.TownshipName, 
        COUNT(gate.GateID) AS TotalGates
    FROM township
    LEFT JOIN gate ON township.TownshipID = gate.TownshipID
    WHERE township.TownshipName LIKE ?
    GROUP BY township.TownshipID
";
$stmt = mysqli_prepare($connect, $query);
$searchParam = "%" . $searchQuery . "%";
mysqli_stmt_bind_param($stmt, "s", $searchParam);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$townships = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $townships[] = $row;
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
    <meta name="description" content="YBS Hub is your ultimate guide for Yangon Bus Service (YBS). Find accurate bus routes, gate details, and township information for public transport in Yangon, Myanmar.">
    <meta name="keywords" content="YBS, Yangon Bus Service, YBS Hub, Yangon Bus, Yangon Public Transport, YBS Routes, Yangon Transit">
    <title>YBS Hub - Yangon Bus Service Guide</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/Logo/YBS_Web_Logo.png">
    <style>
        nav { background-color: white; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        
        .slider { position: relative; height: 50vh; overflow: hidden; border-radius: 0.75rem; z-index: 1; }
        .slider img { position: absolute; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 1s ease-in-out; }
        .slider img.active { opacity: 1; }
        #mobile-menu.open { max-height: 20rem; } 
        @media only screen and (max-width: 450px) { .slider { height: 25vh; } }
    </style>
</head>
<body class="bg-gray-100">
        
    <div id="app">
        <?php include('includes/header.php'); ?>

        <div class="max-w-7xl mx-auto px-4 pt-10 pb-6 md:py-6">
            <div class="hidden md:block mb-8 sticky top-16 z-40 bg-gray-100 py-3 rounded-b-lg shadow-sm border-b border-gray-200">
                <form method="GET" action="index.php">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search by Township Name" class="w-full px-4 py-2 border rounded-lg" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
            <?php if ($searchQuery == ''): ?>
            <div class="slider mb-8">
                <img src="assets/images/Image/thumbnail.jpg" alt="YBS Hub Map 1" class="active">
                <img src="assets/images/Image/thumbnail (2).jpg" alt="YBS Hub Map 2">
                <img src="assets/images/Image/thumbnail (3).jpg" alt="YBS Hub Map 3">
            </div>
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if (!empty($townships)): ?>
                    <?php foreach ($townships as $township): ?>
                        <a href="township_details.php?TownshipID=<?php echo htmlspecialchars($township['TownshipID']); ?>" class="block" onclick="logEvent('click_township', '<?php echo addslashes($township['TownshipName']); ?>', <?php echo $township['TownshipID']; ?>);">
                            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
                                <h3 class="text-lg font-semibold text-center mb-2"><?php echo htmlspecialchars($township['TownshipName']); ?></h3>
                                <p class="text-gray-600 text-center">Total Gates: <?php echo $township['TotalGates']; ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center col-span-full text-gray-600">No results found.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php include('includes/footer.php'); ?>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const images = document.querySelectorAll('.slider img');
                let currentIndex = 0;
                if (images.length > 0) {
                    setInterval(() => {
                        images[currentIndex].classList.remove('active');
                        currentIndex = (currentIndex + 1) % images.length;
                        images[currentIndex].classList.add('active');
                    }, 3000);
                }
            });
            
            // Client-side analytics tracking
            function logEvent(action_type, action_value = '', township_id = null) {
                fetch('api/log_event.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action_type, action_value, township_id })
                }).catch(err => console.error("Analytics Error:", err));
            }
        </script>
    </div>
</body>
</html>