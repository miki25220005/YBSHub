<?php
session_start();
include('../config/database.php');

if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

if (!isset($_GET['RouteID'])) {
    echo "<script>window.alert('Invalid Route ID')</script>";
    echo "<script>window.location='RouteListAdmin.php';</script>";
    exit();
}

$RouteID = mysqli_real_escape_string($connect, $_GET['RouteID']);

// Fetch route details (Including Direction)
$query = "
    SELECT route.RouteID, route.Direction, bus.BusNo, bus.BusID, admin.AdminName
    FROM route
    LEFT JOIN bus ON route.BusID = bus.BusID
    LEFT JOIN admin ON route.AdminID = admin.AdminID
    WHERE route.RouteID = '$RouteID'
";
$routeResult = mysqli_query($connect, $query);

if (!$routeResult || mysqli_num_rows($routeResult) == 0) {
    echo "<script>window.alert('Route not found.')</script>";
    echo "<script>window.location='RouteListAdmin.php';</script>";
    exit();
}

$routeData = mysqli_fetch_assoc($routeResult);

// Fetch gate list for the route
$gateQuery = "
    SELECT gate.GateID, gate.GateName, gate.Latitude, gate.Longitude
    FROM route_gate
    LEFT JOIN gate ON route_gate.GateID = gate.GateID
    WHERE route_gate.RouteID = '$RouteID'
";
$gateResult = mysqli_query($connect, $gateQuery);

$gateData = [];
while ($row = mysqli_fetch_assoc($gateResult)) {
    $gateData[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Route Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 40px -5px rgba(0,0,0,0.05), 0 10px 20px -5px rgba(0,0,0,0.02);
        }
        .info-card {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -5px rgba(0,0,0,0.08);
            border-color: #e2e8f0;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        #map { 
            height: 500px; 
            width: 100%; 
            border-radius: 1rem; 
            z-index: 10;
        }
        .action-btn {
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 flex flex-col min-h-screen text-slate-800 font-sans antialiased selection:bg-indigo-200 selection:text-indigo-900">
    <?php include('../includes/admheader.php'); ?>

    <main class="flex-grow pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto glass-card rounded-3xl p-6 sm:p-10 animate-fade-in-up">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 border-b border-slate-100 pb-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="RouteListAdmin.php" class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 flex items-center justify-center transition-colors shadow-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500 tracking-tight">Route Details</h1>
                    </div>
                    <p class="text-slate-500 font-medium ml-2 sm:ml-13 pl-10 sm:pl-3">Comprehensive overview of the selected route's configuration and map.</p>
                </div>
                
                <div class="flex gap-3 w-full md:w-auto sm:ml-0">
                    <a href="RouteEdit.php?RouteID=<?php echo urlencode($routeData['RouteID']); ?>" 
                       class="action-btn flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-indigo-500 to-blue-600 text-white font-bold rounded-xl flex items-center justify-center gap-2 shadow-md hover:from-indigo-600 hover:to-blue-700">
                        <i class="fas fa-edit"></i> Edit Route
                    </a>
                </div>
            </div>

            <!-- Info Grid Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Route ID Card -->
                <div class="info-card rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner border border-indigo-100 flex-shrink-0">
                            <i class="fas fa-route"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Route ID</p>
                            <p class="text-lg font-extrabold text-slate-800 truncate" title="<?php echo htmlspecialchars($routeData['RouteID']); ?>"><?php echo htmlspecialchars($routeData['RouteID']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Bus Number Card -->
                <div class="info-card rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner border border-emerald-100 flex-shrink-0">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Assigned Bus</p>
                            <p class="text-lg font-extrabold text-slate-800 truncate" title="<?php echo htmlspecialchars($routeData['BusNo']); ?>"><?php echo htmlspecialchars($routeData['BusNo']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Direction Card -->
                <div class="info-card rounded-2xl p-6 shadow-sm flex flex-col justify-between relative overflow-hidden group">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-inner border border-blue-100 flex-shrink-0">
                                <i class="fas fa-compass"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Direction</p>
                                <?php 
                                    if ($routeData['Direction'] == 'Forward') {
                                        echo "<span class='inline-flex text-sm font-bold text-emerald-600'><i class='fas fa-arrow-right mr-1.5 mt-1'></i>Forward</span>";
                                    } elseif ($routeData['Direction'] == 'Reverse') {
                                        echo "<span class='inline-flex text-sm font-bold text-blue-600'><i class='fas fa-arrow-left mr-1.5 mt-1'></i>Reverse</span>";
                                    } else {
                                        echo "<span class='inline-flex text-sm font-bold text-slate-600'><i class='fas fa-exchange-alt mr-1.5 mt-1'></i>Single</span>";
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($routeData['Direction'] === "Forward" || $routeData['Direction'] === "Reverse") { ?>
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <button onclick="toggleDirection('<?php echo $routeData['RouteID']; ?>', '<?php echo $routeData['Direction']; ?>', '<?php echo $routeData['BusID']; ?>')" 
                                    class="w-full py-2 bg-slate-50 hover:bg-indigo-50 text-indigo-600 rounded-lg text-sm font-bold transition-colors flex items-center justify-center gap-2 border border-slate-200 hover:border-indigo-200">
                                <i class="fas fa-sync-alt text-xs"></i> Swap Direction
                            </button>
                        </div>
                    <?php } ?>
                </div>

                <!-- Admin Card -->
                <div class="info-card rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner border border-purple-100 flex-shrink-0">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($routeData['AdminName']); ?>&background=f3e8ff&color=9333ea&size=48" alt="Avatar" class="w-full h-full rounded-xl">
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Created By</p>
                            <p class="text-lg font-extrabold text-slate-800 truncate" title="<?php echo htmlspecialchars($routeData['AdminName']); ?>"><?php echo htmlspecialchars($routeData['AdminName']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="bg-white p-2 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 mb-2 bg-slate-50/50 rounded-t-xl">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-map-marked-alt text-indigo-500"></i> Interactive Route Map
                    </h2>
                    <span class="text-xs font-bold bg-slate-200 text-slate-600 px-3 py-1 rounded-full"><?php echo count($gateData); ?> Gates Total</span>
                </div>
                <div id="map" class="shadow-inner"></div>
            </div>
        </div>
    </main>

    <script>
    // Initialize the map
    const map = L.map('map').setView([16.8409, 96.1735], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '<b>YBSHubMM</b> &copy; OpenStreetMap contributors',
    }).addTo(map);

    // Custom icons
    const gateIcon = L.icon({ iconUrl: '../assets/images/SVG/bus_gate.svg', iconSize: [35, 35] });
    const userIcon = L.icon({ iconUrl: 'target.png', iconSize: [30, 30] });

    // Add gate markers
    const gates = <?php echo json_encode($gateData); ?>;
    gates.forEach(gate => {
        if (gate.Latitude && gate.Longitude) {
            L.marker([parseFloat(gate.Latitude), parseFloat(gate.Longitude)], { icon: gateIcon })
                .addTo(map)
                .bindPopup(`<b>${gate.GateName}</b>`);
        }
    });

    // User location tracking
    function requestLocationPermission() {
        if (!navigator.geolocation) {
            alert("Geolocation is not supported by your browser.");
            return;
        }

        navigator.geolocation.watchPosition(
            position => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;

                const userMarker = L.marker([lat, lon], { icon: userIcon })
                    .addTo(map)
                    .bindPopup("You are here!")
                    .openPopup();

                map.setView([lat, lon], 13);
            },
            error => {
                console.error("Geolocation error:", error);
                alert("Could not get your location.");
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    requestLocationPermission();
    // Handle changing route direction without updating database
    function toggleDirection(currentRouteID, currentDirection, busID) {
            let newDirection = currentDirection === "Forward" ? "Reverse" : "Forward";

            fetch(`GetRouteByDirection.php?BusID=${busID}&Direction=${newDirection}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `ViewRouteAdmin.php?RouteID=${data.RouteID}`;
                    } else {
                        alert("No route found for this bus in " + newDirection + " direction.");
                    }
                })
                .catch(error => console.error("Error:", error));
        }


    </script>
</body>
</html>
