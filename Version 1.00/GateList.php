<?php
session_start();
include('config/database.php');
if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}
include('includes/maintenance_check.php'); // Include the maintenance check function

// Check for active maintenance
checkMaintenance($connect);

// Search functionality
$searchQuery = isset($_GET['search']) ? trim(mysqli_real_escape_string($connect, $_GET['search'])) : '';

// Normalize search query by removing spaces
$searchQueryNoSpaces = str_replace(' ', '', $searchQuery);

// Include centralized analytics tracking
if (file_exists('core/analytics.php')) {
    include_once('core/analytics.php');
}

// Fetch gate list with prepared statement, handling spaces
$gatesQuery = "
    SELECT 
        gate.GateID, 
        gate.GateName, 
        gate.Road, 
        gate.Latitude, 
        gate.Longitude
    FROM gate
    WHERE (LOWER(gate.GateName) LIKE LOWER(?) 
           OR LOWER(REPLACE(gate.GateName, ' ', '')) LIKE LOWER(?)
           OR LOWER(gate.Road) LIKE LOWER(?)
           OR LOWER(REPLACE(gate.Road, ' ', '')) LIKE LOWER(?))
    ORDER BY gate.GateName ASC
";
$stmt = mysqli_prepare($connect, $gatesQuery);
$searchParam = "%$searchQuery%";
$searchParamNoSpaces = "%$searchQueryNoSpaces%";
mysqli_stmt_bind_param($stmt, "ssss", $searchParam, $searchParamNoSpaces, $searchParam, $searchParamNoSpaces);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$gates = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if (function_exists('logStat')) {
    logStat($connect, 'page_view', 'GateList.php');
}

// --- Pagination Logic ---
$gatesPerPage = 50;
$totalGates = count($gates);
$totalPages = ceil($totalGates / $gatesPerPage);
$pageNumber = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
if ($pageNumber > $totalPages && $totalPages > 0) {
    $pageNumber = $totalPages;
}
$offset = ($pageNumber - 1) * $gatesPerPage;

// Slice gates for the current page grid
$pagedGates = array_slice($gates, $offset, $gatesPerPage);

// Fetch buses ONLY for the paged gates
$gateBuses = [];
foreach ($pagedGates as $gate) {
    $gateID = $gate['GateID'];
    $busesQuery = "
        SELECT DISTINCT bus.BusID, bus.BusNo, bus.Color
        FROM route_gate
        JOIN route ON route_gate.RouteID = route.RouteID
        JOIN bus ON route.BusID = bus.BusID
        WHERE route_gate.GateID = ?
        ORDER BY CAST(bus.BusNo AS UNSIGNED) ASC
    ";
    $stmt = mysqli_prepare($connect, $busesQuery);
    mysqli_stmt_bind_param($stmt, "s", $gateID);
    mysqli_stmt_execute($stmt);
    $busesResult = mysqli_stmt_get_result($stmt);
    $gateBuses[$gateID] = mysqli_fetch_all($busesResult, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore all bus gates and stops for the Yangon Bus Service (YBS). Find the nearest gate for your Yangon public transport journey.">
    <meta name="keywords" content="YBS Gates, Yangon Bus Service, YBS Bus Stops, Yangon Public Transport, Yangon Bus">
    <title>Gate List - YBS Hub | Yangon Bus Service</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400&display=swap" rel="stylesheet" rel="preload" as="style">
    <link rel="icon" type="image/svg+xml" href="assets/images/Logo/YBS_Web_Logo.svg"> 
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <style>
        #map { height: 384px; width: 100%; } /* Explicit height for map */
        /* Spinner Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 1.5s linear infinite;
        }
        /* Ensure Google Fonts or system fonts are used to avoid Roboto-Regular.ttf 404 */
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include('includes/header.php'); ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        <!-- Search Section -->
        <div class="hidden md:block mb-8 sticky top-16 z-40 bg-gray-100 py-3 rounded-b-lg shadow-sm border-b border-gray-200">
            <form method="GET" action="GateList.php">
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by Gate Name or Road" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                        value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Header and View Map Button -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Gate List</h2>
            <button id="view-map-btn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-300 flex items-center">
                <i class="fas fa-map mr-2"></i> View Map
            </button>
        </div>

        <!-- Gates Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($pagedGates)): ?>
                <?php $counter = $offset + 1; ?>
                <?php foreach ($pagedGates as $gate): ?>
                    <a href="gate_details.php?GateID=<?php echo htmlspecialchars($gate['GateID']); ?>" 
                       class="block bg-white rounded-lg shadow p-6 hover:shadow-xl transition-shadow">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 font-bold"><?php echo $counter++; ?></span>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium"><?php echo htmlspecialchars($gate['GateName']); ?></h3>
                                <p class="text-sm text-gray-600">Road - <?php echo htmlspecialchars($gate['Road'] ?? 'Unknown'); ?></p>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600">Buses:</p>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        <?php if (!empty($gateBuses[$gate['GateID']])): ?>
                                            <?php foreach ($gateBuses[$gate['GateID']] as $bus): ?>
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white text-sm font-bold"
                                                      style="background-color: <?php echo htmlspecialchars($bus['Color']); ?>; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);">
                                                    <?php echo htmlspecialchars($bus['BusNo']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">No buses stop here.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center col-span-full text-gray-600">No gates found.</p>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 0): ?>
            <div class="mt-10 flex justify-center items-center space-x-4">
                <?php 
                $searchParamStr = !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '';
                ?>
                <!-- Previous Button -->
                <?php if ($pageNumber > 1): ?>
                    <a href="?page=<?php echo $pageNumber - 1; ?><?php echo $searchParamStr; ?>" class="flex items-center px-6 py-2.5 bg-white text-gray-700 font-medium rounded-full shadow hover:shadow-md hover:bg-blue-50 hover:text-blue-600 transition-all duration-300 border border-gray-200 group">
                        <i class="fas fa-chevron-left mr-2 text-sm transform group-hover:-translate-x-1 transition-transform"></i> Previous
                    </a>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <div class="px-5 py-2 bg-gray-50 rounded-full text-gray-700 font-bold shadow-inner border border-gray-200">
                    <?php echo $pageNumber; ?> <span class="text-gray-400 font-normal mx-1">/</span> <?php echo max(1, $totalPages); ?>
                </div>

                <!-- Next Button -->
                <?php if ($pageNumber < $totalPages): ?>
                    <a href="?page=<?php echo $pageNumber + 1; ?><?php echo $searchParamStr; ?>" class="flex items-center px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-full shadow-md hover:shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:-translate-y-0.5 transition-all duration-300 group">
                        Next <i class="fas fa-chevron-right ml-2 text-sm transform group-hover:translate-x-1 transition-transform"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Map Modal (Hidden by Default) -->
        <div id="map-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-4xl mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">All Gates Map</h3>
                    <button id="close-map-btn" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="flex items-center mb-4">
                    <button id="toggle-location-btn" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors duration-300 flex items-center">
                        <i class="fas fa-location-dot mr-2"></i> Show My Location
                    </button>
                </div>
                <div id="map" class="h-96 w-full rounded-lg"></div>
            </div>
        </div>
    </main>

    <?php include('includes/footer.php'); ?>

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script>
        // Initialize global variables
        let map = null;
        let userMarker = null; // Leaflet marker
        let userAccuracyCircle = null; // Leaflet circle
        let gateMarkers = [];
        let isMapInitialized = false;
        let locationWatchId = null;
        let isTrackingLocation = false;

        function initMap() {
            try {
                const gates = <?php echo json_encode($gates); ?>;
                if (gates && gates.length > 0) {
                    const validCoords = gates
                        .map(g => ({
                            gate: g,
                            lat: parseFloat(g.Latitude),
                            lng: parseFloat(g.Longitude),
                        }))
                        .filter(x => !isNaN(x.lat) && !isNaN(x.lng));

                    const defaultLocation = validCoords.length
                        ? [validCoords[0].lat, validCoords[0].lng]
                        : [16.8409, 96.1735];

                    map = L.map('map', {
                        zoomControl: true,
                        attributionControl: true,
                    }).setView(defaultLocation, 12);

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

                    const bounds = L.latLngBounds();
                    
                    // Create a cluster group
                    const markersCluster = L.markerClusterGroup({
                        chunkedLoading: true, // Smoother loading for 1000+ markers
                        maxClusterRadius: 50, // Distance to group
                        spiderfyOnMaxZoom: true
                    });

                    validCoords.forEach(({ gate, lat, lng }) => {
                        const marker = L.marker([lat, lng], { icon: gateIcon, title: gate.GateName });
                        marker.bindPopup(
                            `<div style="font-weight: 700;">${gate.GateName}</div><div style="font-size: 12px; color: #4b5563;">Road: ${gate.Road ?? ''}</div>`
                        );

                        // Click: show popup. Double click: go to details
                        marker.on('click', () => marker.openPopup());
                        marker.on('dblclick', () => {
                            window.location.href = `gate_details.php?GateID=${encodeURIComponent(gate.GateID)}`;
                        });

                        markersCluster.addLayer(marker);
                        gateMarkers.push(marker);
                        bounds.extend([lat, lng]);
                    });

                    // Add cluster to map
                    map.addLayer(markersCluster);

                    if (validCoords.length) {
                        map.fitBounds(bounds.pad(0.1));
                    }
                } else {
                    document.getElementById('map').innerHTML = '<p class="text-gray-500 text-center py-4">No gate locations available to display on the map.</p>';
                }

                isMapInitialized = true;
            } catch (error) {
                console.error("Failed to initialize map:", error);
                document.getElementById('map').innerHTML = '<p class="text-red-700 text-center py-3">Failed to load map. Please check your internet connection or refresh website again!.</p>';
            }
        }

        function requestLocationPermission() {
            if (!navigator.geolocation) {
                console.error("Geolocation not supported by this browser.");
                document.getElementById('map').innerHTML = '<p class="text-red-700 text-center py-3">Sorry, geolocation does not supported by this browser.</p>';
                return;
            }

            locationWatchId = navigator.geolocation.watchPosition(
                position => {
                    if (!map) return;
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    const userLatLng = L.latLng(lat, lng);
                    if (!userMarker) {
                        userMarker = L.circleMarker(userLatLng, {
                            radius: 7,
                            color: '#1d4ed8',
                            weight: 2,
                            fillColor: '#3b82f6',
                            fillOpacity: 0.85,
                        }).addTo(map);
                        userMarker.bindPopup('You are here!');
                    } else {
                        userMarker.setLatLng(userLatLng);
                    }

                    if (!userAccuracyCircle) {
                        userAccuracyCircle = L.circle(userLatLng, {
                            radius: accuracy || 0,
                            color: '#60a5fa',
                            weight: 1,
                            fillColor: '#93c5fd',
                            fillOpacity: 0.25,
                        }).addTo(map);
                    } else {
                        userAccuracyCircle.setLatLng(userLatLng);
                        userAccuracyCircle.setRadius(accuracy || 0);
                    }
                },
                error => {
                    console.error("Geolocation error:", error);
                    let errorMsg = 'Unable to retrieve your location.';
                    if (error.code === 1) errorMsg = 'Location permission denied by user.';
                    else if (error.code === 2) errorMsg = 'Location position unavailable.';
                    else if (error.code === 3) errorMsg = 'Location request timed out.';
                    
                    if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
                        errorMsg += ' Note: Geolocation requires HTTPS or localhost.';
                    }
                    
                    document.getElementById('map').innerHTML = `<p class="text-red-700 text-center py-3">${errorMsg}</p>`;
                },
                { enableHighAccuracy: false, timeout: 15000, maximumAge: 60000 }
            );
        }

        document.addEventListener('DOMContentLoaded', function () {
            const viewMapBtn = document.getElementById('view-map-btn');
            const mapModal = document.getElementById('map-modal');
            const closeMapBtn = document.getElementById('close-map-btn');
            const toggleLocationBtn = document.getElementById('toggle-location-btn');

            if (viewMapBtn && mapModal && closeMapBtn && toggleLocationBtn) {
                viewMapBtn.addEventListener('click', () => {
                    mapModal.classList.remove('hidden');
                    if (!isMapInitialized) {
                        initMap();
                    } else if (map) {
                        // Leaflet needs invalidateSize after modal opens
                        setTimeout(() => {
                            map.invalidateSize();
                            const gates = <?php echo json_encode($gates); ?>;
                            const validCoords = (gates || [])
                                .map(g => [parseFloat(g.Latitude), parseFloat(g.Longitude)])
                                .filter(([lat, lng]) => !isNaN(lat) && !isNaN(lng));
                            if (validCoords.length) {
                                map.fitBounds(L.latLngBounds(validCoords).pad(0.1));
                            }
                        }, 50);
                    }
                });

                closeMapBtn.addEventListener('click', () => {
                    mapModal.classList.add('hidden');
                    if (locationWatchId) {
                        navigator.geolocation.clearWatch(locationWatchId);
                        locationWatchId = null;
                        isTrackingLocation = false;
                        toggleLocationBtn.innerHTML = '<i class="fas fa-location-dot mr-2"></i> Show My Location';
                        if (userMarker) {
                            map.removeLayer(userMarker);
                            userMarker = null;
                        }
                        if (userAccuracyCircle) {
                            map.removeLayer(userAccuracyCircle);
                            userAccuracyCircle = null;
                        }
                    }
                });

                mapModal.addEventListener('click', (e) => {
                    if (e.target === mapModal) {
                        mapModal.classList.add('hidden');
                        if (locationWatchId) {
                            navigator.geolocation.clearWatch(locationWatchId);
                            locationWatchId = null;
                            isTrackingLocation = false;
                            toggleLocationBtn.innerHTML = '<i class="fas fa-location-dot mr-2"></i> Show My Location';
                            if (userMarker) {
                                map.removeLayer(userMarker);
                                userMarker = null;
                            }
                            if (userAccuracyCircle) {
                                map.removeLayer(userAccuracyCircle);
                                userAccuracyCircle = null;
                            }
                        }
                    }
                });

                toggleLocationBtn.addEventListener('click', () => {
                    if (!isTrackingLocation) {
                        requestLocationPermission();
                        isTrackingLocation = true;
                        toggleLocationBtn.innerHTML = '<i class="fas fa-times mr-2"></i> Hide My Location';
                    } else {
                        if (locationWatchId) {
                            navigator.geolocation.clearWatch(locationWatchId);
                            locationWatchId = null;
                        }
                        isTrackingLocation = false;
                        toggleLocationBtn.innerHTML = '<i class="fas fa-location-dot mr-2"></i> Show My Location';
                        if (userMarker) {
                            map.removeLayer(userMarker);
                            userMarker = null;
                        }
                        if (userAccuracyCircle) {
                            map.removeLayer(userAccuracyCircle);
                            userAccuracyCircle = null;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>