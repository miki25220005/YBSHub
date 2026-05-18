<?php
session_start();
include('config/database.php'); // Databases connection
if (file_exists('includes/loader.php')) { // loader for more better design
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
    logStat($connect, 'page_view', 'Gate_Details');
}

// Get Gate ID from URL
$gateID = isset($_GET['GateID']) ? mysqli_real_escape_string($connect, $_GET['GateID']) : '';

// Fetch Correct Gate Info
$gateQuery = "
    SELECT gate.GateID, gate.GateName, gate.Latitude, gate.Longitude, 
           gate.Road, 
           township.TownshipName 
    FROM gate
    LEFT JOIN township ON gate.TownshipID = township.TownshipID
    WHERE gate.GateID = ?";
$stmt = mysqli_prepare($connect, $gateQuery);
mysqli_stmt_bind_param($stmt, "s", $gateID);
mysqli_stmt_execute($stmt);
$gateResult = mysqli_stmt_get_result($stmt);
$gate = mysqli_fetch_assoc($gateResult);
mysqli_stmt_close($stmt);

// Fetch Buses at This Gate (Ensuring Unique Bus per ID)
$busesQuery = "
    SELECT 
        bus.BusID, 
        bus.BusNo, 
        bus.CardQR, 
        bus.Color, 
        MIN(route.Notes) AS Notes
    FROM bus
    JOIN route ON bus.BusID = route.BusID
    JOIN route_gate ON route.RouteID = route_gate.RouteID
    WHERE route_gate.GateID = ?
    GROUP BY bus.BusID
    ORDER BY CAST(bus.BusNo AS UNSIGNED) ASC
";

$stmt = mysqli_prepare($connect, $busesQuery);
mysqli_stmt_bind_param($stmt, "s", $gateID);
mysqli_stmt_execute($stmt);
$busesResult = mysqli_stmt_get_result($stmt);
$buses = mysqli_fetch_all($busesResult, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gate['GateName'] ?? 'Unknown Gate'); ?> - YBS Hub</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400&display=swap" rel="stylesheet" rel="preload" as="style">
    <link rel="icon" type="image/png" href="assets/images/Logo/web_logo.png">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <style>
        /* Spinner Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 1.5s linear infinite;
        }
        /* Popup Styles */
        .popup {
            position: fixed;
            top: 10px;
            left: 10px; /* Positioned top-left */
            background-color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            max-width: 90%; /* Ensure it fits on mobile */
        }
        .popup .icon {
            margin-right: 8px;
            font-size: 18px;
            color: #f59e0b; /* Orange color for hand icon */
        }
        .popup .message {
            font-size: 14px;
            color: #374151;
            flex: 1; /* Allow text to take available space */
        }
        .popup .dismiss {
            margin-left: 10px;
            color: #3b82f6;
            text-decoration: underline;
            cursor: pointer;
            font-size: 12px;
            white-space: nowrap; /* Prevent wrapping */
        }
        .popup.hidden {
            animation: slideOutLeft 0.5s ease-out forwards; /* Default to slide left */
        }
        @keyframes slideIn {
            0% { transform: translateY(-100%); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideOutLeft {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateX(-100%) translateY(0); opacity: 0; }
        }
        @keyframes slideOutRight {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateX(100%) translateY(0); opacity: 0; }
        }
        @keyframes slideOutTop {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(-100%); opacity: 0; }
        }
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="notificationPopup" class="popup hidden">
        <i class="fa-solid fa-hand icon"></i>
        <span class="message">အချို့မှတ်တိုင်များမှာ လူတက်/ဆင်းနည်းသည့်အတွက် စီးနင်းလိုသည့် ကားရောက်ရှိလာပါက <b>လက်တား</b> ခြင်းဖြင့် ကားစီးလိုသည့်အကြောင်းကို ယာဥ်မောင်းကိုကြိုတင်အသိပေးပါ။</span>
        <span id="dismissBtn" class="dismiss">Dismiss</span>
    </div>

    <?php include('includes/header.php'); ?>

    <div class="max-w-7xl mx-auto px-4 mt-8">
        <div class="flex items-center space-x-3">
            <img src="assets/images/SVG/bus_stop.svg" alt="Gate Icon" class="w-12 h-12 text-gray-600">
            <h2 class="text-xl font-semibold">
                Gate Name - <?php echo htmlspecialchars($gate['GateName'] ?? 'Unknown'); ?>
            </h2>
        </div>
        <p class="text-gray-600 mt-2">
            မြို့အမည် - <span class="font-semibold"><?php echo htmlspecialchars($gate['TownshipName'] ?? 'Unknown'); ?></span>
        </p>
        <p class="text-gray-600 mt-2">
            လမ်းအမည် - <span class="font-semibold"><?php echo htmlspecialchars($gate['Road'] ?? 'Unknown'); ?></span>
        </p>
        <p class="mt-2">မှတ်တိုင်သို့ ရောက်ရှိသည့် ဘက်စ်ကားအရေအတွက် - <span class="font-semibold"> <?php echo count($buses); ?></span></p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <?php if (!empty($buses)): ?>
                <?php foreach ($buses as $bus): ?>
                    <a href="bus_details.php?BusID=<?php echo htmlspecialchars($bus['BusID']); ?>" class="block">
                        <div class="bg-white p-6 rounded-lg shadow flex items-center space-x-4 hover:shadow-lg transition">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold text-white"
                                 style="background-color: <?php echo htmlspecialchars($bus['Color'] ?? '#000'); ?>;
                                        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">
                                <?php echo htmlspecialchars($bus['BusNo']); ?>
                            </div>
                            <div class="space-y-2 flex-1">
                                <p class="font-medium"><b><?php echo htmlspecialchars($bus['Notes'] ?? 'No Route Available'); ?></b></p>
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
                                <p class="text-sm">
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
                <p class="text-gray-600 text-center col-span-full flex items-center justify-center space-x-2">
                    <i class="fas fa-triangle-exclamation text-red-500 text-lg"></i>
                    <span>No buses found for this gate.</span>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 mt-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <div id="map" class="h-96 w-full rounded-lg">
                <div class="flex items-center justify-center h-full bg-gray-100 rounded-lg">
                    <div class="text-center p-6">
                        <i class="fas fa-spinner text-3xl text-blue-500 spinner mb-4"></i>
                        <p class="text-lg font-semibold text-gray-700">Loading Map...</p>
                        <p class="text-sm text-gray-500 mt-2">Please wait a moment while we fetch the gate location.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script>
    // Define global variables
    let map;
    let userMarker;
    let userAccuracyCircle;
    let locationWatchId = null;

    function initMap() {
        try {
            const gateLat = <?php echo json_encode($gate['Latitude'] ?? 16.8409); ?>;
            const gateLng = <?php echo json_encode($gate['Longitude'] ?? 96.1735); ?>;
            const gateName = <?php echo json_encode($gate['GateName'] ?? 'Gate'); ?>;
            const lat = parseFloat(gateLat);
            const lng = parseFloat(gateLng);
            const gatePosition = (!isNaN(lat) && !isNaN(lng)) ? [lat, lng] : [16.8409, 96.1735];

            map = L.map('map').setView(gatePosition, 15);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            }).addTo(map);

            const gateIcon = L.icon({
                iconUrl: 'assets/images/SVG/bus_gate.svg',
                iconSize: [35, 35],
                iconAnchor: [17, 35],
                popupAnchor: [0, -32],
            });

            const gateMarker = L.marker(gatePosition, { icon: gateIcon, title: gateName }).addTo(map);
            gateMarker.bindPopup(`<b>${gateName}</b>`);

            requestLocationPermission();
        } catch (error) {
            console.error("Failed to initialize map:", error);
            document.getElementById('map').innerHTML = '<p class="text-red-600 text-center py-4">Failed to load map. Please check your internet connection.</p>';
        }
    }

    function requestLocationPermission() {
        if (!navigator.geolocation) {
            console.error("Geolocation not supported by this browser.");
            showLocationError("Geolocation not supported by this browser.");
            return;
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        locationWatchId = navigator.geolocation.watchPosition(
            position => {
                if (!map) return;
                const userLocation = L.latLng(position.coords.latitude, position.coords.longitude);
                const accuracy = position.coords.accuracy;

                if (!userMarker) {
                    userMarker = L.circleMarker(userLocation, {
                        radius: 7,
                        color: '#1d4ed8',
                        weight: 2,
                        fillColor: '#3b82f6',
                        fillOpacity: 0.85,
                    }).addTo(map);
                    userMarker.bindPopup('You are here!');
                } else {
                    userMarker.setLatLng(userLocation);
                }

                if (!userAccuracyCircle) {
                    userAccuracyCircle = L.circle(userLocation, {
                        radius: accuracy || 0,
                        color: '#60a5fa',
                        weight: 1,
                        fillColor: '#93c5fd',
                        fillOpacity: 0.25,
                    }).addTo(map);
                } else {
                    userAccuracyCircle.setLatLng(userLocation);
                    userAccuracyCircle.setRadius(accuracy || 0);
                }
            },
            error => {
                console.error("Geolocation error:", error);
                showLocationError("Unable to retrieve your location. Please enable location services in your phone settings.");
            },
            options
        );
    }

    function showLocationError(message) {
        let popup = document.getElementById('locationErrorPopup');
        if (!popup) {
            popup = document.createElement('div');
            popup.id = 'locationErrorPopup';
            popup.className = 'popup hidden';
            popup.innerHTML = `
                <i class="fa-solid fa-exclamation-triangle icon"></i>
                <span class="message">${message}</span>
                <span id="dismissLocationBtn" class="dismiss">Dismiss</span>
            `;
            document.body.appendChild(popup);
        } else {
            popup.querySelector('.message').textContent = message;
        }

        popup.classList.remove('hidden');
        popup.style.animation = 'slideIn 0.5s ease-out forwards';

        const dismissBtn = document.getElementById('dismissLocationBtn');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                popup.style.animation = 'slideOutLeft 0.5s ease-out forwards';
                setTimeout(() => popup.classList.add('hidden'), 500);
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const notificationPopup = document.getElementById("notificationPopup");
        const dismissBtn = document.getElementById("dismissBtn");
        let dismissDirection = 'left';

        setTimeout(() => {
            if (notificationPopup) {
                notificationPopup.classList.remove("hidden");
                notificationPopup.classList.add("popup");
                notificationPopup.style.animation = 'slideIn 0.5s ease-out forwards';
            }
        }, 500);

        if (dismissBtn) {
            dismissBtn.addEventListener("click", () => {
                const animation = dismissDirection === 'left' ? 'slideOutLeft' :
                                dismissDirection === 'right' ? 'slideOutRight' :
                                'slideOutTop';
                notificationPopup.style.animation = `${animation} 0.5s ease-out forwards`;
                setTimeout(() => {
                    notificationPopup.classList.add("hidden");
                    notificationPopup.style.animation = '';
                }, 500);

                dismissDirection = dismissDirection === 'left' ? 'right' :
                                dismissDirection === 'right' ? 'top' : 'left';
            });
        }
    });
    
    // Initialize Leaflet map on load
    document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>