<?php
session_start();
include('../config/database.php');
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Handle delete action
if (isset($_GET['deleteRouteID'])) {
    $deleteRouteID = mysqli_real_escape_string($connect, $_GET['deleteRouteID']);

    // Begin a transaction to ensure data consistency
    mysqli_begin_transaction($connect);

    try {
        // First, delete related entries in the route_gate table
        $deleteRouteGateQuery = "DELETE FROM route_gate WHERE RouteID = ?";
        $stmtRouteGate = mysqli_prepare($connect, $deleteRouteGateQuery);
        mysqli_stmt_bind_param($stmtRouteGate, "s", $deleteRouteID);
        mysqli_stmt_execute($stmtRouteGate);
        mysqli_stmt_close($stmtRouteGate);

        // Then, delete the route from the route table
        $deleteRouteQuery = "DELETE FROM route WHERE RouteID = ?";
        $stmtRoute = mysqli_prepare($connect, $deleteRouteQuery);
        mysqli_stmt_bind_param($stmtRoute, "s", $deleteRouteID);
        mysqli_stmt_execute($stmtRoute);
        mysqli_stmt_close($stmtRoute);

        // Commit the transaction
        mysqli_commit($connect);

        // Show success message and redirect
        echo "<script>window.alert('Route deleted successfully.')</script>";
        echo "<script>window.location='RouteListAdmin.php';</script>";
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($connect);
        echo "<script>window.alert('Error deleting route: " . addslashes($e->getMessage()) . "')</script>";
        echo "<script>window.location='RouteListAdmin.php';</script>";
        exit();
    }
}

// Handle search query
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = mysqli_real_escape_string($connect, $_GET['search']);
}

$RouteQuery = "
    SELECT 
        route.RouteID, 
        bus.BusNo, 
        route.Direction,
        COUNT(route_gate.GateID) AS GateQuantity,
        admin.AdminName
    FROM route
    LEFT JOIN bus ON route.BusID = bus.BusID
    LEFT JOIN route_gate ON route.RouteID = route_gate.RouteID
    LEFT JOIN admin ON route.AdminID = admin.AdminID
";
if (!empty($searchQuery)) {
    $RouteQuery .= " 
    WHERE route.RouteID LIKE '%$searchQuery%' 
    OR bus.BusNo LIKE '%$searchQuery%' 
    OR admin.AdminName LIKE '%$searchQuery%' 
    OR route.Direction LIKE '%$searchQuery%'";
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$RouteQuery .= " 
    GROUP BY route.RouteID 
    LIMIT $start, $limit";

// Execute query
$ret = mysqli_query($connect, $RouteQuery);
$totalResult = mysqli_query($connect, "SELECT COUNT(DISTINCT RouteID) as total FROM route");
$totalRows = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route List - Yangon Bus Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom scrollbar for table */
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
        .action-btn, .pagination-btn, .search-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .action-btn:hover, .pagination-btn:hover, .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }
        .table-row {
            transition: all 0.2s ease;
        }
        .table-row:hover {
            background-color: rgba(238, 242, 255, 0.5); /* indigo-50 with opacity */
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 flex flex-col min-h-screen text-slate-800 font-sans antialiased selection:bg-indigo-200 selection:text-indigo-900">
    <?php include('../includes/admheader.php'); ?>
    
    <main class="flex-grow pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto glass-card rounded-2xl p-6 sm:p-8 animate-fade-in-up">
            
            <!-- Header & Search -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500 drop-shadow-sm mb-2">Route List</h1>
                    <p class="text-slate-500 text-sm font-medium">Manage and monitor all active bus routes</p>
                </div>
                
                <form action="RouteListAdmin.php" method="GET" class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full sm:w-80">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-search text-indigo-400"></i>
                        </div>
                        <input type="text" name="search" placeholder="Search routes, buses, or admins..." 
                               value="<?php echo htmlspecialchars($searchQuery); ?>" 
                               class="w-full pl-11 pr-4 py-3 bg-white/60 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm font-medium text-slate-700 shadow-inner placeholder-slate-400 focus:bg-white">
                    </div>
                    <button type="submit" class="search-btn w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-xl hover:from-indigo-600 hover:to-blue-700 focus:ring-4 focus:ring-indigo-200 flex items-center justify-center gap-2 font-bold shadow-md">
                        <i class="fas fa-search hidden sm:block"></i>
                        <span>Search</span>
                    </button>
                </form>
            </div>

            <!-- Route List Table/Cards -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden sm:block overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Route ID</th>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Bus Number</th>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Direction</th>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Gate Quantity</th>
                                <th class="py-4 px-6 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Admin Name</th>
                                <th class="py-4 px-6 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php while ($row = mysqli_fetch_assoc($ret)) { ?>
                                <tr class="table-row group">
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-3 font-bold text-slate-700">
                                            <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-sm">
                                                <i class="fas fa-route text-sm"></i>
                                            </div>
                                            <?php echo htmlspecialchars($row['RouteID']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap font-semibold text-slate-600">
                                        <i class="fas fa-bus text-slate-400 mr-2 text-sm"></i><?php echo htmlspecialchars($row['BusNo']); ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <?php 
                                            if ($row['Direction'] == 'Forward') {
                                                echo "<span class='px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm'><i class='fas fa-arrow-right mr-1.5 mt-0.5'></i>Forward</span>";
                                            } elseif ($row['Direction'] == 'Reverse') {
                                                echo "<span class='px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200 shadow-sm'><i class='fas fa-arrow-left mr-1.5 mt-0.5'></i>Reverse</span>";
                                            } else {
                                                echo "<span class='px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg bg-slate-50 text-slate-700 border border-slate-200 shadow-sm'><i class='fas fa-exchange-alt mr-1.5 mt-0.5'></i>Single</span>";
                                            }
                                        ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-slate-600">
                                        <div class="flex items-center gap-2">
                                            <span class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center text-xs font-extrabold shadow-sm"><?php echo $row['GateQuantity']; ?></span>
                                            <span class="text-xs text-slate-500 font-medium">Gates</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-sm text-slate-600 font-semibold">
                                        <div class="flex items-center gap-2">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['AdminName']); ?>&background=random&color=fff&size=32" alt="Avatar" class="w-8 h-8 rounded-full shadow-sm border border-slate-200">
                                            <?php echo htmlspecialchars($row['AdminName']); ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap text-center">
                                        <div class="flex justify-center space-x-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity duration-200">
                                            <a href='ViewRouteAdmin.php?RouteID=<?php echo urlencode($row['RouteID']); ?>' 
                                               class='w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors shadow-sm border border-emerald-100 hover:border-emerald-500' title="View Route">
                                                <i class='fas fa-eye text-sm'></i>
                                            </a>
                                            <a href='RouteListAdmin.php?deleteRouteID=<?php echo urlencode($row['RouteID']); ?>' 
                                               class='w-9 h-9 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-colors shadow-sm border border-rose-100 hover:border-rose-500' title="Delete Route"
                                               onclick='return confirm("Are you sure you want to delete this route? This action cannot be undone.")'>
                                                <i class='fas fa-trash text-sm'></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card Layout -->
                <div class="sm:hidden divide-y divide-slate-100">
                    <?php mysqli_data_seek($ret, 0); ?>
                    <?php while ($row = mysqli_fetch_assoc($ret)) { ?>
                        <div class="p-5 hover:bg-slate-50 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner border border-indigo-100">
                                        <i class="fas fa-route"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-extrabold text-slate-800 text-lg"><?php echo htmlspecialchars($row['RouteID']); ?></h3>
                                        <p class="text-sm font-semibold text-slate-500 mt-0.5"><i class="fas fa-bus mr-1.5 text-slate-400"></i><?php echo htmlspecialchars($row['BusNo']); ?></p>
                                    </div>
                                </div>
                                <?php 
                                    if ($row['Direction'] == 'Forward') {
                                        echo "<span class='px-2.5 py-1 inline-flex text-[10px] leading-4 font-bold uppercase rounded-md bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-100'>Forward</span>";
                                    } elseif ($row['Direction'] == 'Reverse') {
                                        echo "<span class='px-2.5 py-1 inline-flex text-[10px] leading-4 font-bold uppercase rounded-md bg-blue-50 text-blue-700 shadow-sm border border-blue-100'>Reverse</span>";
                                    } else {
                                        echo "<span class='px-2.5 py-1 inline-flex text-[10px] leading-4 font-bold uppercase rounded-md bg-slate-50 text-slate-700 shadow-sm border border-slate-200'>Single</span>";
                                    }
                                ?>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 mb-5">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block mb-1">Gates</span>
                                    <span class="font-extrabold text-slate-700 text-base"><i class="fas fa-door-open mr-2 text-indigo-400"></i><?php echo $row['GateQuantity']; ?></span>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block mb-1">Admin</span>
                                    <span class="font-extrabold text-slate-700 text-sm truncate block"><i class="fas fa-user-shield mr-2 text-indigo-400"></i><?php echo htmlspecialchars($row['AdminName']); ?></span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <a href='ViewRouteAdmin.php?RouteID=<?php echo urlencode($row['RouteID']); ?>' 
                                   class='flex-1 action-btn py-3 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl hover:bg-emerald-500 hover:text-white flex items-center justify-center gap-2 text-sm font-bold shadow-sm'>
                                    <i class='fas fa-eye'></i> View Details
                                </a>
                                <a href='RouteListAdmin.php?deleteRouteID=<?php echo urlencode($row['RouteID']); ?>' 
                                   class='action-btn px-5 py-3 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl hover:bg-rose-500 hover:text-white flex items-center justify-center shadow-sm'
                                   onclick='return confirm("Are you sure you want to delete this route?")'>
                                    <i class='fas fa-trash'></i>
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                
                <?php if(mysqli_num_rows($ret) == 0): ?>
                <div class="p-16 text-center">
                    <div class="w-24 h-24 bg-slate-50 border-2 border-dashed border-slate-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-search text-4xl text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-700 mb-2">No routes found</h3>
                    <p class="text-slate-500 max-w-sm mx-auto">Try adjusting your search criteria or add a new route to get started.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <div class="mt-8 flex justify-center items-center gap-2 flex-wrap">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="RouteListAdmin.php?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>"
                       class="pagination-btn w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all shadow-sm
                       <?php echo ($page == $i) ? 'bg-indigo-600 text-white ring-4 ring-indigo-100 border-transparent' : 'bg-white text-slate-600 border border-slate-200 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300'; ?>">
                       <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include('../includes/admfooter.php'); ?>
</body>
</html>