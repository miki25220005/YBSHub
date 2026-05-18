<?php
session_start();
include('../config/database.php');
include('AutoID_Functions.php');
include('RouteProcess.php');

// Redirect if the user is not logged in as an admin
if (!isset($_SESSION['AdminName']) || !isset($_SESSION['AdminID'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Add a gate to the session for a specific route
if (isset($_POST['btnAdd'])) {
    $RouteID = $_POST['txtRouteID'];
    $BusID = $_POST['cboBusID'] ?? $_POST['txtLockedBusID'];
    $Direction = $_POST['cboDirection'] ?? $_POST['txtLockedDirection'];
    $Notes = $_POST['txtRouteNotes'] ?? $_POST['txtLockedNotes'];
    $GateID = $_POST['cboGateID'];
    $AdminID = $_POST['txtAdminID'];

    if ($GateID === '') {
        echo "<script>alert('Please select a Gate.');</script>";
    } else {
        AddGateToRoute($RouteID, $BusID, $Direction, $Notes, $GateID, $AdminID);
    }
}

// Remove a gate from the session for a specific route
if (isset($_GET['action']) && $_GET['action'] === 'remove') {
    RemoveGateFromRoute($_GET['RouteID'], $_GET['GateID']);
}

// Submit the route with all gates
if (isset($_POST['btnSubmit'])) {
    SaveRouteWithGates();
    echo "<script>alert('Route and Gates submitted successfully!');</script>";
    echo "<script>window.location='RouteEntry.php';</script>";
}

// Locking logic
$lockedBusID = '';
$lockedBusName = '';
$lockedDirection = '';
$lockedNotes = '';

if (!empty($_SESSION['Route_Process'])) {
    $firstRoute = reset($_SESSION['Route_Process']);
    $lockedBusID = $firstRoute['BusID'];
    $lockedBusName = $firstRoute['BusNo'];
    $lockedDirection = $firstRoute['Direction'];
    $lockedNotes = $firstRoute['Notes'];
}

// Preselect bus from dashboard link
$preselectedBusID = isset($_GET['BusID']) ? $_GET['BusID'] : $lockedBusID;
$preselectedBusName = isset($_GET['BusNo']) ? $_GET['BusNo'] : $lockedBusName;

// Check existing directions for preselected/locked bus
$existingDirections = [];
if ($preselectedBusID || $lockedBusID) {
    $checkRoutesQuery = "SELECT Direction FROM route WHERE BusID = ? GROUP BY Direction";
    $stmt = mysqli_prepare($connect, $checkRoutesQuery);
    $busID = $preselectedBusID ?: $lockedBusID; // Assign to variable
    mysqli_stmt_bind_param($stmt, "s", $busID); // Use variable
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($dir = mysqli_fetch_assoc($result)) {
        $existingDirections[] = $dir['Direction'];
    }
    mysqli_stmt_close($stmt);
}

// Suggested direction from dashboard
$suggestedDirection = isset($_GET['SuggestedDirection']) ? $_GET['SuggestedDirection'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include jQuery (required by Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Custom Select2 styling to match Tailwind */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 40px -5px rgba(0,0,0,0.05), 0 10px 20px -5px rgba(0,0,0,0.02);
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
        .select2-container--default .select2-selection--single {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            height: 3.2rem;
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
            height: 3.2rem;
            right: 0.5rem;
        }
        .select2-dropdown {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .select2-results__option {
            padding: 0.5rem 1rem;
            color: #334155;
        }
        .select2-results__option--highlighted {
            background-color: #6366f1 !important;
            color: #fff !important;
        }
        .input-premium {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            color: #334155;
            font-weight: 500;
            transition: all 0.2s ease;
            width: 100%;
        }
        .input-premium:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
            background-color: #ffffff;
        }
        .input-premium:read-only {
            background-color: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
            border-color: #cbd5e1;
        }

    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen font-sans antialiased flex flex-col text-slate-800 transition-colors duration-300 selection:bg-indigo-200 selection:text-indigo-900">
    <?php include('../includes/admheader.php'); ?>
    <main class="flex-grow pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        
        <div class="max-w-5xl mx-auto mb-6 flex justify-between items-center animate-fade-in-up">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500 tracking-tight flex items-center gap-3">
                <i class="fas fa-route text-indigo-500"></i> Route Entry
            </h1>
            <a href="RouteListAdmin.php" class="action-btn px-5 py-2.5 bg-white text-slate-600 font-bold rounded-xl flex items-center gap-2 shadow-sm border border-slate-200 hover:text-indigo-600 hover:border-indigo-200">
                <i class="fas fa-list"></i> View All
            </a>
        </div>

        <div class="max-w-5xl mx-auto glass-card rounded-3xl p-6 sm:p-10 animate-fade-in-up">
            <form action="RouteEntry.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Route ID -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Route ID</label>
                        <input type="text" name="txtRouteID" value="<?php echo AutoID('route', 'RouteID', 'RTE-', 6); ?>" readonly
                               class="input-premium">
                    </div>

                    <!-- Bus Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Assigned Bus</label>
                        <?php if ($lockedBusID): ?>
                            <input type="text" name="txtLockedBus" value="<?php echo htmlspecialchars($lockedBusName); ?> (<?php echo htmlspecialchars($lockedBusID); ?>)" readonly
                                   class="input-premium">
                            <input type="hidden" name="txtLockedBusID" value="<?php echo htmlspecialchars($lockedBusID); ?>">
                        <?php else: ?>
                            <select name="cboBusID" id="busSelect" required class="w-full">
                                <option value="">Select Bus</option>
                                <?php
                                $busQuery = "SELECT * FROM bus";
                                $busResult = mysqli_query($connect, $busQuery);
                                while ($row = mysqli_fetch_assoc($busResult)) {
                                    $selected = ($row['BusID'] == $preselectedBusID) ? 'selected' : '';
                                    echo "<option value='{$row['BusID']}' $selected>({$row['BusID']}) {$row['BusNo']} - {$row['Path']}</option>";
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <!-- Direction Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Direction</label>
                        <?php if ($lockedDirection): ?>
                            <input type="text" name="txtLockedDirection" value="<?php echo htmlspecialchars($lockedDirection); ?>" readonly
                                   class="input-premium">
                            <input type="hidden" name="txtLockedDirection" value="<?php echo htmlspecialchars($lockedDirection); ?>">
                        <?php else: ?>
                            <select name="cboDirection" required class="input-premium">
                                <option value="">Select Direction</option>
                                <?php
                                if (empty($existingDirections) || (count($existingDirections) == 1 && !in_array('Single', $existingDirections))) {
                                    echo '<option value="Forward" ' . ($suggestedDirection === 'Forward' ? 'selected' : '') . '>Forward</option>';
                                    echo '<option value="Reverse" ' . ($suggestedDirection === 'Reverse' ? 'selected' : '') . '>Reverse</option>';
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <!-- Route Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Route Notes</label>
                        <?php if ($lockedNotes): ?>
                            <input type="text" name="txtLockedNotes" value="<?php echo htmlspecialchars($lockedNotes); ?>" readonly
                                   class="input-premium">
                        <?php else: ?>
                            <input type="text" name="txtRouteNotes" class="input-premium" placeholder="e.g. Express, Normal (optional)">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Gate Section -->
                <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-6 shadow-inner">
                    <h2 class="text-lg font-bold text-indigo-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-indigo-500"></i> Next Gate Location
                    </h2>
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-grow w-full">
                            <label class="block text-xs font-bold text-indigo-400 uppercase tracking-wider mb-2">Select Gate</label>
                            <select name="cboGateID" id="gateSelect" required class="w-full">
                                <option value="">Search and select a gate...</option>
                            </select>
                        </div>
                        
                        <input type="hidden" name="txtAdminID" value="<?php echo $_SESSION['AdminID']; ?>">

                        <div class="flex gap-3 w-full md:w-auto">
                            <button type="reset" name="btnClear" class="action-btn px-6 py-3 bg-white text-slate-600 font-bold rounded-xl flex items-center justify-center gap-2 shadow-sm border border-slate-200 hover:text-slate-800 hover:bg-slate-50 flex-1 md:flex-none">
                                <i class="fas fa-eraser"></i> Clear
                            </button>
                            <button type="submit" name="btnAdd" class="action-btn px-8 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-bold rounded-xl flex items-center justify-center gap-2 shadow-md hover:from-indigo-700 hover:to-blue-700 flex-1 md:flex-none whitespace-nowrap">
                                <i class="fas fa-plus"></i> Add Gate
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Display Route Table -->
        <?php if (!empty($_SESSION['Route_Process'])): ?>
            <div class="max-w-5xl mx-auto mt-8 bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <i class="fas fa-list-ol text-emerald-500"></i> Route Preview
                        <?php 
                        $totalGates = 0;
                        foreach ($_SESSION['Route_Process'] as $r) { $totalGates += count($r['Gates']); }
                        ?>
                        <span class="ml-2 text-xs font-bold bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full"><?php echo $totalGates; ?> Gates Pending</span>
                    </h2>
                    
                    <form action="RouteEntry.php" method="POST" class="m-0">
                        <button type="submit" name="btnSubmit" class="action-btn w-full md:w-auto px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold rounded-xl flex items-center justify-center gap-2 shadow-md hover:from-emerald-600 hover:to-teal-700">
                            <i class="fas fa-save"></i> Save Route to Database
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Bus Info</th>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Direction</th>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Gate Sequence</th>
                                <th class="py-4 px-6 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($_SESSION['Route_Process'] as $routeID => $routeDetails): ?>
                                <?php 
                                $pos = 1;
                                foreach ($routeDetails['Gates'] as $gate): 
                                ?>
                                    <tr class="group hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-6">
                                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($routeDetails['BusNo']); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($routeDetails['Path']); ?></p>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="inline-flex text-xs font-bold px-2 py-1 rounded-md <?php echo $routeDetails['Direction'] == 'Forward' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'; ?>">
                                                <?php echo htmlspecialchars($routeDetails['Direction']); ?>
                                            </span>
                                            <?php if ($routeDetails['Notes']): ?>
                                                <p class="text-xs text-slate-500 mt-1 italic"><?php echo htmlspecialchars($routeDetails['Notes']); ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <span class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold">
                                                    <?php echo sprintf('%02d', $pos++); ?>
                                                </span>
                                                <span class="font-bold text-slate-700"><?php echo htmlspecialchars($gate['GateName']); ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 whitespace-nowrap text-right">
                                            <a href="RouteEntry.php?action=remove&RouteID=<?php echo urlencode($routeID); ?>&GateID=<?php echo urlencode($gate['GateID']); ?>" 
                                               class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white inline-flex items-center justify-center transition-colors shadow-sm border border-rose-100 hover:border-rose-500" title="Remove Gate">
                                                <i class="fas fa-trash text-sm"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include('../includes/admfooter.php'); ?>

    <!-- Initialize Select2 for Gate and Bus Selection with Auto-Focus -->
    <script>
    $(document).ready(function() {
        // Initialize Select2 for Gate Selection with typing by GateID
        $('#gateSelect').select2({
            placeholder: "Select Gate",
            allowClear: true,
            width: '100%',
            dropdownCssClass: 'text-gray-700',
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

        // Automatically focus the search input when the Gate dropdown is opened
        $('#gateSelect').on('select2:open', function() {
            setTimeout(function() {
                $('.select2-search__field').focus();
            }, 50);
        });

        // Initialize Select2 for Bus Selection
        $('#busSelect').select2({
            placeholder: "Select Bus",
            allowClear: true,
            width: '100%',
            dropdownCssClass: 'text-gray-700'
        });

        // Automatically focus the search input when the Bus dropdown is opened
        $('#busSelect').on('select2:open', function() {
            setTimeout(function() {
                $('.select2-search__field').focus();
            }, 50);
        });
    });
    </script>
</body>
</html>