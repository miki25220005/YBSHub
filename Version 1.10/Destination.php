<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!file_exists('config/database.php')) {
    die('Error: Database connection file not found.');
}
include('config/database.php');

if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}

if (!file_exists('includes/maintenance_check.php')) {
    die('Error: Maintenance check file not found.');
}
include('includes/maintenance_check.php');

checkMaintenance($connect);
mysqli_set_charset($connect, "utf8mb4");

if (file_exists('core/analytics.php')) {
    include_once('core/analytics.php');
}
if (function_exists('logStat')) {
    logStat($connect, 'page_view', 'Destination');
}

$fromSearch = isset($_GET['from']) ? trim(htmlspecialchars($_GET['from'])) : '';
$toSearch = isset($_GET['to']) ? trim(htmlspecialchars($_GET['to'])) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Find the best Yangon Bus Service (YBS) routes to your destination. Plan your public transport journey in Yangon efficiently.">
    <meta name="keywords" content="YBS Destination, Yangon Bus Service Route Planner, YBS routes, Yangon Public Transport, Yangon Bus">
    <title>Find Your Destination - YBS Hub | Yangon Bus Service</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- SweetAlert2 for elegant alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="icon" type="image/png" href="assets/images/Logo/YBS_Web_Logo.png">
    
    <style>
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        .ui-menu-item { padding: 0.75rem 1rem; cursor: pointer; }
        .ui-menu-item:hover { background-color: #dbeafe; }
        .ui-state-active { background-color: #3b82f6; color: white; border: none !important; }
        
        /* Skeleton Animation */
        .skeleton { background: #e2e8f0; border-radius: 8px; position: relative; overflow: hidden; }
        .skeleton::after {
            content: ""; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: skeleton-loading 1.5s infinite;
        }
        @keyframes skeleton-loading { 100% { left: 100%; } }

        /* Bottom Sheet Styles */
        #bottom-sheet {
            box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
            transition: transform 0.4s cubic-bezier(0.32, 0.72, 0, 1);
        }
        #bottom-sheet.open { transform: translateY(0); }
        .drawer-handle { width: 50px; height: 5px; border-radius: 5px; background: #cbd5e1; margin: 10px auto; }
        
        /* Progress Stepper */
        .step-row { display: flex; align-items: flex-start; gap: 0; position: relative; }
        .step-icon-col { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; width: 40px; }
        .step-icon-badge { width: 36px; height: 36px; border-radius: 50%; border: 2px solid #d1d5db; background: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 4px rgba(0,0,0,0.1); flex-shrink: 0; position: relative; z-index: 1; }
        .step-connector { width: 2px; flex: 1; background: #e2e8f0; min-height: 20px; }
        .step-content-col { flex: 1; min-width: 0; padding-left: 12px; padding-bottom: 20px; }
        
        /* Map Placeholder Pattern */
        .map-placeholder {
            background-color: #f1f5f9;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased overflow-x-hidden">
    <?php 
    if (!file_exists('includes/header.php')) {
        die('Error: Header file not found.');
    }
    include('includes/header.php'); 
    ?>

    <main class="max-w-4xl mx-auto px-4 py-8 pb-24">
        <section class="mb-8 relative z-10">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center flex items-center justify-center">
                <i class="fas fa-route mr-2 text-blue-500"></i> လမ်းကြောင်းရှာဖွေရန်
            </h1>
            <form id="search-form" class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative">
                        <div class="flex justify-between items-center mb-2 px-2">
                            <label class="block text-sm font-bold text-gray-700">From</label>
                            <button type="button" id="btn-nearest-gate" class="text-xs text-blue-600 hover:text-blue-800 hover:bg-blue-100 flex items-center bg-blue-50 px-2 py-1 rounded transition-colors duration-200">
                                <i class="fas fa-location-arrow mr-1"></i> Nearest Gate
                            </button>
                        </div>
                        <input type="text" name="from" id="from-gate" placeholder="Enter Starting Gate" value="<?php echo $fromSearch; ?>" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-blue-500 transition-colors shadow-sm text-gray-800 font-medium">
                        <i class="fas fa-map-marker-alt absolute right-4 top-12 text-blue-400"></i>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-bold text-gray-700 mb-2 px-2">To</label>
                        <input type="text" name="to" id="to-gate" placeholder="Enter Destination Gate" value="<?php echo $toSearch; ?>" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-0 focus:border-blue-500 transition-colors shadow-sm text-gray-800 font-medium">
                        <i class="fas fa-flag-checkered absolute right-4 top-12 text-red-400"></i>
                    </div>
                </div>
                <div class="mt-8 flex justify-center">
                    <button type="submit" id="btn-search" class="bg-blue-600 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-blue-700 transition-all duration-300 flex items-center shadow-md hover:shadow-lg transform hover:-translate-y-0.5 w-full md:w-auto justify-center">
                        <i class="fas fa-search mr-2"></i> ရှာဖွေပါ
                    </button>
                </div>
            </form>
        </section>

        <!-- Skeleton Loader (Hidden by default) -->
        <section id="skeleton-container" class="hidden space-y-6">
            <h2 class="skeleton w-3/4 h-8 mx-auto mb-8 rounded-lg"></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="skeleton w-14 h-14 rounded-full flex-shrink-0"></div>
                    <div class="flex-1 space-y-3">
                        <div class="skeleton w-full h-5"></div>
                        <div class="skeleton w-2/3 h-4"></div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="skeleton w-14 h-14 rounded-full flex-shrink-0"></div>
                    <div class="flex-1 space-y-3">
                        <div class="skeleton w-full h-5"></div>
                        <div class="skeleton w-2/3 h-4"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Results Container -->
        <section id="results-container" class="space-y-8">
            <!-- Rendered via JS -->
        </section>
    </main>

    <!-- Bottom Sheet Drawer -->
    <div id="bottom-sheet-overlay" class="fixed inset-0 bg-black bg-opacity-40 z-40 hidden transition-opacity duration-300 opacity-0"></div>
    <div id="bottom-sheet" class="fixed inset-x-0 bottom-0 z-50 transform translate-y-full bg-white rounded-t-3xl h-[85vh] flex flex-col md:w-[600px] md:mx-auto md:h-[90vh]">
        <div class="drawer-handle cursor-pointer z-50 absolute left-1/2 transform -translate-x-1/2" id="close-drawer-handle" style="box-shadow: 0 1px 3px rgba(0,0,0,0.3);"></div>
        
        <!-- Drawer Header / Real Map -->
        <div id="drawer-map" class="relative h-64 w-full rounded-t-3xl border-b border-gray-200 z-10">
            <button id="btn-close-drawer" class="absolute top-4 right-4 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-700 hover:text-black z-[1000] transition-transform active:scale-95 border border-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Drawer Content (Stepper) -->
        <div class="flex-1 overflow-y-auto p-6" id="drawer-content">
            <!-- Populated via JS -->
        </div>
    </div>

    <?php 
    if (!file_exists('includes/footer.php')) {
        die('Error: Footer file not found.');
    }
    include('includes/footer.php'); 
    ?>

    <script>
        // Store current route data globally for the drawer
        let currentRoutes = [];

        $(document).ready(function() {
            // Auto-trigger search if params exist in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('from') && urlParams.get('to')) {
                performSearch();
            }

            $('#search-form').on('submit', function(e) {
                e.preventDefault();
                
                // Update URL quietly
                const from = $('#from-gate').val();
                const to = $('#to-gate').val();
                const newUrl = window.location.pathname + `?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
                window.history.pushState({path:newUrl}, '', newUrl);

                performSearch();
            });

            function performSearch() {
                const fromGate = $('#from-gate').val().trim();
                const toGate = $('#to-gate').val().trim();

                if (!fromGate || !toGate) {
                    Swal.fire({ icon: 'warning', title: 'Oops...', text: 'Please enter both origin and destination gates.' });
                    return;
                }

                // UI Loading State
                $('#results-container').empty();
                $('#skeleton-container').removeClass('hidden');
                $('#btn-search').prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin mr-2"></i> Searching...');

                $.ajax({
                    url: 'api/search_destination.php',
                    type: 'GET',
                    data: { from: fromGate, to: toGate },
                    dataType: 'json',
                    success: function(response) {
                        $('#skeleton-container').addClass('hidden');
                        $('#btn-search').prop('disabled', false).html('<i class="fas fa-search mr-2"></i> ရှာဖွေပါ');
                        
                        if (!response.success) {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.error });
                            return;
                        }

                        renderResults(response.direct || [], response.indirect || [], fromGate, toGate);
                    },
                    error: function() {
                        $('#skeleton-container').addClass('hidden');
                        $('#btn-search').prop('disabled', false).html('<i class="fas fa-search mr-2"></i> ရှာဖွေပါ');
                        Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Unable to reach the server. Please try again.' });
                    }
                });
            }

            function renderResults(direct, indirect, fromQuery, toQuery) {
                const container = $('#results-container');
                container.empty();
                currentRoutes = [...direct, ...indirect]; // Combine for drawer lookup

                const titleHtml = `<h2 class="text-2xl font-bold text-gray-800 text-center mb-8">Routes from <span class="text-blue-600">${fromQuery}</span> to <span class="text-red-500">${toQuery}</span></h2>`;
                container.append(titleHtml);

                if (direct.length === 0 && indirect.length === 0) {
                    container.append(`
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-2xl flex flex-col items-center justify-center text-center">
                            <i class="fas fa-route text-5xl text-yellow-400 mb-4"></i>
                            <h3 class="text-lg font-bold mb-1">No Routes Found</h3>
                            <p>We couldn't find a path between these two gates. Try selecting a nearby landmark.</p>
                        </div>
                    `);
                    return;
                }

                // Render Direct
                if (direct.length > 0) {
                    container.append(`<h3 class="text-xl font-bold text-gray-700 mb-4 flex items-center"><i class="fas fa-bolt text-yellow-500 mr-2"></i> Direct Routes</h3>`);
                    const grid = $('<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8"></div>');
                    
                    direct.forEach((route, index) => {
                        const isLoop = route.IsCrossTerminus == 1;
                        const displayDirection = isLoop ? 'Loop (Fwd & Rev)' : route.FromDirection;
                        const arrowColor = isLoop ? 'text-purple-500' : (route.FromDirection === 'Forward' ? 'text-green-500' : 'text-blue-500');
                        const arrowIcon = isLoop ? 'fa-sync-alt' : (route.FromDirection === 'Forward' ? 'fa-arrow-right' : 'fa-arrow-left');
                        const stopsCount = route.IntermediateStops.length + 2; // + Origin + Dest

                        const card = $(`
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow cursor-pointer relative overflow-hidden group" onclick="openDrawer('direct', ${index})">
                                <div class="absolute right-0 top-0 bottom-0 w-2 bg-gradient-to-b from-${isLoop ? 'purple' : (route.FromDirection === 'Forward' ? 'green' : 'blue')}-400 to-${isLoop ? 'purple' : (route.FromDirection === 'Forward' ? 'green' : 'blue')}-600"></div>
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold text-white shadow-inner flex-shrink-0" style="background-color: ${route.Color || '#333'}">
                                        ${route.BusNo}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-800 truncate">${route.Notes}</h4>
                                        <div class="flex items-center text-sm text-gray-500 mt-1 gap-2">
                                            <span class="inline-flex items-center font-medium ${arrowColor} bg-gray-50 px-2 py-0.5 rounded">
                                                <i class="fas ${arrowIcon} mr-1"></i> ${displayDirection}
                                            </span>
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded-full text-gray-600">${stopsCount} Stops</span>
                                        </div>
                                    </div>
                                    <div class="text-gray-300 group-hover:text-blue-500 transition-colors">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        `);
                        grid.append(card);
                    });
                    container.append(grid);
                }

                // Render Indirect
                if (indirect.length > 0) {
                    container.append(`<h3 class="text-xl font-bold text-gray-700 mb-4 flex items-center"><i class="fas fa-random text-purple-500 mr-2"></i> 1-Transfer Routes</h3>`);
                    const grid = $('<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"></div>');
                    
                    indirect.forEach((route, index) => {
                        const totalStops = route.FirstLegStops.length + route.SecondLegStops.length + 3;
                        const card = $(`
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow cursor-pointer relative overflow-hidden group" onclick="openDrawer('indirect', ${index + direct.length})">
                                <div class="absolute right-0 top-0 bottom-0 w-2 bg-purple-500"></div>
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-xs font-bold uppercase tracking-wider text-purple-600 bg-purple-50 px-2 py-1 rounded">Transfer Required</span>
                                    <span class="text-xs text-gray-500 font-medium">${totalStops} Total Stops</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white shadow-sm flex-shrink-0 text-sm" style="background-color: ${route.FirstBusColor || '#333'}">${route.FirstBusNo}</div>
                                    <div class="text-gray-400 text-xs"><i class="fas fa-exchange-alt"></i></div>
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white shadow-sm flex-shrink-0 text-sm" style="background-color: ${route.SecondBusColor || '#333'}">${route.SecondBusNo}</div>
                                    
                                    <div class="ml-auto text-gray-300 group-hover:text-purple-500 transition-colors">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                                <div class="mt-3 text-sm text-gray-600 truncate">
                                    Transfer at <span class="font-bold text-gray-800">${route.IntermediateGateName}</span>
                                </div>
                            </div>
                        `);
                        grid.append(card);
                    });
                    container.append(grid);
                }
            }

            // Autocomplete Setup
            function setupAutocomplete(selector) {
                $(selector).autocomplete({
                    source: function(request, response) {
                        $.ajax({
                            url: 'core/autocomplete.php',
                            dataType: "json",
                            data: { term: request.term },
                            success: function(data) { response(data); }
                        });
                    },
                    minLength: 2, delay: 300
                });
            }
            setupAutocomplete("#from-gate");
            setupAutocomplete("#to-gate");

            // Drawer Logic
            $('#btn-close-drawer, #bottom-sheet-overlay, #close-drawer-handle').on('click', closeDrawer);

            // Nearest Gate (simplified for length, using sweetalert)
            $('#btn-nearest-gate').click(function() {
                if (!navigator.geolocation) return Swal.fire('Error', 'Geolocation not supported', 'error');
                const btn = $(this);
                const orig = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        $.ajax({
                            url: 'api/get_nearest_gate.php', type: 'GET',
                            data: { lat: pos.coords.latitude, lng: pos.coords.longitude },
                            dataType: 'json',
                            success: function(res) {
                                btn.html(orig).prop('disabled', false);
                                if(res.success) {
                                    $('#from-gate').val(res.gateName);
                                    $('#to-gate').focus();
                                } else { Swal.fire('Error', res.error, 'error'); }
                            },
                            error: () => { btn.html(orig).prop('disabled', false); Swal.fire('Error', 'Server error', 'error'); }
                        });
                    },
                    err => { btn.html(orig).prop('disabled', false); Swal.fire('Error', 'Location blocked or unavailable', 'error'); }
                );
            });
        });

        // Global functions for Drawer
        let drawerMap = null;
        let routeLayerGroup = null;

        function initMap() {
            if (!drawerMap) {
                drawerMap = L.map('drawer-map', { zoomControl: false }).setView([16.8, 96.15], 13);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                    maxZoom: 20
                }).addTo(drawerMap);
                routeLayerGroup = L.layerGroup().addTo(drawerMap);
            }
        }

        window.openDrawer = function(type, index) {
            const route = currentRoutes[index];
            if (!route) return;
            
            const content = $('#drawer-content');
            content.empty();

            if (type === 'direct') {
                const isLoop = route.IsCrossTerminus == 1;
                const linkDir = isLoop ? 'Loop' : route.FromDirection;
                const link = `bus_details.php?BusID=${route.BusID}&Direction=${linkDir}&from_gate=${route.FromGateID}&to_gate=${route.ToGateID}`;

                content.append(`
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-black text-gray-800">Bus ${route.BusNo} ${isLoop ? '<span class="text-purple-500 text-sm ml-2"><i class="fas fa-sync-alt"></i> Loop</span>' : ''}</h3>
                            <p class="text-sm text-gray-500">${route.Notes}</p>
                        </div>
                        <a href="${link}" class="text-blue-600 bg-blue-50 px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-100 transition">Full Route</a>
                    </div>
                `);

                const stepper = $(`<div class="stepper-wrap"></div>`);

                stepper.append(buildStepItem('fa-map-marker-alt', `YBS ${route.BusNo} စီးရန်`, route.FromGateName, false, route.Color, link));
                if (route.IntermediateStops.length > 0) {
                    stepper.append(buildAccordion(route.IntermediateStops, route.Color));
                }
                stepper.append(buildStepItem('fa-flag-checkered', 'လမ်းဆုံး', route.ToGateName, true, route.Color));
                
                content.append(stepper);
            } else {
                content.append(`
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-xl font-black text-gray-800">Transfer Route</h3>
                        <span class="text-purple-800 bg-purple-100 border border-purple-200 shadow-sm px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">1 Transfer</span>
                    </div>
                `);

                const stepper = $(`<div class="stepper-wrap"></div>`);

                const isLoop1 = route.Leg1Cross == 1;
                const isLoop2 = route.Leg2Cross == 1;
                const linkDir1 = isLoop1 ? 'Loop' : route.FirstDirection;
                const linkDir2 = isLoop2 ? 'Loop' : route.SecondDirection;

                const link1 = `bus_details.php?BusID=${route.FirstBusID}&Direction=${linkDir1}&from_gate=${route.FromGateID}&to_gate=${route.IntermediateGateID}`;
                const link2 = `bus_details.php?BusID=${route.SecondBusID}&Direction=${linkDir2}&from_gate=${route.IntermediateGateID}&to_gate=${route.ToGateID}`;

                const boardLabel1 = `YBS ${route.FirstBusNo} စီးရန် ${isLoop1 ? '<span class="text-purple-500 text-xs ml-1"><i class="fas fa-sync-alt"></i> Loop</span>' : ''}`;
                const transferLabel2 = `YBS ${route.SecondBusNo} သို့ ပြောင်းစီးရန် ${isLoop2 ? '<span class="text-purple-500 text-xs ml-1"><i class="fas fa-sync-alt"></i> Loop</span>' : ''}`;

                stepper.append(buildStepItem('fa-map-marker-alt', boardLabel1, route.FromGateName, false, route.FirstBusColor, link1));
                if (route.FirstLegStops.length > 0) stepper.append(buildAccordion(route.FirstLegStops, route.FirstBusColor));
                stepper.append(buildStepItem('fa-exchange-alt', transferLabel2, route.IntermediateGateName, false, route.SecondBusColor, link2));
                if (route.SecondLegStops.length > 0) stepper.append(buildAccordion(route.SecondLegStops, route.SecondBusColor));
                stepper.append(buildStepItem('fa-flag-checkered', 'လမ်းဆုံး', route.ToGateName, true, route.SecondBusColor));

                content.append(stepper);
            }

            $('#bottom-sheet-overlay').removeClass('hidden');
            setTimeout(() => {
                $('#bottom-sheet-overlay').removeClass('opacity-0');
                $('#bottom-sheet').addClass('open');
                
                // Initialize and render map after drawer opens
                initMap();
                routeLayerGroup.clearLayers();
                renderMapRoute(type, route);
            }, 10);
            
            $('body').css('overflow', 'hidden');
        };

        // ── OSRM road-snapping helper (identical strategy to bus_details.php) ──
        async function buildRoadPath(coordPairs) {
            if (!coordPairs || coordPairs.length < 2) return null;
            const chunkSize = 25;
            const merged = [];
            try {
                for (let i = 0; i < coordPairs.length - 1; i += chunkSize - 1) {
                    const chunk = coordPairs.slice(i, i + chunkSize);
                    const coordsStr = chunk.map(p => `${p[1]},${p[0]}`).join(';');
                    const url = `https://router.project-osrm.org/route/v1/walking/${coordsStr}?overview=full&geometries=geojson&steps=false`;
                    const res = await fetch(url);
                    if (!res.ok) return null;
                    const data = await res.json();
                    const coords = data?.routes?.[0]?.geometry?.coordinates;
                    if (!coords || coords.length < 2) continue;
                    const latlngs = coords.map(([lng, lat]) => [lat, lng]);
                    for (const pt of latlngs) {
                        const last = merged[merged.length - 1];
                        if (!last || last[0] !== pt[0] || last[1] !== pt[1]) merged.push(pt);
                    }
                }
                return merged.length >= 2 ? merged : null;
            } catch { return null; }
        }

        // ── Draw directional arrow icons along a polyline ──
        function addArrows(map, latlngs, color, layerGroup) {
            if (latlngs.length < 2) return;
            // Place an arrow roughly every N points (more frequent = more arrows)
            const step = Math.max(4, Math.floor(latlngs.length / 5));
            for (let i = step; i < latlngs.length - 1; i += step) {
                const a = latlngs[i - 1];
                const b = latlngs[i];
                // Calculate bearing angle
                const dy = b[0] - a[0];
                const dx = (b[1] - a[1]) * Math.cos((a[0] * Math.PI) / 180);
                const angle = Math.atan2(dx, dy) * (180 / Math.PI);
                const arrowIcon = L.divIcon({
                    className: '',
                    html: `<div style="
                        width: 24px; height: 24px;
                        border-radius: 50%;
                        background: white;
                        box-shadow: 0 1px 5px rgba(0,0,0,0.35);
                        display: flex; align-items: center; justify-content: center;
                        transform: rotate(${angle}deg);
                    ">
                        <div style="
                            width:0; height:0;
                            border-left: 7px solid transparent;
                            border-right: 7px solid transparent;
                            border-bottom: 14px solid ${color};
                            opacity: 0.95;
                        "></div>
                    </div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });
                L.marker(b, { icon: arrowIcon, interactive: false }).addTo(layerGroup);
            }
        }

        // ── Bus-stop marker (small dot) ──
        const busStopIcon = L.icon({
            iconUrl: 'assets/images/bus-stop.svg',
            iconSize: [18, 18],
            iconAnchor: [9, 9],
            popupAnchor: [0, -10]
        });
        // Fallback circle if SVG missing
        function busStopCircleIcon(color) {
            return L.divIcon({
                className: '',
                html: `<div style="width:12px;height:12px;border-radius:50%;background:white;border:2.5px solid ${color || '#3b82f6'};box-shadow:0 1px 4px rgba(0,0,0,0.35);"></div>`,
                iconSize: [12, 12],
                iconAnchor: [6, 6],
                popupAnchor: [0, -8]
            });
        }

        // ── Draw a single leg (path + stops + arrows) ──
        async function drawLeg(path, allStops, color, layerGroup, bounds) {
            if (path.length < 1) return;
            path.forEach(p => bounds.extend(p));

            // Draw straight-line placeholder immediately
            const straight = L.polyline(path, {
                color,
                weight: 5,
                opacity: 0.65,
                lineCap: 'round',
                lineJoin: 'round',
                dashArray: '10 5'
            }).addTo(layerGroup);

            // Draw bus-stop markers at every gate
            allStops.forEach(s => {
                L.marker([parseFloat(s.Lat || s[0]), parseFloat(s.Lng || s[1])], {
                    icon: busStopCircleIcon(color),
                    zIndexOffset: 100
                }).addTo(layerGroup).bindPopup(s.GateName || '');
            });

            // Try OSRM road snap
            const roadPath = await buildRoadPath(path);
            if (roadPath) {
                layerGroup.removeLayer(straight);
                // Casing (dark shadow under line)
                L.polyline(roadPath, { color: '#0f172a', weight: 9, opacity: 0.15, lineCap: 'round', lineJoin: 'round' }).addTo(layerGroup);
                // Main colored line
                L.polyline(roadPath, { color, weight: 5, opacity: 0.9, lineCap: 'round', lineJoin: 'round' }).addTo(layerGroup);
                // Direction arrows
                addArrows(drawerMap, roadPath, color, layerGroup);
            } else {
                // Fallback: redraw solid and add arrows on straight path
                layerGroup.removeLayer(straight);
                L.polyline(path, { color, weight: 5, opacity: 0.9, lineCap: 'round', lineJoin: 'round' }).addTo(layerGroup);
                addArrows(drawerMap, path, color, layerGroup);
            }
        }

        // ── Origin / Destination / Transfer pin icons ──
        function pinIcon(color, faClass) {
            return L.divIcon({
                className: '',
                html: `<div style="background:${color};width:28px;height:28px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;color:white;font-size:11px;">
                           <i class="fas ${faClass}"></i>
                       </div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
                popupAnchor: [0, -14]
            });
        }

        async function renderMapRoute(type, route) {
            const bounds = L.latLngBounds();

            if (type === 'direct') {
                const path = [];
                const stops = [];

                if (route.FromLat && route.FromLng) {
                    const p = [parseFloat(route.FromLat), parseFloat(route.FromLng)];
                    path.push(p);
                    stops.push({ Lat: p[0], Lng: p[1], GateName: route.FromGateName });
                }
                route.IntermediateStops.forEach(s => {
                    if (s.Lat && s.Lng) {
                        path.push([parseFloat(s.Lat), parseFloat(s.Lng)]);
                        stops.push(s);
                    }
                });
                if (route.ToLat && route.ToLng) {
                    const p = [parseFloat(route.ToLat), parseFloat(route.ToLng)];
                    path.push(p);
                    stops.push({ Lat: p[0], Lng: p[1], GateName: route.ToGateName });
                }

                await drawLeg(path, stops, route.Color || '#3b82f6', routeLayerGroup, bounds);

                // Origin & destination pins on top
                if (path.length > 0) {
                    L.marker(path[0], { icon: pinIcon(route.Color || '#3b82f6', 'fa-map-marker-alt'), zIndexOffset: 500 })
                        .addTo(routeLayerGroup).bindPopup(route.FromGateName);
                    L.marker(path[path.length - 1], { icon: pinIcon('#dc2626', 'fa-flag-checkered'), zIndexOffset: 500 })
                        .addTo(routeLayerGroup).bindPopup(route.ToGateName);
                }
            } else {
                // ── Leg 1 ──
                const path1 = [];
                const stops1 = [];
                if (route.FromLat && route.FromLng) {
                    const p = [parseFloat(route.FromLat), parseFloat(route.FromLng)];
                    path1.push(p);
                    stops1.push({ Lat: p[0], Lng: p[1], GateName: route.FromGateName });
                }
                route.FirstLegStops.forEach(s => {
                    if (s.Lat && s.Lng) { path1.push([parseFloat(s.Lat), parseFloat(s.Lng)]); stops1.push(s); }
                });
                if (route.IntermediateLat && route.IntermediateLng) {
                    const p = [parseFloat(route.IntermediateLat), parseFloat(route.IntermediateLng)];
                    path1.push(p);
                    stops1.push({ Lat: p[0], Lng: p[1], GateName: route.IntermediateGateName });
                }

                // ── Leg 2 ──
                const path2 = [];
                const stops2 = [];
                if (route.IntermediateLat && route.IntermediateLng) {
                    const p = [parseFloat(route.IntermediateLat), parseFloat(route.IntermediateLng)];
                    path2.push(p);
                    stops2.push({ Lat: p[0], Lng: p[1], GateName: route.IntermediateGateName });
                }
                route.SecondLegStops.forEach(s => {
                    if (s.Lat && s.Lng) { path2.push([parseFloat(s.Lat), parseFloat(s.Lng)]); stops2.push(s); }
                });
                if (route.ToLat && route.ToLng) {
                    const p = [parseFloat(route.ToLat), parseFloat(route.ToLng)];
                    path2.push(p);
                    stops2.push({ Lat: p[0], Lng: p[1], GateName: route.ToGateName });
                }

                await Promise.all([
                    drawLeg(path1, stops1, route.FirstBusColor || '#3b82f6', routeLayerGroup, bounds),
                    drawLeg(path2, stops2, route.SecondBusColor || '#a855f7', routeLayerGroup, bounds)
                ]);

                // Special pins on top
                if (path1.length > 0)
                    L.marker(path1[0], { icon: pinIcon(route.FirstBusColor || '#3b82f6', 'fa-map-marker-alt'), zIndexOffset: 500 })
                        .addTo(routeLayerGroup).bindPopup(route.FromGateName);
                if (path1.length > 0 && path2.length > 0)
                    L.marker(path1[path1.length - 1], { icon: pinIcon('#f59e0b', 'fa-exchange-alt'), zIndexOffset: 500 })
                        .addTo(routeLayerGroup).bindPopup(route.IntermediateGateName + ' (ပြောင်းစီးရန်)');
                if (path2.length > 0)
                    L.marker(path2[path2.length - 1], { icon: pinIcon('#dc2626', 'fa-flag-checkered'), zIndexOffset: 500 })
                        .addTo(routeLayerGroup).bindPopup(route.ToGateName);
            }

            if (bounds.isValid()) {
                setTimeout(() => {
                    drawerMap.invalidateSize();
                    drawerMap.fitBounds(bounds, { padding: [30, 30] });
                }, 350);
            }
        }

        window.closeDrawer = function() {
            $('#bottom-sheet').removeClass('open');
            $('#bottom-sheet-overlay').addClass('opacity-0');
            setTimeout(() => {
                $('#bottom-sheet-overlay').addClass('hidden');
                $('body').css('overflow', '');
            }, 300);
        };

        window.toggleAccordion = function(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById('icon-' + id);
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                el.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        };

        // UI Helpers — 2-column flex stepper
        // Signature: buildStepItem(iconClass, label, gateName, isLast, customColor, linkUrl)
        function buildStepItem(iconClass, label, gateName, isLast = false, customColor = null, linkUrl = null) {
            const borderStyle = customColor ? `border-color:${customColor};` : '';
            const iconColor   = customColor ? `color:${customColor};` : 'color:#6b7280;';
            const connectorHtml = isLast ? '' : `<div class="step-connector"></div>`;

            const textBlock = `
                <p class="text-xs font-bold uppercase tracking-widest mb-0.5" style="${iconColor}">${label}</p>
                <h4 class="text-base font-semibold text-gray-800 leading-snug">${gateName}</h4>`;

            const contentHtml = linkUrl
                ? `<a href="${linkUrl}" class="flex-1 min-w-0 pl-3 pb-5 group hover:bg-blue-50 rounded-xl transition-colors -mr-1 pr-2 pt-0.5">
                        <div class="flex items-start justify-between">
                            <div>${textBlock}</div>
                            <i class="fas fa-chevron-right text-gray-300 text-xs mt-1 ml-2 group-hover:text-blue-400 transition-colors flex-shrink-0"></i>
                        </div>
                   </a>`
                : `<div class="flex-1 min-w-0 pl-3 pb-5 pt-0.5">${textBlock}</div>`;

            return `
                <div class="step-row">
                    <div class="step-icon-col">
                        <div class="step-icon-badge" style="${borderStyle}${customColor ? `background:white;` : ''}">
                            <i class="fas ${iconClass} text-xs" style="${iconColor}"></i>
                        </div>
                        ${connectorHtml}
                    </div>
                    ${contentHtml}
                </div>`;
        }

        function buildAccordion(stopsList, color) {
            const accId = 'acc-' + Math.random().toString(36).substr(2, 9);
            const colorHex = color || '#9ca3af';
            const mmLabel = `ကြားဂိတ် ${stopsList.length} ခု ရှိသည်`;

            const stopsHtml = stopsList.map(stop => `
                <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                    <div class="w-2 h-2 rounded-full flex-shrink-0" style="background-color:${colorHex}"></div>
                    <span class="text-sm text-gray-600 truncate">${stop.GateName}</span>
                </div>`).join('');

            return `
                <div class="step-row">
                    <div class="step-icon-col">
                        <div class="step-icon-badge" style="border-color:#d1d5db; width:28px; height:28px;">
                            <div class="w-2.5 h-2.5 rounded-full" style="background-color:${colorHex}"></div>
                        </div>
                        <div class="step-connector"></div>
                    </div>
                    <div class="flex-1 min-w-0 pl-3 pb-5 pt-0.5">
                        <button onclick="toggleAccordion('${accId}')"
                            class="w-full bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl px-4 py-2.5 flex items-center justify-between transition-colors shadow-sm">
                            <span class="text-sm font-bold text-gray-600">${mmLabel}</span>
                            <i id="icon-${accId}" class="fas fa-chevron-down text-gray-400 transition-transform duration-300 ml-2 flex-shrink-0"></i>
                        </button>
                        <div id="${accId}" class="hidden mt-2 bg-white border border-gray-100 rounded-xl p-3 shadow-inner max-h-48 overflow-y-auto">
                            ${stopsHtml}
                        </div>
                    </div>
                </div>`;
        }
    </script>
</body>
</html>