<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include database connection
if (!file_exists('config/database.php')) {
    die('Error: Database connection file not found.');
}
include('config/database.php');

// Public site loader (skip rapid double flash on quick submits)
if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}

// Include maintenance check
if (!file_exists('includes/maintenance_check.php')) {
    die('Error: Maintenance check file not found.');
}
include('includes/maintenance_check.php');

// Check for active maintenance
checkMaintenance($connect);

// Set UTF-8 encoding
mysqli_set_charset($connect, "utf8mb4");

// Include centralized analytics tracking
if (file_exists('core/analytics.php')) {
    include_once('core/analytics.php');
}
if (function_exists('logStat')) {
    logStat($connect, 'page_view', 'Destination');
}

// Initialize search variables
$fromSearch = isset($_GET['from']) ? trim(mysqli_real_escape_string($connect, $_GET['from'])) : '';
$toSearch = isset($_GET['to']) ? trim(mysqli_real_escape_string($connect, $_GET['to'])) : '';
$directResults = [];
$indirectResults = [];
$isSubmitted = isset($_GET['from']) || isset($_GET['to']);

if (!empty($fromSearch) && !empty($toSearch) && $fromSearch !== $toSearch) {
    // Step 1: Find all possible GateIDs for the 'from' and 'to' searches
    $fromGates = [];
    $toGates = [];

    $gateSearchQuery = "SELECT GateID, GateName FROM gate WHERE LOWER(GateName) LIKE LOWER(?) OR LOWER(Road) LIKE LOWER(?)";
    if ($stmt = mysqli_prepare($connect, $gateSearchQuery)) {
        $fromPattern = "%$fromSearch%";
        $toPattern = "%$toSearch%";
        
        // Search for 'from' gates
        mysqli_stmt_bind_param($stmt, "ss", $fromPattern, $fromPattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $fromGates[] = $row['GateID'];
        }

        // Search for 'to' gates
        mysqli_stmt_bind_param($stmt, "ss", $toPattern, $toPattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $toGates[] = $row['GateID'];
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log('Gate search preparation failed: ' . mysqli_error($connect));
    }

    if (!empty($fromGates) && !empty($toGates)) {
        // Direct Routes Query
        $directQuery = "
            SELECT DISTINCT
                b.BusID, 
                b.BusNo, 
                b.Color, 
                b.CardQR, 
                r.Notes,
                r.Direction,
                rg_from.GateID AS FromGateID,
                g_from.GateName AS FromGateName,
                rg_to.GateID AS ToGateID,
                g_to.GateName AS ToGateName
            FROM bus b
            JOIN route r ON b.BusID = r.BusID
            JOIN route_gate rg_from ON r.RouteID = rg_from.RouteID
            JOIN gate g_from ON rg_from.GateID = g_from.GateID
            JOIN route_gate rg_to ON r.RouteID = rg_to.RouteID
            JOIN gate g_to ON rg_to.GateID = g_to.GateID
            WHERE rg_from.GateID IN (" . implode(',', array_fill(0, count($fromGates), '?')) . ")
            AND rg_to.GateID IN (" . implode(',', array_fill(0, count($toGates), '?')) . ")
            AND rg_from.GateID != rg_to.GateID
            ORDER BY CAST(b.BusNo AS UNSIGNED) ASC
            LIMIT 5
        ";

        if ($stmt = mysqli_prepare($connect, $directQuery)) {
            $types = str_repeat('s', count($fromGates) + count($toGates));
            $params = array_merge($fromGates, $toGates);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result !== false) {
                $directResults = mysqli_fetch_all($result, MYSQLI_ASSOC);
            } else {
                error_log('Failed to fetch direct route results: ' . mysqli_error($connect));
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log('Direct route preparation failed: ' . mysqli_error($connect));
        }

        // Indirect Routes Query (if no direct routes are found)
        if (empty($directResults)) {
            mysqli_query($connect, "SET SQL_BIG_SELECTS=1");
            
            $indirectQuery = "
                SELECT DISTINCT
                    b1.BusID AS FirstBusID,
                    b1.BusNo AS FirstBusNo,
                    b1.Color AS FirstBusColor,
                    b1.CardQR AS FirstCardQR,
                    r1.Notes AS FirstNotes,
                    r1.Direction AS FirstDirection,
                    g_from.GateID AS FromGateID,
                    g_from.GateName AS FromGateName,
                    g_intermediate.GateID AS IntermediateGateID,
                    g_intermediate.GateName AS IntermediateGateName,
                    b2.BusID AS SecondBusID,
                    b2.BusNo AS SecondBusNo,
                    b2.Color AS SecondBusColor,
                    b2.CardQR AS SecondCardQR,
                    r2.Notes AS SecondNotes,
                    r2.Direction AS SecondDirection,
                    g_to.GateID AS ToGateID,
                    g_to.GateName AS ToGateName
                FROM route_gate rg_from
                JOIN gate g_from ON rg_from.GateID = g_from.GateID
                JOIN route r1 ON rg_from.RouteID = r1.RouteID
                JOIN bus b1 ON r1.BusID = b1.BusID
                JOIN route_gate rg_intermediate ON r1.RouteID = rg_intermediate.RouteID
                JOIN gate g_intermediate ON rg_intermediate.GateID = g_intermediate.GateID
                JOIN route_gate rg_to ON rg_to.GateID = g_intermediate.GateID
                JOIN route r2 ON rg_to.RouteID = r2.RouteID
                JOIN bus b2 ON r2.BusID = b2.BusID
                JOIN route_gate rg_final ON r2.RouteID = rg_final.RouteID
                JOIN gate g_to ON rg_final.GateID = g_to.GateID
                WHERE g_from.GateID IN (" . implode(',', array_fill(0, count($fromGates), '?')) . ")
                AND g_to.GateID IN (" . implode(',', array_fill(0, count($toGates), '?')) . ")
                AND g_from.GateID != g_intermediate.GateID
                AND g_intermediate.GateID != g_to.GateID
                AND b1.BusID != b2.BusID
                ORDER BY CAST(b1.BusNo AS UNSIGNED) ASC, CAST(b2.BusNo AS UNSIGNED) ASC
                LIMIT 100
            ";

            if ($stmt = mysqli_prepare($connect, $indirectQuery)) {
                $types = str_repeat('s', count($fromGates) + count($toGates));
                $params = array_merge($fromGates, $toGates);
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($result !== false) {
                    $rawIndirectResults = mysqli_fetch_all($result, MYSQLI_ASSOC);
                    
                    // Deduplicate by FirstBusID and SecondBusID combination
                    $seenCombos = [];
                    foreach ($rawIndirectResults as $route) {
                        $comboKey = $route['FirstBusID'] . '-' . $route['SecondBusID'];
                        if (!isset($seenCombos[$comboKey])) {
                            $seenCombos[$comboKey] = true;
                            $indirectResults[] = $route;
                            
                            // Limit to 5 unique indirect routes
                            if (count($indirectResults) >= 5) {
                                break;
                            }
                        }
                    }
                } else {
                    error_log('Failed to fetch indirect route results: ' . mysqli_error($connect));
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log('Indirect route preparation failed: ' . mysqli_error($connect));
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
    <meta name="description" content="Find the best Yangon Bus Service (YBS) routes to your destination. Plan your public transport journey in Yangon efficiently.">
    <meta name="keywords" content="YBS Destination, Yangon Bus Service Route Planner, YBS routes, Yangon Public Transport, Yangon Bus">
    <title>Find Your Destination - YBS Hub | Yangon Bus Service</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
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
        .ui-menu-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
        }
        .ui-menu-item:hover {
            background-color: #dbeafe;
        }
        .ui-state-active {
            background-color: #3b82f6;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased">
    <?php 
    if (!file_exists('includes/header.php')) {
        die('Error: Header file not found.');
    }
    include('includes/header.php'); 
    ?>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <section class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center flex items-center justify-center">
                <i class="fas fa-route mr-2 text-blue-500"></i> လမ်းကြောင်းရှာဖွေရန်
            </h1>
            <form method="GET" action="Destination.php" class="bg-white p-6 rounded-xl shadow-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative">
                        <div class="flex justify-between items-center mb-2 px-2">
                            <label class="block text-sm font-bold text-gray-700">From</label>
                            <button type="button" id="btn-nearest-gate" class="text-xs text-blue-600 hover:text-blue-800 hover:bg-blue-100 flex items-center bg-blue-50 px-2 py-1 rounded transition-colors duration-200">
                                <i class="fas fa-location-arrow mr-1"></i> Nearest Gate
                            </button>
                        </div>
                        <input 
                            type="text" 
                            name="from" 
                            id="from-gate" 
                            placeholder="Enter Starting Gate" 
                            value="<?php echo htmlspecialchars($fromSearch); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                        <i class="fas fa-search absolute right-3 top-12 text-gray-400"></i>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-bold text-gray-700 mb-2" style="padding-left: 10px;">To</label>
                        <input 
                            type="text" 
                            name="to" 
                            id="to-gate" 
                            placeholder="Enter Destination Gate" 
                            value="<?php echo htmlspecialchars($toSearch); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                        <i class="fas fa-search absolute right-3 top-12 text-gray-400"></i>
                    </div>
                </div>
                <div class="mt-6 flex justify-center">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors duration-300 flex items-center">
                        <i class="fas fa-search mr-2"></i> ကားနံပါတ်ရှာဖွေပါ
                    </button>
                </div>
            </form>
        </section>

        <?php if ($isSubmitted && (empty($fromSearch) || empty($toSearch))): ?>
            <section class="mb-8">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <div>
                        <h2 class="text-lg font-semibold">မှားယွင်းနေသောအချက်အလက်</h2>
                        <p class="mt-1">စီးလိုသည့်ဂိတ် နှင့် သွားလိုသည့်ဂိတ်အား ထည့်သွင်းမှ ရှာဖွေလို့ရမည်။</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($fromSearch) && !empty($toSearch)): ?>
            <section>
                <h2 class="text-2xl text-gray-800 mb-11"><b><?php echo htmlspecialchars($fromSearch); ?></b> မှ <b><?php echo htmlspecialchars($toSearch); ?></b> သို့ ‌ရောက်ရှိနိုင်မည့်လမ်းကြောင်းများ</h2>

                <?php if (!empty($directResults)): ?>
                    <h3 class="text-xl font-bold text-gray-700 mb-4">တိုက်ရိုက်လမ်းကြောင်း</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <?php foreach ($directResults as $bus): ?>
                            <a href="bus_details.php?BusID=<?php echo htmlspecialchars($bus['BusID']); ?>&Direction=<?php echo htmlspecialchars($bus['Direction']); ?>" 
                               class="block bg-white rounded-lg shadow p-6 hover:shadow-xl transition-shadow duration-300">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold text-white"
                                         style="background-color: <?php echo htmlspecialchars($bus['Color'] ?? '#000'); ?>;">
                                        <?php echo htmlspecialchars($bus['BusNo']); ?>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($bus['Notes'] ?? 'No Notes Available'); ?></h3>
                                        <p class="text-sm text-gray-600">Direction: <?php echo htmlspecialchars($bus['Direction']); ?></p>
                                        <p class="text-sm text-gray-600">Card/QR: <?php echo $bus['CardQR'] == 'Yes' ? 'Yes' : 'No'; ?></p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Ride Bus No. <?php echo htmlspecialchars($bus['BusNo']); ?> from <?php echo htmlspecialchars($bus['FromGateName']); ?> to <?php echo htmlspecialchars($bus['ToGateName']); ?>.
                                        </p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($indirectResults)): ?>
                    <h3 class="text-xl font-bold text-gray-700 mb-4">ထပ်ဆင့်လမ်းကြောင်း</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($indirectResults as $route): ?>
                            <div class="bg-white rounded-xl shadow p-6 hover:shadow-xl transition-shadow duration-300 border border-gray-100">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="text-sm text-gray-600">
                                        <span class="font-semibold text-gray-800">2-step route</span>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold">
                                            Transfer at <?php echo htmlspecialchars($route['IntermediateGateName']); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Timeline -->
                                <div class="mt-5">

                                    <!-- Step 1 -->
                                    <div class="pb-5">
                                        <div class="flex items-start gap-3">
                                            <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold text-white"
                                                 style="background-color: <?php echo htmlspecialchars($route['FirstBusColor'] ?? '#000'); ?>;">
                                                <?php echo htmlspecialchars($route['FirstBusNo']); ?>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <div class="font-bold text-gray-800 truncate"><?php echo htmlspecialchars($route['FirstNotes'] ?? 'No Notes Available'); ?></div>
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                                                        <?php echo htmlspecialchars($route['FirstDirection']); ?>
                                                    </span>
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?php echo ($route['FirstCardQR'] ?? 'No') === 'Yes' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'; ?>">
                                                        Card/QR: <?php echo ($route['FirstCardQR'] ?? 'No') === 'Yes' ? 'Yes' : 'No'; ?>
                                                    </span>
                                                </div>
                                                <div class="mt-2 text-sm text-gray-700">
                                                    <span class="font-semibold"><?php echo htmlspecialchars($route['FromGateName']); ?></span>
                                                    <span class="mx-2 text-gray-300">→</span>
                                                    <span class="font-semibold"><?php echo htmlspecialchars($route['IntermediateGateName']); ?></span>
                                                </div>
                                                <div class="mt-3">
                                                    <a href="bus_details.php?BusID=<?php echo htmlspecialchars($route['FirstBusID']); ?>&Direction=<?php echo htmlspecialchars($route['FirstDirection']); ?>"
                                                       class="inline-flex items-center justify-center text-sm font-semibold px-3 py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                                        View Bus <?php echo htmlspecialchars($route['FirstBusNo']); ?> details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div>
                                        <div class="flex items-start gap-3">
                                            <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold text-white"
                                                 style="background-color: <?php echo htmlspecialchars($route['SecondBusColor'] ?? '#000'); ?>;">
                                                <?php echo htmlspecialchars($route['SecondBusNo']); ?>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <div class="font-bold text-gray-800 truncate"><?php echo htmlspecialchars($route['SecondNotes'] ?? 'No Notes Available'); ?></div>
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                                                        <?php echo htmlspecialchars($route['SecondDirection']); ?>
                                                    </span>
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?php echo ($route['SecondCardQR'] ?? 'No') === 'Yes' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'; ?>">
                                                        Card/QR: <?php echo ($route['SecondCardQR'] ?? 'No') === 'Yes' ? 'Yes' : 'No'; ?>
                                                    </span>
                                                </div>
                                                <div class="mt-2 text-sm text-gray-700">
                                                    <span class="font-semibold"><?php echo htmlspecialchars($route['IntermediateGateName']); ?></span>
                                                    <span class="mx-2 text-gray-300">→</span>
                                                    <span class="font-semibold"><?php echo htmlspecialchars($route['ToGateName']); ?></span>
                                                </div>
                                                <div class="mt-3">
                                                    <a href="bus_details.php?BusID=<?php echo htmlspecialchars($route['SecondBusID']); ?>&Direction=<?php echo htmlspecialchars($route['SecondDirection']); ?>"
                                                       class="inline-flex items-center justify-center text-sm font-semibold px-3 py-2 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                                        View Bus <?php echo htmlspecialchars($route['SecondBusNo']); ?> details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (empty($directResults)): ?>
                    <p class="text-center text-gray-600 flex items-center justify-center space-x-2">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-lg"></i>
                        <span>လမ်းကြောင်းရှာမတွေ့ပါ။ နီးစပ်ရာဂိတ်တစ်ခုဖြင့် ထပ်မံရှာဖွေကြည့်ပါ။</span>
                    </p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php 
    if (!file_exists('includes/footer.php')) {
        die('Error: Footer file not found.');
    }
    include('includes/footer.php'); 
    ?>
    <script>
        $(document).ready(function() {
            // Nearest Gate Logic
            $('#btn-nearest-gate').click(function() {
                const btn = $(this);
                const originalText = btn.html();
                
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by your browser.');
                    return;
                }

                if (!window.isSecureContext) {
                    alert('Location access requires a secure context (HTTPS). Please use the HTTPS version of this site.');
                    return;
                }
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Finding...');

                const getLocation = (options) => new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, options);
                });

                const fetchNearestGate = (lat, lng) => $.ajax({
                    url: 'api/get_nearest_gate.php',
                    type: 'GET',
                    data: { lat: lat, lng: lng },
                    dataType: 'json'
                });

                // Fast-first strategy:
                // 1) Try coarse/cached position quickly (works better on many browsers/devices).
                // 2) Fallback to precise GPS if needed.
                const lowAccuracyOptions = { enableHighAccuracy: false, timeout: 7000, maximumAge: 300000 };
                const highAccuracyOptions = { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 };
                const getPermissionState = async () => {
                    try {
                        if (navigator.permissions && navigator.permissions.query) {
                            const result = await navigator.permissions.query({ name: 'geolocation' });
                            return result.state || 'unknown';
                        }
                    } catch (e) {
                        // Ignore unsupported permission-query failures.
                    }
                    return 'unknown';
                };
                const getPolicyState = () => {
                    try {
                        if (document.permissionsPolicy && typeof document.permissionsPolicy.allowsFeature === 'function') {
                            return document.permissionsPolicy.allowsFeature('geolocation');
                        }
                        if (document.featurePolicy && typeof document.featurePolicy.allowsFeature === 'function') {
                            return document.featurePolicy.allowsFeature('geolocation');
                        }
                    } catch (e) {
                        // ignore policy inspection errors
                    }
                    return null; // unknown
                };

                const beginLocate = () => getLocation(lowAccuracyOptions)
                    .catch(() => getLocation(highAccuracyOptions))
                    .then((position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        return fetchNearestGate(lat, lng);
                    })
                    .then((response) => {
                        if (response && response.success) {
                            $('#from-gate').val(response.gateName);

                            // Highlight + move user to "To" field for faster search flow
                            $('#from-gate')
                                .addClass('ring-2 ring-green-500')
                                .delay(900)
                                .queue(function(next){
                                    $(this).removeClass('ring-2 ring-green-500');
                                    next();
                                });
                            $('#to-gate').trigger('focus');
                        } else {
                            alert('Could not find a nearby gate: ' + ((response && response.error) ? response.error : 'Unknown error'));
                        }
                    })
                    .catch(async (error) => {
                        let errorMsg = 'Unable to retrieve your location. Please ensure location services are enabled.';
                        const permissionState = await getPermissionState();
                        const policyAllowsGeo = getPolicyState();
                        const inIframe = window.self !== window.top;

                        if (error && typeof error.code !== 'undefined') {
                            if (error.code === 1) {
                                if (permissionState === 'granted' && policyAllowsGeo === false) {
                                    errorMsg =
                                        'Geolocation is blocked by site policy (Permissions-Policy).\n\n' +
                                        'Fix on server/hosting:\n' +
                                        '- Remove geolocation restriction header\n' +
                                        '- Or allow geolocation for this origin\n\n' +
                                        'Typical bad header example:\n' +
                                        'Permissions-Policy: geolocation=()';
                                } else if (permissionState === 'granted' && inIframe) {
                                    errorMsg =
                                        'Geolocation is blocked because this page is inside an iframe/in-app browser.\n\n' +
                                        'Open this URL directly in Chrome/Safari browser (top-level tab), then try again.';
                                } else if (permissionState === 'granted' && policyAllowsGeo === true) {
                                    errorMsg =
                                        'Browser permission is granted, but runtime location is still blocked.\n\n' +
                                        'Most common causes:\n' +
                                        '- Device Location (GPS) is OFF at OS level\n' +
                                        '- Browser/OS privacy mode is blocking location\n' +
                                        '- Extension/ad-blocker is blocking geolocation API\n\n' +
                                        'Try this:\n' +
                                        '1) Turn ON phone/computer Location service\n' +
                                        '2) Re-open page in normal Chrome tab (not private mode)\n' +
                                        '3) Disable strict privacy/adblock for this site\n' +
                                        '4) Reload and try again';
                                } else {
                                    errorMsg =
                                        'Location request was blocked.\n\n' +
                                        'This is not always a real "user denied" case. It can also happen when:\n' +
                                        '- the site permission is blocked in the browser\n' +
                                        '- the page is opened inside an in-app browser / webview\n' +
                                        '- geolocation is blocked by browser policy or iframe policy\n' +
                                        '- the browser has not actually granted location to this domain';
                                }

                                errorMsg += '\n\nDebug info:\n' +
                                    `- Browser message: ${error.message || 'Unavailable'}\n` +
                                    `- Permission state: ${permissionState}\n` +
                                    `- Permissions-Policy allows geolocation: ${policyAllowsGeo === null ? 'unknown' : (policyAllowsGeo ? 'yes' : 'no')}\n` +
                                    `- In iframe: ${inIframe ? 'yes' : 'no'}\n` +
                                    `- Secure context: ${window.isSecureContext ? 'yes' : 'no'}\n` +
                                    `- Hostname: ${window.location.hostname}`;
                            }
                            else if (error.code === 2) errorMsg = 'Location position unavailable (your device could not determine its location).';
                            else if (error.code === 3) errorMsg = 'Location request timed out. Please try again.';
                        } else if (error && error.status) {
                            errorMsg = 'Error connecting to the server to find the nearest gate.';
                        }

                        // Geolocation secure-context hint
                        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
                            errorMsg += ' Note: Geolocation requires HTTPS or localhost.';
                        }

                        console.error('Nearest Gate geolocation error:', {
                            code: error && error.code,
                            message: error && error.message,
                            permissionState: permissionState,
                            policyAllowsGeo: policyAllowsGeo,
                            secureContext: window.isSecureContext,
                            hostname: window.location.hostname,
                            protocol: window.location.protocol,
                            inIframe: inIframe
                        });

                        alert(errorMsg);
                    })
                    .finally(() => {
                        btn.prop('disabled', false).html(originalText);
                    });

                beginLocate();
            });

            $("#from-gate").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'core/autocomplete.php',
                        dataType: "json",
                        data: { term: request.term },
                        success: function(data) {
                            response(data);
                        },
                        error: function(xhr, status, error) {
                            console.error('Autocomplete error:', error);
                        }
                    });
                },
                minLength: 2,
                delay: 300
            });

            $("#to-gate").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'core/autocomplete.php',
                        dataType: "json",
                        data: { term: request.term },
                        success: function(data) {
                            response(data);
                        },
                        error: function(xhr, status, error) {
                            console.error('Autocomplete error:', error);
                        }
                    });
                },
                minLength: 2,
                delay: 300
            });
        });
    </script>
</body>
</html>