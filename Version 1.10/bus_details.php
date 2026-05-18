<?php
session_start();
include('config/database.php');
if (file_exists('includes/loader.php')) {
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
    logStat($connect, 'page_view', 'bus_details.php');
}

$busID = isset($_GET['BusID']) ? mysqli_real_escape_string($connect, $_GET['BusID']) : '';
$direction = isset($_GET['Direction']) ? mysqli_real_escape_string($connect, $_GET['Direction']) : 'Forward';
$from_gate = isset($_GET['from_gate']) ? mysqli_real_escape_string($connect, $_GET['from_gate']) : '';
$to_gate = isset($_GET['to_gate']) ? mysqli_real_escape_string($connect, $_GET['to_gate']) : '';

if (empty($busID)) {
    die("Invalid Bus ID provided.");
}

// Fetch Bus Number (even if no route data exists)
$busNoQuery = "SELECT BusNo FROM bus WHERE BusID = ?";
$stmt = mysqli_prepare($connect, $busNoQuery);
mysqli_stmt_bind_param($stmt, "s", $busID);
mysqli_stmt_execute($stmt);
$busNoResult = mysqli_stmt_get_result($stmt);
$busNoRow = mysqli_fetch_assoc($busNoResult);
$busNo = $busNoRow ? htmlspecialchars($busNoRow['BusNo']) : 'Unknown';
mysqli_stmt_close($stmt);

$isLoop = false;
if ($direction === 'Loop') {
    $isLoop = true;
    $direction = 'Forward'; // Use Forward for the main bus details
}

// Fetch Bus Information
$busQuery = "
    SELECT 
        bus.BusID, 
        bus.BusNo, 
        bus.CardQR, 
        bus.Color, 
        route.Notes, 
        route.Direction
    FROM bus
    JOIN route ON bus.BusID = route.BusID
    WHERE bus.BusID = ? AND (route.Direction = ? OR route.Direction = 'Single')
    LIMIT 1";

$stmt = mysqli_prepare($connect, $busQuery);
mysqli_stmt_bind_param($stmt, "ss", $busID, $direction);
mysqli_stmt_execute($stmt);
$busResult = mysqli_stmt_get_result($stmt);
$bus = mysqli_fetch_assoc($busResult);
mysqli_stmt_close($stmt);

// isLoop logic moved above

// Fetch Gates for Selected Direction (Preserving natural insertion order)
// Step 1: Find the RouteID(s)
$routeIDs = [];
if ($isLoop) {
    $routeQuery = "SELECT RouteID FROM route WHERE BusID = ? AND (Direction = 'Forward' OR Direction = 'Reverse')";
    $stmt = mysqli_prepare($connect, $routeQuery);
    mysqli_stmt_bind_param($stmt, "s", $busID);
    mysqli_stmt_execute($stmt);
    $routeResult = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($routeResult)) {
        $routeIDs[] = $row['RouteID'];
    }
    mysqli_stmt_close($stmt);
} else {
    $routeQuery = "SELECT RouteID FROM route WHERE BusID = ? AND (Direction = ? OR Direction = 'Single') LIMIT 1";
    $stmt = mysqli_prepare($connect, $routeQuery);
    mysqli_stmt_bind_param($stmt, "ss", $busID, $direction);
    mysqli_stmt_execute($stmt);
    $routeResult = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($routeResult)) {
        $routeIDs[] = $row['RouteID'];
    }
    mysqli_stmt_close($stmt);
}

$gateData = [];
if (!empty($routeIDs)) {
    $orderedGateIDs = [];
    foreach ($routeIDs as $rID) {
        $rgQuery = "SELECT GateID FROM route_gate WHERE RouteID = ? ORDER BY Position ASC";
        $stmt = mysqli_prepare($connect, $rgQuery);
        mysqli_stmt_bind_param($stmt, "s", $rID);
        mysqli_stmt_execute($stmt);
        $rgResult = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($rgResult)) {
            // Avoid inserting duplicate terminus gates when merging loops
            if (empty($orderedGateIDs) || end($orderedGateIDs) !== $row['GateID']) {
                $orderedGateIDs[] = $row['GateID'];
            }
        }
        mysqli_stmt_close($stmt);
    }

    if (!empty($orderedGateIDs)) {
        // Step 3: Fetch gate details
        $inStr = "'" . implode("','", array_map([$connect, 'real_escape_string'], $orderedGateIDs)) . "'";
        $gQuery = "
            SELECT 
                gate.GateID, 
                gate.GateName, 
                gate.Road, 
                gate.Latitude, 
                gate.Longitude,
                (SELECT COUNT(DISTINCT r2.BusID) 
                 FROM route r2
                 JOIN route_gate rg ON r2.RouteID = rg.RouteID
                 WHERE rg.GateID = gate.GateID) AS TotalBuses 
            FROM gate 
            WHERE GateID IN ($inStr)";
            
        $gResult = mysqli_query($connect, $gQuery);
        $gateMap = [];
        while ($row = mysqli_fetch_assoc($gResult)) {
            $gateMap[$row['GateID']] = $row;
        }

        // Step 4: Reconstruct array in the exact insertion order
        foreach ($orderedGateIDs as $gid) {
            if (isset($gateMap[$gid])) {
                $gateData[] = $gateMap[$gid];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus <?php echo htmlspecialchars($bus['BusNo'] ?? 'Unknown'); ?> - YBS Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/Logo/web_logo.png">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <style>
        .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15); }
        #map { height: 384px; width: 100%; }
        /* Fullscreen Map Styles */
        .map-fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 9999 !important;
            margin: 0 !important;
            border-radius: 0 !important;
        }
        /* Make Leaflet feel closer to Google/Grab */
        .leaflet-control-attribution { font-size: 10px; opacity: 0.85; }
        .leaflet-popup-content-wrapper { border-radius: 12px; }
        .leaflet-popup-content { margin: 10px 12px; }

        .ybs-pin {
            width: 30px;
            height: 30px;
            border-radius: 9999px;
            background: #111827;
            border: 3px solid #ffffff;
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.22);
            display: grid;
            place-items: center;
        }
        .ybs-pin svg { width: 16px; height: 16px; fill: #ffffff; }
        #go-to-top-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #3b82f6;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
        #go-to-top-btn.show { opacity: 1; }
        #go-to-top-btn:hover { background-color: #2563eb; }
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
            left: 10px; /* Adjusted to top-left corner */
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
            color: #f59e0b;
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
        /* Responsive Bus Stop Cards */
        .bus-stop-card {
            padding: 12px;
        }
        .bus-stop-card h3 {
            font-size: 16px;
        }
        .bus-stop-card p {
            font-size: 12px;
        }
        .bus-stop-card .view-details {
            padding: 6px 12px;
            font-size: 12px;
        }
        /* Ensure Google Fonts or system fonts are used to avoid Roboto-Regular.ttf 404 */
        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif !important;
        }
        @media (max-width: 640px) { /* Tailwind sm breakpoint */
            .bus-stop-grid {
                grid-template-columns: 1fr; /* Stack cards vertically on mobile */
            }
            .bus-stop-card {
                margin-bottom: 10px;
            }
            #route-map-section {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">
    <?php if ($bus): ?>
        <div id="notificationPopup" class="popup hidden">
            <i class="fa-solid fa-bell icon"></i>
            <span class="message">ဆင်းမည့်မှတ်တိုင်အား <b>ဘဲလ်</b> ကြိုတင်တီးခြင်းဖြင့် မောင်းနှင်နေသည့် ယာဥ်မောင်းအား ကူညီပေးပါ။</span>
            <span id="dismissBtn" class="dismiss">Dismiss</span>
        </div>
    <?php endif; ?>

    <?php include('includes/header.php'); ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if (!$bus): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-lg mb-8">
                <div class="flex items-center">
                    <i class="fas fa-triangle-exclamation mr-3 text-xl"></i>
                    <div>
                        <h2 class="text-xl font-semibold">Sorry, Bus Not Found</h2>
                        <p class="mt-1">No information available for YBS <?php echo $busNo; ?> </p>
                        <a href="index.php" class="mt-2 inline-block text-blue-600 hover:underline">Return to Home</a>
                    </div>
                </div>
            </div>
    <?php else: ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6">
                    <div class="w-24 h-24 rounded-full border-4 border-gray-200 flex items-center justify-center"
                         style="background-color: <?php echo htmlspecialchars($bus['Color'] ?? '#e5e7eb'); ?>;">
                        <div class="text-center text-white font-semibold">
                            <div class="text-xl"><?php echo htmlspecialchars($bus['BusNo'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <div class="text-center sm:text-left flex-1">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <img src="assets/images/SVG/bus.svg" alt="Bus" class="w-6 h-6 mr-2">
                            <?php echo htmlspecialchars($bus['Notes'] ?? 'No Route Notes'); ?>
                        </h2>
                        <p class="mt-2 text-sm font-medium flex items-center <?php echo ($bus['CardQR'] == 'Yes') ? 'text-green-600' : 'text-red-600'; ?> text-center md:text-left">
                            <i class="fas fa-credit-card mr-2"></i>
                            Card/QR Payment: <?php echo ($bus['CardQR'] == 'Yes') ? 'Available' : 'Not Available'; ?>
                        </p>
                        <?php if ($bus['Direction'] !== "Single"): ?>
                            <div class="mt-4 flex justify-center sm:justify-start space-x-4">
                                <?php if ($bus['Direction'] === "Forward"): ?>
                                    <button id="reverseBtn" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-300 flex items-center">
                                        Reverse Route <i class="fa-solid fa-backward ml-3"></i>
                                    </button>
                                <?php elseif ($bus['Direction'] === "Reverse"): ?>
                                    <button id="forwardBtn" class="bg-blue-500 text-white px-5 py-2 rounded-lg hover:bg-blue-600 transition-all duration-300 flex items-center">
                                        <i class="fa-solid fa-forward mr-2"></i> Forward Route
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-4 flex justify-center sm:justify-start">
                            <button id="viewMapBtn" class="bg-blue-500 text-white px-5 py-2 rounded-lg hover:bg-blue-600 transition-all duration-300 flex items-center">
                                <i class="fa-solid fa-map-location-dot mr-2"></i> View Map
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <section class="mb-8 bg-gray-50 shadow-lg rounded-xl p-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4 border-b-2 border-blue-500 pb-2 flex items-center"> <img src="assets/images/SVG/bus-stop.svg" alt="Bus Stop Icon" class="w-10 h-11 mr-2">
        Bus Stops
    </h2>
    <div class="bus-stop-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (!empty($gateData)): ?>
            <?php 
                $counter = 1; 
                $inSegment = false;
            ?>
            <?php foreach ($gateData as $gate): ?>
                <?php 
                    if ($from_gate && $gate['GateID'] == $from_gate) {
                        $inSegment = true;
                    }
                    
                    $highlightClass = ($inSegment) ? 'bg-blue-50 border-l-4 border-blue-500' : 'bg-white border-gray-300';
                    $targetId = ($from_gate && $gate['GateID'] == $from_gate) ? 'id="gate-from-highlight"' : '';
                ?>
                <a href="gate_details.php?GateID=<?php echo htmlspecialchars($gate['GateID']); ?>" 
                   <?php echo $targetId; ?>
                   class="block bus-stop-card rounded-xl p-4 flex flex-col justify-between border shadow-md transition-all duration-300 hover:shadow-xl <?php echo $highlightClass; ?>">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center shadow-md">
                            <span class="text-blue-600 font-bold text-sm"><?php echo $counter++; ?></span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($gate['GateName']); ?></h3>
                            <p class="text-sm text-gray-600">Road: <?php echo htmlspecialchars($gate['Road'] ?? 'Unknown'); ?></p>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <span class="view-details inline-flex items-center px-3 py-1 bg-blue-500 text-white text-sm font-medium rounded-md shadow-md hover:bg-blue-600 transition-colors duration-300">
                            View Details
                            <i class="fas fa-arrow-right ml-1"></i>
                        </span>
                    </div>
                </a>
                <?php 
                    if ($to_gate && $gate['GateID'] == $to_gate) {
                        $inSegment = false;
                    }
                ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="col-span-full text-center text-gray-500 py-4">No bus stops found for this route.</p>
        <?php endif; ?>
    </div>
</section>

            <section id="route-map-section">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Route Map</h2>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div id="map" class="relative">
                        <button id="closeMapBtn" class="hidden absolute top-4 right-4 bg-white text-red-500 rounded-full w-10 h-10 flex items-center justify-center shadow-lg hover:bg-gray-100 transition-colors" style="z-index: 10000;">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                        <div class="flex items-center justify-center h-full bg-gray-100 rounded-lg">
                            <div class="text-center p-6">
                                <i class="fas fa-spinner text-3xl text-blue-500 spinner mb-4"></i>
                                <p class="text-lg font-semibold text-gray-700">Loading Map...</p>
                                <p class="text-sm text-gray-500 mt-2">Please wait a moment while we fetch the route.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include('includes/footer.php'); ?>

    <button id="go-to-top-btn" title="Go to Top">
        <i class="fas fa-arrow-up text-xl"></i>
    </button>

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
        let markerClickCount = {}; // click counts for markers (Leaflet)
        let routeLine = null;
        let routeLineCasing = null;
        let activeStraightLine = null;
        let activeStraightLineCasing = null;

        function initMap() {
            try {
                const gates = <?php echo json_encode($gateData ?: []); ?>;
                if (gates && gates.length > 0) {
                    const coords = (gates || [])
                        .map(g => ({
                            gate: g,
                            lat: parseFloat(g.Latitude),
                            lng: parseFloat(g.Longitude),
                        }))
                        .filter(x => !isNaN(x.lat) && !isNaN(x.lng));

                    const defaultLocation = coords.length ? [coords[0].lat, coords[0].lng] : [16.8409, 96.1735];
                    map = L.map('map').setView(defaultLocation, 13);
                    // Light basemap (Grab/Google-like) using OSM data (no API key)
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                        maxZoom: 19,
                        attribution: '&copy; YBS Hub',
                    }).addTo(map);

                    const fromGateID = <?php echo json_encode($from_gate); ?>;
                    const toGateID = <?php echo json_encode($to_gate); ?>;
                    
                    const gateIcon = L.icon({
                        iconUrl: 'assets/images/SVG/bus-stop.svg',
                        iconSize: [35, 35],
                        iconAnchor: [17, 35],
                        popupAnchor: [0, -32],
                    });
                    
                    const activeGateIcon = L.icon({
                        iconUrl: 'assets/images/SVG/bus-stop.svg',
                        iconSize: [45, 45],
                        iconAnchor: [22, 45],
                        popupAnchor: [0, -40],
                        className: 'drop-shadow-xl brightness-110 filter'
                    });

                    const bounds = L.latLngBounds();
                    const path = [];
                    const activeSegment = [];
                    let inActiveSegment = false;

                    coords.forEach(({ gate, lat, lng }) => {
                        const isStart = fromGateID && gate.GateID == fromGateID;
                        const isEnd = toGateID && gate.GateID == toGateID;
                        
                        if (isStart) inActiveSegment = true;
                        
                        const isHighlight = inActiveSegment || (fromGateID === '' && toGateID === '');
                        
                        if (isHighlight) {
                            activeSegment.push([lat, lng]);
                        }
                        
                        const currentIcon = isHighlight ? activeGateIcon : gateIcon;
                        const marker = L.marker([lat, lng], { icon: currentIcon, title: gate.GateName, zIndexOffset: isHighlight ? 1000 : 0 }).addTo(map);
                        marker.bindPopup(`<div style="font-weight: 700;">${gate.GateName}</div>`);

                        markerClickCount[gate.GateID] = 0;
                        marker.on('click', () => {
                            markerClickCount[gate.GateID]++;
                            if (markerClickCount[gate.GateID] === 1) {
                                marker.openPopup();
                                setTimeout(() => {
                                    if (markerClickCount[gate.GateID] === 1) {
                                        markerClickCount[gate.GateID] = 0;
                                        marker.closePopup();
                                    }
                                }, 2000);
                            } else if (markerClickCount[gate.GateID] === 2) {
                                window.location.href = `gate_details.php?GateID=${encodeURIComponent(gate.GateID)}`;
                                markerClickCount[gate.GateID] = 0;
                            }
                        });

                        if (isEnd) inActiveSegment = false;

                        bounds.extend([lat, lng]);
                        path.push([lat, lng]);
                    });

                    const drawStraightLine = () => {
                        if (routeLine) {
                            map.removeLayer(routeLine);
                            routeLine = null;
                        }
                        if (routeLineCasing) {
                            map.removeLayer(routeLineCasing);
                            routeLineCasing = null;
                        }
                        if (path.length >= 2) {
                            // Casing + main line (navigation style - faded for full route)
                            routeLineCasing = L.polyline(path, {
                                color: '#0f172a',
                                weight: 8,
                                opacity: 0.15,
                                lineCap: 'round',
                                lineJoin: 'round',
                            }).addTo(map);
                            routeLine = L.polyline(path, {
                                color: '#94a3b8',
                                weight: 4,
                                opacity: 0.5,
                                lineCap: 'round',
                                lineJoin: 'round',
                            }).addTo(map);
                            
                            // Highlight the active segment specifically
                            if (activeSegment.length >= 2) {
                                activeStraightLineCasing = L.polyline(activeSegment, {
                                    color: '#0f172a',
                                    weight: 8,
                                    opacity: 0.3,
                                    lineCap: 'round',
                                    lineJoin: 'round',
                                }).addTo(map);
                                activeStraightLine = L.polyline(activeSegment, {
                                    color: '#2563eb', // Bright blue
                                    weight: 5,
                                    opacity: 1,
                                    lineCap: 'round',
                                    lineJoin: 'round',
                                }).addTo(map);
                            }
                        }
                    };

                    const buildRoadRoute = async (sourcePath) => {
                        // Use OSRM public server to snap route to roads.
                        // We chunk requests to avoid rate limits and URL length issues.
                        if (!sourcePath || sourcePath.length < 2) return null;

                        const chunkSize = 25;
                        const merged = [];

                        try {
                            for (let i = 0; i < sourcePath.length - 1; i += chunkSize - 1) {
                                const chunk = sourcePath.slice(i, i + chunkSize);
                                const coordsStr = chunk.map(p => `${p[1]},${p[0]}`).join(';');
                                // Use the 'walking' profile instead of 'driving'. 
                                // The driving profile strictly obeys one-way streets and divided roads.
                                // If a gate coordinate is slightly off, driving forces massive loops around blocks.
                                // Walking ignores these traffic rules and snaps to the shortest road path.
                                const url = `https://router.project-osrm.org/route/v1/walking/${coordsStr}?overview=full&geometries=geojson&steps=false`;

                                const res = await fetch(url);
                                if (!res.ok) return null; // Fallback to straight lines if API fails
                                
                                const data = await res.json();
                                const coords = data?.routes?.[0]?.geometry?.coordinates;
                                if (!coords || coords.length < 2) continue;

                                // OSRM returns [lng, lat], convert to [lat, lng]
                                const latlngs = coords.map(([lng, lat]) => [lat, lng]);
                                
                                // Append without duplicating the connection point between chunks
                                for (let j = 0; j < latlngs.length; j++) {
                                    const pt = latlngs[j];
                                    const last = merged[merged.length - 1];
                                    if (!last || last[0] !== pt[0] || last[1] !== pt[1]) {
                                        merged.push(pt);
                                    }
                                }
                            }
                            return merged.length >= 2 ? merged : null;
                        } catch (err) {
                            console.error("Routing error:", err);
                            return null;
                        }
                    };

                    // Draw a straight line immediately, then try to improve it with road routing.
                    drawStraightLine();
                    
                    buildRoadRoute(path)
                        .then((roadPath) => {
                            if (!roadPath) return;
                            if (routeLine) {
                                map.removeLayer(routeLine);
                                routeLine = null;
                            }
                            if (routeLineCasing) {
                                map.removeLayer(routeLineCasing);
                                routeLineCasing = null;
                            }
                            routeLineCasing = L.polyline(roadPath, {
                                color: '#0f172a',
                                weight: 9,
                                opacity: 0.15,
                                lineCap: 'round',
                                lineJoin: 'round',
                            }).addTo(map);
                            routeLine = L.polyline(roadPath, {
                                color: '#94a3b8',
                                weight: 4,
                                opacity: 0.5,
                                lineCap: 'round',
                                lineJoin: 'round',
                            }).addTo(map);
                        })
                        .catch(() => {});
                        
                    // Build active segment route if exists
                    if (activeSegment.length >= 2) {
                        buildRoadRoute(activeSegment).then((activeRoadPath) => {
                            if (!activeRoadPath) return;
                            if (activeStraightLine) map.removeLayer(activeStraightLine);
                            if (activeStraightLineCasing) map.removeLayer(activeStraightLineCasing);
                            
                            L.polyline(activeRoadPath, { color: '#0f172a', weight: 8, opacity: 0.3, lineCap: 'round', lineJoin: 'round' }).addTo(map);
                            L.polyline(activeRoadPath, { color: '#2563eb', weight: 6, opacity: 1, lineCap: 'round', lineJoin: 'round' }).addTo(map);
                        }).catch(() => {});
                    }

                    map.fitBounds(bounds.pad(0.1));
                } else {
                    document.getElementById('map').innerHTML = '<p class="text-gray-500 text-center py-4">No route data available to display on the map.</p>';
                }

                // Call the enhanced location requester
                requestLocationPermission();
            } catch (error) {
                console.error("Failed to initialize map:", error);
                document.getElementById('map').innerHTML = '<p class="text-red-600 text-center py-4">Failed to load map. Please check your internet connection.</p>';
            }
        }

        /* ENHANCED LOCATION HANDLING
           1. First, define what happens when we successfully get location.
           2. Then, try High Accuracy. If it fails, fallback to Low Accuracy.
        */
        
        function handlePositionSuccess(position) {
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
        }

        function requestLocationPermission() {
            if (!navigator.geolocation) {
                console.error("Geolocation not supported by this browser.");
                showLocationError("Geolocation not supported by this browser.");
                return;
            }

            // ATTEMPT 1: High Accuracy (GPS)
            // Timeout set to 10 seconds.
            const highAccuracyOptions = { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 };

            navigator.geolocation.getCurrentPosition(
                handlePositionSuccess, // Success on 1st try
                (error) => {
                    // IF ATTEMPT 1 FAILS:
                    console.warn("High accuracy location failed (" + error.message + "). Trying low accuracy...");
                    
                    // ATTEMPT 2: Low Accuracy (WiFi/Cell/IP)
                    // Timeout increased to 20 seconds. maximumAge allowed (cached result).
                    const lowAccuracyOptions = { enableHighAccuracy: false, timeout: 20000, maximumAge: 60000 };

                    navigator.geolocation.getCurrentPosition(
                        handlePositionSuccess, // Success on 2nd try
                        (finalError) => {
                            // IF ATTEMPT 2 ALSO FAILS:
                            console.error("Geolocation finally failed:", finalError);
                            
                            let msg = "Unable to retrieve your location.";
                            if (finalError.code === 1) msg = "Location permission denied. Please enable in settings.";
                            if (finalError.code === 2) msg = "Location unavailable. Check GPS/WiFi.";
                            if (finalError.code === 3) msg = "Location request timed out.";
                            
                            showLocationError(msg);
                        },
                        lowAccuracyOptions
                    );
                },
                highAccuracyOptions
            );
        }

        function showLocationError(message) {
            // Create or update a non-intrusive popup for location errors
            let popup = document.getElementById('locationErrorPopup');
            if (!popup) {
                popup = document.createElement('div');
                popup.id = 'locationErrorPopup';
                popup.className = 'popup hidden';
                // Add red styling for error
                popup.style.borderLeft = "4px solid #ef4444"; 
                popup.innerHTML = `
                    <i class="fa-solid fa-exclamation-triangle icon" style="color: #ef4444;"></i>
                    <span class="message">${message}</span>
                    <span id="dismissLocationBtn" class="dismiss">Dismiss</span>
                `;
                document.body.appendChild(popup);
            } else {
                popup.querySelector('.message').textContent = message;
            }

            // Show popup with animation
            popup.classList.remove('hidden');
            popup.style.animation = 'slideIn 0.5s ease-out forwards';

            // Dismiss logic
            const dismissBtn = document.getElementById('dismissLocationBtn');
            if (dismissBtn) {
                dismissBtn.addEventListener('click', () => {
                    popup.style.animation = 'slideOutLeft 0.5s ease-out forwards';
                    setTimeout(() => popup.classList.add('hidden'), 500);
                });
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const forwardBtn = document.getElementById("forwardBtn");
            const reverseBtn = document.getElementById("reverseBtn");
            const goToTopBtn = document.getElementById("go-to-top-btn");
            const viewMapBtn = document.getElementById("viewMapBtn");
            const busID = "<?php echo htmlspecialchars($busID); ?>";
            const notificationPopup = document.getElementById("notificationPopup");
            const dismissBtn = document.getElementById("dismissBtn");

            if (notificationPopup && dismissBtn) {
                let dismissDirection = 'left'; // Default slide direction

                // Show popup on page load only if bus data exists
                if (notificationPopup) {
                    setTimeout(() => {
                        notificationPopup.classList.remove("hidden");
                        notificationPopup.classList.add("popup");
                        notificationPopup.style.animation = 'slideIn 0.5s ease-out forwards';
                    }, 500); // Delay to ensure DOM is ready
                }

                // Dismiss popup with animation
                dismissBtn.addEventListener("click", () => {
                    const animation = dismissDirection === 'left' ? 'slideOutLeft' :
                                    dismissDirection === 'right' ? 'slideOutRight' :
                                    'slideOutTop';
                    notificationPopup.style.animation = `${animation} 0.5s ease-out forwards`;
                    setTimeout(() => {
                        notificationPopup.classList.add("hidden");
                        notificationPopup.style.animation = '';
                    }, 500);

                    // Cycle through dismiss directions
                    dismissDirection = dismissDirection === 'left' ? 'right' :
                                    dismissDirection === 'right' ? 'top' : 'left';
                });
            }

            if (forwardBtn) {
                forwardBtn.addEventListener("click", () => {
                    window.location.href = `bus_details.php?BusID=${busID}&Direction=Forward`;
                });
            }
            if (reverseBtn) {
                reverseBtn.addEventListener("click", () => {
                    window.location.href = `bus_details.php?BusID=${busID}&Direction=Reverse`;
                });
            }

            if (viewMapBtn) {
                viewMapBtn.addEventListener("click", () => {
                    const mapDiv = document.getElementById("map");
                    const closeBtn = document.getElementById("closeMapBtn");
                    
                    // Add fullscreen class
                    mapDiv.classList.add("map-fullscreen");
                    closeBtn.classList.remove("hidden");
                    
                    // Prevent body scrolling
                    document.body.style.overflow = "hidden";
                    
                    // Force Leaflet to recalculate size after transition
                    setTimeout(() => {
                        if (map) map.invalidateSize();
                    }, 100);
                });
            }

            const closeMapBtn = document.getElementById("closeMapBtn");
            if (closeMapBtn) {
                closeMapBtn.addEventListener("click", (e) => {
                    e.stopPropagation(); // Prevent map click events
                    const mapDiv = document.getElementById("map");
                    
                    mapDiv.classList.remove("map-fullscreen");
                    closeMapBtn.classList.add("hidden");
                    
                    document.body.style.overflow = "";
                    
                    setTimeout(() => {
                        if (map) map.invalidateSize();
                        // Scroll back to map section smoothly
                        document.getElementById("route-map-section").scrollIntoView({ behavior: "smooth" });
                    }, 100);
                });
            }

            if (goToTopBtn) {
                window.addEventListener("scroll", () => {
                    if (window.scrollY > 300) {
                        goToTopBtn.classList.add("show");
                    } else {
                        goToTopBtn.classList.remove("show");
                    }
                });
                goToTopBtn.addEventListener("click", () => {
                    window.scrollTo({ top: 0, behavior: "smooth" });
                });
            }

            const highlightTarget = document.getElementById("gate-from-highlight");
            if (highlightTarget) {
                setTimeout(() => {
                    highlightTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
        });

        // Initialize Leaflet map on load
        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>