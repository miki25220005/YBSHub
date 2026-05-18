<?php
session_start();
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

include('../config/database.php');
include('../includes/admheader.php');

$adminEmail = '';
if (isset($_SESSION['AdminID'])) {
    $query = "SELECT EmailAddress FROM admin WHERE AdminID = " . intval($_SESSION['AdminID']);
    $result = mysqli_query($connect, $query);
    if ($result) {
        $adminEmail = mysqli_fetch_assoc($result)['EmailAddress'];
    }
}

if ($adminEmail !== 'thantzinhtoo2005@gmail.com') {
    echo "<script>window.alert('You do not have permission to access this page.')</script>";
    echo "<script>window.location='index.php';</script>";
}

$admins = [];
$queryAdmins = "SELECT * FROM admin";
$resultAdmins = mysqli_query($connect, $queryAdmins);
if ($resultAdmins) {
    while ($row = mysqli_fetch_assoc($resultAdmins)) {
        $admins[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        $adminName = mysqli_real_escape_string($connect, $_POST['AdminName']);
        $email = mysqli_real_escape_string($connect, $_POST['EmailAddress']);
        $password = password_hash($_POST['Password'], PASSWORD_DEFAULT);

        $addQuery = "INSERT INTO admin (AdminName, EmailAddress, Password) VALUES ('$adminName', '$email', '$password')";
        if (mysqli_query($connect, $addQuery)) {
            echo "<script>window.alert('Admin added successfully!')</script>";
            echo "<script>window.location='admin_mgm.php';</script>";
        } else {
            echo "<script>window.alert('Error adding admin: " . mysqli_error($connect) . "')</script>";
        }
    } elseif (isset($_POST['delete_admin'])) {
        $adminID = intval($_POST['AdminID']);
        $deleteQuery = "DELETE FROM admin WHERE AdminID = $adminID";
        if (mysqli_query($connect, $deleteQuery)) {
            echo "<script>window.alert('Admin deleted successfully!')</script>";
            echo "<script>window.location='admin_mgm.php';</script>";
        } else {
            echo "<script>window.alert('Error deleting admin: " . mysqli_error($connect) . "')</script>";
        }
    }
}

// Fetch pending admins
$pendingAdmins = mysqli_query($connect, "SELECT * FROM pending_admins");

// Handle Approval & Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_admin'])) {
        $pendingID = intval($_POST['PendingID']);
        
        // Move data from pending_admins to admin table
        $queryMove = "INSERT INTO admin (AdminName, EmailAddress, Password, PhoneNumber, BirthofDate, Photo, City, State)
                      SELECT AdminName, EmailAddress, Password, PhoneNumber, BirthofDate, Photo, City, State FROM pending_admins WHERE PendingID = $pendingID";
        mysqli_query($connect, $queryMove);

        // Delete from pending_admins
        mysqli_query($connect, "DELETE FROM pending_admins WHERE PendingID = $pendingID");

        echo "<script>alert('Admin Approved!');</script>";
        echo "<script>window.location='admin_mgm.php';</script>";
    }

    if (isset($_POST['reject_admin'])) {
        $pendingID = intval($_POST['PendingID']);
        mysqli_query($connect, "DELETE FROM pending_admins WHERE PendingID = $pendingID");

        echo "<script>alert('Admin Rejected!');</script>";
        echo "<script>window.location='admin_mgm.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Yangon Bus Service</title>
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
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900 flex flex-col min-h-screen selection:bg-indigo-200 selection:text-indigo-900">

    <!-- Include Header -->
    <?php include('../includes/admheader.php'); ?>

    <main class="flex-grow pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto space-y-8 animate-fade-in-up">
            
            <!-- Page Header -->
            <div class="text-center space-y-3">
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 drop-shadow-sm">
                    Admin Management
                </h1>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                    Manage existing administrators and review pending access requests.
                </p>
            </div>

            <!-- Existing Admins Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 transform transition-all hover:shadow-2xl duration-300">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                        <div class="bg-indigo-100 p-2 rounded-lg text-indigo-600 shadow-sm">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        Existing Admins
                    </h2>
                    <span class="bg-indigo-50 border border-indigo-100 text-indigo-700 py-1 px-3 rounded-full text-xs font-bold tracking-wide shadow-sm">
                        <?php echo count($admins); ?> Active
                    </span>
                </div>
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Admin Info</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Email Address</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <?php foreach ($admins as $admin): ?>
                                <tr class="hover:bg-indigo-50/40 transition-colors duration-200 group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-indigo-100 to-blue-200 border border-white flex items-center justify-center text-indigo-700 font-bold text-lg shadow-sm">
                                                    <?php echo strtoupper(substr(htmlspecialchars($admin['AdminName']), 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors"><?php echo htmlspecialchars($admin['AdminName']); ?></div>
                                                <div class="text-xs font-medium text-gray-500 flex items-center gap-1 mt-0.5">
                                                    <i class="fas fa-id-badge text-gray-400"></i> #<?php echo $admin['AdminID']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-600 flex items-center gap-2">
                                            <div class="bg-gray-100 p-1.5 rounded text-gray-400 group-hover:bg-indigo-100 group-hover:text-indigo-500 transition-colors">
                                                <i class="far fa-envelope"></i>
                                            </div>
                                            <?php echo htmlspecialchars($admin['EmailAddress']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-3 opacity-90 group-hover:opacity-100 transition-opacity">
                                            <a href="check_adm.php?AdminID=<?php echo $admin['AdminID']; ?>"
                                               class="inline-flex items-center px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm font-semibold text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-all duration-200">
                                                <i class="fas fa-eye mr-2 text-indigo-500"></i> View
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this admin? This action cannot be undone.');">
                                                <input type="hidden" name="AdminID" value="<?php echo $admin['AdminID']; ?>">
                                                <button type="submit" name="delete_admin"
                                                        class="inline-flex items-center px-3 py-2 bg-white border border-red-200 rounded-lg text-sm font-semibold text-red-600 hover:bg-red-50 hover:border-red-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-sm transition-all duration-200">
                                                    <i class="fas fa-trash-alt mr-2 text-red-500"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($admins) === 0): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                            <i class="fas fa-users-slash text-2xl text-gray-400"></i>
                                        </div>
                                        <p class="text-gray-500 text-base font-medium">No administrators found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Admin Approvals Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 transform transition-all hover:shadow-2xl duration-300 mt-8">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-white flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                        <div class="bg-amber-100 p-2 rounded-lg text-amber-600 shadow-sm">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        Pending Approvals
                    </h2>
                    <span class="bg-amber-50 border border-amber-200 text-amber-700 py-1 px-3 rounded-full text-xs font-bold tracking-wide shadow-sm">
                        <?php echo mysqli_num_rows($pendingAdmins); ?> Pending
                    </span>
                </div>
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Applicant Info</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Email Address</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <?php if (mysqli_num_rows($pendingAdmins) > 0): ?>
                                <?php mysqli_data_seek($pendingAdmins, 0); ?>
                                <?php while ($admin = mysqli_fetch_assoc($pendingAdmins)): ?>
                                    <tr class="hover:bg-amber-50/30 transition-colors duration-200 group">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-12 w-12">
                                                    <div class="h-12 w-12 rounded-full bg-gradient-to-br from-amber-100 to-orange-200 border border-white flex items-center justify-center text-amber-700 font-bold text-lg shadow-sm">
                                                        <?php echo strtoupper(substr(htmlspecialchars($admin['AdminName']), 0, 1)); ?>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-gray-900 group-hover:text-amber-600 transition-colors"><?php echo htmlspecialchars($admin['AdminName']); ?></div>
                                                    <div class="text-xs font-medium text-gray-500 flex items-center gap-1 mt-0.5">
                                                        <i class="fas fa-hashtag text-gray-400"></i> Req #<?php echo $admin['PendingID']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-600 flex items-center gap-2">
                                                <div class="bg-gray-100 p-1.5 rounded text-gray-400 group-hover:bg-amber-100 group-hover:text-amber-500 transition-colors">
                                                    <i class="far fa-envelope"></i>
                                                </div>
                                                <?php echo htmlspecialchars($admin['EmailAddress']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-3">
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to approve this request?');">
                                                    <input type="hidden" name="PendingID" value="<?php echo $admin['PendingID']; ?>">
                                                    <button type="submit" name="approve_admin"
                                                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-lg text-sm font-bold hover:from-emerald-600 hover:to-teal-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                                                        <i class="fas fa-check mr-2"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                                    <input type="hidden" name="PendingID" value="<?php echo $admin['PendingID']; ?>">
                                                    <button type="submit" name="reject_admin"
                                                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-sm transition-all duration-200">
                                                        <i class="fas fa-times mr-2 text-red-500"></i> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                            <i class="fas fa-clipboard-check text-2xl text-gray-400"></i>
                                        </div>
                                        <p class="text-gray-600 text-base font-semibold">No pending approvals at the moment.</p>
                                        <p class="text-gray-400 text-sm mt-1">You're all caught up!</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </main>

    <!-- Include Footer -->
    <?php include('../includes/admfooter.php'); ?>

    <!-- Simple custom animation for loading -->
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
    </style>
</body>
</html>

