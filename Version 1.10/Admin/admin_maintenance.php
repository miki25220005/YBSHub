<?php
session_start();
include('../config/database.php'); // Include your database connection file

// Check if admin is logged in
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You do not have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Handle form submission
if (isset($_POST['btnSave'])) {
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validate dates
    if (strtotime($startTime) >= strtotime($endTime)) {
        echo "<script>window.alert('End time must be after start time.')</script>";
    } else {
        // Check if a schedule already exists
        $checkQuery = "SELECT * FROM maintenance_schedule LIMIT 1";
        $checkResult = mysqli_query($connect, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            // Update existing schedule
            $updateQuery = "UPDATE maintenance_schedule SET start_time = ?, end_time = ?, is_active = ?";
            $stmt = mysqli_prepare($connect, $updateQuery);
            mysqli_stmt_bind_param($stmt, "ssi", $startTime, $endTime, $isActive);
        } else {
            // Insert new schedule
            $insertQuery = "INSERT INTO maintenance_schedule (start_time, end_time, is_active) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($connect, $insertQuery);
            mysqli_stmt_bind_param($stmt, "ssi", $startTime, $endTime, $isActive);
        }

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>window.alert('Maintenance schedule updated successfully.')</script>";
            echo "<script>window.location='admin_maintenance.php';</script>";
        } else {
            echo "<script>window.alert('Something went wrong: " . addslashes(mysqli_error($connect)) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch the current maintenance schedule
$scheduleQuery = "SELECT * FROM maintenance_schedule LIMIT 1";
$scheduleResult = mysqli_query($connect, $scheduleQuery);
$schedule = mysqli_num_rows($scheduleResult) > 0 ? mysqli_fetch_assoc($scheduleResult) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60;url=admin_maintenance.php">
    <title>Manage Maintenance Schedule - Yangon Bus Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom scrollbar for tables */
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
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
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
        /* Toggle Switch CSS */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #4f46e5;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #4f46e5;
        }
        .toggle-checkbox {
            right: 0;
            z-index: 1;
            border-color: #e2e8f0;
            transition: all 0.3s;
        }
        .toggle-label {
            background-color: #e2e8f0;
            transition: all 0.3s;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900 flex flex-col min-h-screen selection:bg-indigo-200 selection:text-indigo-900">
    <?php include('../includes/admheader.php'); ?>

    <main class="flex-grow pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto space-y-8 animate-fade-in-up">
            
            <!-- Page Header -->
            <div class="text-center space-y-3">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-amber-100 to-orange-200 border border-white text-amber-600 font-bold text-2xl shadow-md mb-2">
                    <i class="fas fa-tools"></i>
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 drop-shadow-sm">
                    Maintenance Settings
                </h1>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                    Control system availability and schedule upcoming maintenance periods.
                </p>
            </div>

            <!-- Settings Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 transform transition-all hover:shadow-2xl duration-300">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                        <div class="bg-indigo-100 p-2 rounded-lg text-indigo-600 shadow-sm">
                            <i class="fas fa-clock"></i>
                        </div>
                        Configure Schedule
                    </h2>
                </div>
                
                <div class="p-6 md:p-8">
                    <form action="admin_maintenance.php" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-gray-700">Start Time</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-hourglass-start text-gray-400"></i>
                                    </div>
                                    <input type="datetime-local" name="start_time" value="<?php echo $schedule ? date('Y-m-d\TH:i', strtotime($schedule['start_time'])) : ''; ?>" required class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-gray-700">End Time</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-hourglass-end text-gray-400"></i>
                                    </div>
                                    <input type="datetime-local" name="end_time" value="<?php echo $schedule ? date('Y-m-d\TH:i', strtotime($schedule['end_time'])) : ''; ?>" required class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-gray-800">Activate Maintenance Mode</h3>
                                <p class="text-xs text-gray-500 mt-1">If enabled and within the time range, public users will be locked out.</p>
                            </div>
                            <!-- Custom Toggle -->
                            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                                <input type="checkbox" name="is_active" id="toggle" value="1" <?php echo $schedule && $schedule['is_active'] ? 'checked' : ''; ?> class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                                <label for="toggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                            </div>
                        </div>
                        
                        <div class="pt-4 flex justify-end">
                            <button type="submit" name="btnSave" class="w-full sm:w-auto inline-flex justify-center items-center px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl text-sm font-bold hover:from-emerald-600 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                                <i class="fas fa-save mr-2"></i> Save Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Schedule Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 transform transition-all hover:shadow-2xl duration-300">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                        <div class="bg-blue-100 p-2 rounded-lg text-blue-600 shadow-sm">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        Current Schedule
                    </h2>
                </div>
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Start Time</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">End Time</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <?php if ($schedule): ?>
                                <tr class="hover:bg-blue-50/40 transition-colors duration-200">
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                                            <i class="far fa-calendar-alt text-gray-400"></i>
                                            <?php echo date("d F Y, h:i A", strtotime($schedule['start_time'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                                            <i class="far fa-calendar-alt text-gray-400"></i>
                                            <?php echo date("d F Y, h:i A", strtotime($schedule['end_time'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <?php if ($schedule['is_active']): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-sm">
                                                <span class="relative flex h-2 w-2 mr-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                                </span>
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200 shadow-sm">
                                                <i class="fas fa-circle text-gray-400 text-[10px] mr-2"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                            <i class="fas fa-calendar-times text-2xl text-gray-400"></i>
                                        </div>
                                        <p class="text-gray-600 text-base font-semibold">No maintenance schedule set.</p>
                                        <p class="text-gray-400 text-sm mt-1">The system is currently running normally.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </main>

    <?php include('../includes/admfooter.php'); ?>
</body>
</html>