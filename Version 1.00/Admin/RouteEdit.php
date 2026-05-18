<?php
// Start output buffering to prevent unintended output
ob_start();

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

// Handle AJAX requests for adding and deleting gates
if (isset($_GET['action']) && in_array($_GET['action'], ['addGate', 'deleteGate'])) {
    $action = $_GET['action'];

    // Clean any output buffer so far to ensure JSON response is clean
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');

    if ($action == 'addGate') {
        if (!isset($_GET['gateID']) || !isset($_GET['insertType'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            exit();
        }

        $gateID = mysqli_real_escape_string($connect, $_GET['gateID']);
        $insertType = $_GET['insertType']; // 'before', 'after', or 'end'
        $relativePosition = isset($_GET['relativePosition']) ? (int)$_GET['relativePosition'] : null;

        if (!mysqli_begin_transaction($connect)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to start transaction: ' . mysqli_error($connect)]);
            exit();
        }

        try {
            // Validate GateID existence
            $gateCheckQuery = "SELECT GateID FROM gate WHERE GateID = '$gateID'";
            $gateCheckResult = mysqli_query($connect, $gateCheckQuery);
            if (mysqli_num_rows($gateCheckResult) == 0) {
                throw new Exception("GateID '$gateID' does not exist");
            }

            // Determine the new position
            if ($insertType == 'end') {
                $maxPosQuery = "SELECT MAX(Position) as maxPos FROM route_gate WHERE RouteID = '$RouteID'";
                $maxPosResult = mysqli_query($connect, $maxPosQuery);
                $maxPos = mysqli_fetch_assoc($maxPosResult)['maxPos'] ?? 0;
                $newPosition = $maxPos + 1;
            } else {
                if ($relativePosition === null) {
                    throw new Exception("Relative Position is required for insertType '$insertType'");
                }

                if ($insertType == 'before') {
                    $newPosition = $relativePosition;
                } else { // 'after'
                    $newPosition = $relativePosition + 1;
                }

                // Shift gates at or after newPosition
                $shiftQuery = "UPDATE route_gate SET Position = Position + 1 WHERE RouteID = '$RouteID' AND Position >= $newPosition ORDER BY Position DESC";
                if (!mysqli_query($connect, $shiftQuery)) {
                    throw new Exception("Error shifting gates: " . mysqli_error($connect));
                }
            }

            // Insert the new gate
            $insertQuery = "INSERT INTO route_gate (RouteID, GateID, Position) VALUES ('$RouteID', '$gateID', $newPosition)";
            if (!mysqli_query($connect, $insertQuery)) {
                throw new Exception("Error inserting gate: " . mysqli_error($connect));
            }

            mysqli_commit($connect);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($action == 'deleteGate') {
        if (!isset($_GET['position'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing position parameter']);
            exit();
        }

        $position = (int)$_GET['position'];

        if (!mysqli_begin_transaction($connect)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to start transaction: ' . mysqli_error($connect)]);
            exit();
        }

        try {
            // Delete the gate at the exact position
            $deleteQuery = "DELETE FROM route_gate WHERE RouteID = '$RouteID' AND Position = $position";
            if (!mysqli_query($connect, $deleteQuery)) {
                throw new Exception("Error deleting gate: " . mysqli_error($connect));
            }

            // Shift remaining gates
            $shiftQuery = "UPDATE route_gate SET Position = Position - 1 WHERE RouteID = '$RouteID' AND Position > $position ORDER BY Position ASC";
            if (!mysqli_query($connect, $shiftQuery)) {
                throw new Exception("Error shifting gates: " . mysqli_error($connect));
            }

            mysqli_commit($connect);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    }
}

// Fetch route details
$query = "
    SELECT route.RouteID, bus.BusNo, admin.AdminName
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

// Fetch gate list for the route, ordered by Position, including Road
$gateQuery = "
    SELECT gate.GateID, gate.GateName, gate.Road, route_gate.Position
    FROM route_gate
    LEFT JOIN gate ON route_gate.GateID = gate.GateID
    WHERE route_gate.RouteID = '$RouteID'
    ORDER BY route_gate.Position ASC
";
$gateResult = mysqli_query($connect, $gateQuery);

$gateData = [];
while ($row = mysqli_fetch_assoc($gateResult)) {
    $gateData[] = $row;
}

// Fetch all available gates for dropdown is now handled by ajax_get_gates.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Route and Gate List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
        .action-btn {
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0,0,0,0.1);
        }
        /* Custom Select2 styling to match Tailwind */
        .select2-container--default .select2-selection--single {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }
        .select2-container--default .select2-selection--single:focus,
        .select2-container--open .select2-selection--single {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
            background-color: #ffffff;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #334155;
            font-weight: 500;
            padding-left: 1rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 3.5rem;
            right: 0.5rem;
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
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500 tracking-tight">Edit Route</h1>
                    </div>
                    <p class="text-slate-500 font-medium ml-2 sm:ml-13 pl-10 sm:pl-3">Modify gates and configuration for this route.</p>
                </div>
                
                <div class="flex gap-3 w-full md:w-auto sm:ml-0">
                    <a href="ViewRouteAdmin.php?RouteID=<?php echo urlencode($routeData['RouteID']); ?>" 
                       class="action-btn flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold rounded-xl flex items-center justify-center gap-2 shadow-md hover:from-emerald-600 hover:to-teal-700">
                        <i class="fas fa-eye"></i> View Route
                    </a>
                </div>
            </div>

            <!-- Route Details Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <!-- Route ID Card -->
                <div class="info-card rounded-2xl p-6 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner border border-indigo-100 flex-shrink-0">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Route ID</p>
                        <p class="text-lg font-extrabold text-slate-800 truncate" title="<?php echo htmlspecialchars($routeData['RouteID']); ?>"><?php echo htmlspecialchars($routeData['RouteID']); ?></p>
                    </div>
                </div>

                <!-- Bus Number Card -->
                <div class="info-card rounded-2xl p-6 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner border border-emerald-100 flex-shrink-0">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Assigned Bus</p>
                        <p class="text-lg font-extrabold text-slate-800 truncate" title="<?php echo htmlspecialchars($routeData['BusNo']); ?>"><?php echo htmlspecialchars($routeData['BusNo']); ?></p>
                    </div>
                </div>

                <!-- Admin Card -->
                <div class="info-card rounded-2xl p-6 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner border border-purple-100 flex-shrink-0">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($routeData['AdminName']); ?>&background=f3e8ff&color=9333ea&size=48" alt="Avatar" class="w-full h-full rounded-xl">
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Created By</p>
                        <p class="text-lg font-extrabold text-slate-800 truncate" title="<?php echo htmlspecialchars($routeData['AdminName']); ?>"><?php echo htmlspecialchars($routeData['AdminName']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Gate List Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-10">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-list-ol text-indigo-500"></i> Active Gates
                        <span class="ml-2 text-xs font-bold bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full"><?php echo count($gateData); ?></span>
                    </h2>
                    
                    <div class="relative w-full md:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-slate-400"></i>
                        </div>
                        <input type="text" id="search-input" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow text-sm" placeholder="Search gates by name or road..." onkeyup="searchGates()">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200" id="gate-table">
                        <thead class="bg-white">
                            <tr>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Gate Name</th>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Road</th>
                                <th class="py-4 px-6 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($gateData as $gate) { ?>
                                <tr class="group hover:bg-slate-50 transition-colors" data-gate-id="<?php echo htmlspecialchars($gate['GateID']); ?>" data-position="<?php echo $gate['Position']; ?>">
                                    <td class="py-4 px-6 whitespace-nowrap text-sm font-bold text-slate-400">
                                        <?php echo sprintf('%02d', $gate['Position']); ?>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-700 gate-name">
                                        <?php echo htmlspecialchars($gate['GateName']); ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm font-medium text-slate-500 gate-road">
                                        <i class="fas fa-road mr-2 text-slate-300"></i><?php echo htmlspecialchars($gate['Road']); ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-right">
                                        <button class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white inline-flex items-center justify-center transition-colors shadow-sm border border-rose-100 hover:border-rose-500" 
                                                onclick="deleteGate('<?php echo htmlspecialchars($gate['GateID']); ?>', <?php echo $gate['Position']; ?>)" title="Remove Gate">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if (empty($gateData)) { ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-route text-4xl text-slate-200 mb-3"></i>
                                            <p class="font-medium text-lg">No gates added yet</p>
                                            <p class="text-sm mt-1">Use the form below to add gates to this route.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add New Gate Section -->
            <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-6 shadow-inner">
                <h2 class="text-lg font-bold text-indigo-900 mb-4 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-indigo-500"></i> Append New Gate
                </h2>
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Gate Selection with Select2 -->
                    <div class="flex-grow">
                        <select id="gateSelect" class="w-full">
                            <option value="">Search and select a gate...</option>
                        </select>
                    </div>
                    <!-- Insert Type Dropdown -->
                    <div class="w-full md:w-40">
                        <select id="insert-type" class="w-full px-4 py-3.5 bg-white border border-slate-200 text-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow text-sm" onchange="toggleTargetGate()">
                            <option value="end">At the End</option>
                            <option value="before">Before</option>
                            <option value="after">After</option>
                        </select>
                    </div>
                    <!-- Target Gate Dropdown -->
                    <div class="w-full md:w-56 hidden" id="target-gate-container">
                        <select id="target-gate" class="w-full px-4 py-3.5 bg-white border border-slate-200 text-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow text-sm">
                            <?php foreach ($gateData as $gate) { ?>
                                <option value="<?php echo $gate['Position']; ?>"><?php echo htmlspecialchars($gate['GateName']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <button class="action-btn px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-bold rounded-xl flex items-center justify-center gap-2 shadow-md hover:from-indigo-700 hover:to-blue-700 whitespace-nowrap" onclick="addGate()">
                        <i class="fas fa-plus"></i> Add Gate
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Initialize Select2 on the gate dropdown
        $(document).ready(function() {
            $('#gateSelect').select2({
                placeholder: "Type to search gates...",
                allowClear: true,
                width: '100%',
                ajax: {
                    url: 'ajax_get_gates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '', // search term
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
            // Ensure the Select2 is ready
        });

        function deleteGate(gateID, position) {
            if (confirm('Are you sure you want to delete this gate from the route?')) {
                fetch(`RouteEdit.php?action=deleteGate&position=${position}&RouteID=<?php echo $RouteID; ?>`, { method: 'GET' })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error('Server error: ' + text);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.reload();
                        } else {
                            alert('Failed to delete gate: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Error deleting gate: ' + error.message);
                    });
            }
        }

        function addGate() {
            const gateSelect = document.getElementById("gateSelect");
            const gateID = gateSelect.value;
            console.log("Selected GateID:", gateID); // Debug log

            const insertTypeSelect = document.getElementById("insert-type");
            const insertType = insertTypeSelect.value;
            
            const targetGateSelect = document.getElementById("target-gate");
            const relativePosition = targetGateSelect.value;

            if (!gateID) {
                alert("Please select a gate");
                return;
            }

            if ((insertType === 'before' || insertType === 'after') && !relativePosition) {
                alert("Please select a target gate for insertion");
                return;
            }

            fetch(`RouteEdit.php?action=addGate&gateID=${gateID}&RouteID=<?php echo $RouteID; ?>&insertType=${insertType}${relativePosition && insertType !== 'end' ? '&relativePosition=' + relativePosition : ''}`, { method: 'GET' })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error('Server error: ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Failed to add gate: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error adding gate: ' + error.message);
                });
        }

        function toggleTargetGate() {
            const insertType = document.getElementById("insert-type").value;
            const targetContainer = document.getElementById("target-gate-container");
            if (insertType === 'end') {
                targetContainer.classList.add('hidden');
            } else {
                targetContainer.classList.remove('hidden');
            }
        }

        function refreshInsertPositionDropdown() {
            const targetGateSelect = document.getElementById("target-gate");
            targetGateSelect.innerHTML = '';

            const rows = document.querySelectorAll("#gate-table tbody tr");
            rows.forEach(row => {
                const position = row.getAttribute('data-position');
                const gateName = row.querySelector(".gate-name").textContent.trim();
                if (position && gateName) {
                    targetGateSelect.innerHTML += `
                        <option value="${position}">${gateName}</option>
                    `;
                }
            });
        }

        function searchGates() {
            const input = document.getElementById("search-input").value.toLowerCase();
            const rows = document.querySelectorAll("#gate-table tbody tr");

            rows.forEach(row => {
                const gateName = row.querySelector(".gate-name").textContent.toLowerCase();
                const road = row.querySelector(".gate-road").textContent.toLowerCase();
                if (gateName.includes(input) || road.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>