<?php
session_start();
include('../config/database.php');

if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You do not have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Handle Search functionality
$searchQuery = isset($_POST['btnSearch']) && !empty($_POST['searchQuery']) ? mysqli_real_escape_string($connect, trim($_POST['searchQuery'])) : '';

// Default query to fetch townships (last 3 if no search)
if ($searchQuery) {
    $TownshipQuery = "SELECT * FROM township WHERE LOWER(TownshipName) LIKE LOWER(?) ORDER BY TownshipID DESC";
    $stmt = mysqli_prepare($connect, $TownshipQuery);
    $searchPattern = "%$searchQuery%";
    mysqli_stmt_bind_param($stmt, "s", $searchPattern);
    mysqli_stmt_execute($stmt);
    $ret = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $TownshipQuery = "SELECT * FROM township ORDER BY TownshipID DESC LIMIT 3";
    $ret = mysqli_query($connect, $TownshipQuery);
}

// Handle Save functionality
if (isset($_POST['btnSave'])) {
    $txtTownshipName = mysqli_real_escape_string($connect, $_POST['txtTownshipName']);

    $query = "SELECT * FROM township WHERE TownshipName=?";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "s", $txtTownshipName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<script>window.alert('Township Name $txtTownshipName already exists')</script>";
    } else {
        $Insert = "INSERT INTO township(TownshipName) VALUES (?)";
        $stmt = mysqli_prepare($connect, $Insert);
        mysqli_stmt_bind_param($stmt, "s", $txtTownshipName);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>window.alert('Township $txtTownshipName Successfully Saved')</script>";
            echo "<script>window.location='TownshipEntry.php';</script>";
        } else {
            echo "<p> Something went wrong: " . mysqli_error($connect) . "</p>";
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Township Entry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include('../includes/admheader.php'); ?>

    <div class="max-w-4xl mx-auto mt-24 p-4 sm:p-6 bg-white shadow-lg rounded-lg">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Township Entry</h2>

        <!-- Township Entry Form -->
        <form action="TownshipEntry" method="POST" class="space-y-4">
            <div>
                <label class="block font-medium text-gray-700">Township Name</label>
                <input type="text" name="txtTownshipName" placeholder="Enter Township Name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
            </div>

            <div class="flex flex-col sm:flex-row sm:space-x-3 space-y-3 sm:space-y-0 mt-4">
                <button type="submit" name="btnSave" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-green-500 to-green-700 text-white rounded-lg shadow-md hover:from-green-600 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition-all duration-300 ease-in-out transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i> Save
                </button>
                
                <button type="reset" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-700 text-white rounded-lg shadow-md hover:from-gray-600 hover:to-gray-800 focus:ring-4 focus:ring-gray-300 transition-all duration-300 ease-in-out transform hover:scale-105">
                    <i class="fas fa-undo-alt mr-2"></i> Reset
                </button>

                <a href="TownshipListForAdmin.php" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-700 text-white rounded-lg shadow-md hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 transition-all duration-300 ease-in-out transform hover:scale-105 text-center">
                    <i class="fas fa-list mr-2"></i> View Township List
                </a>
            </div>
        </form>

        <hr class="my-6 border-gray-200">

        <!-- Search Townships -->
        <h3 class="text-xl font-bold text-gray-800 mb-4">Search Townships</h3>
        <form method="POST" class="flex flex-col sm:flex-row sm:space-x-2 space-y-3 sm:space-y-0">
            <input type="text" name="searchQuery" placeholder="Search Township Name..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
            <button type="submit" name="btnSearch" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-yellow-500 to-yellow-700 text-white rounded-lg shadow-md hover:from-yellow-600 hover:to-yellow-800 focus:ring-4 focus:ring-yellow-300 transition-all duration-300 ease-in-out transform hover:scale-105">
                <i class="fas fa-search mr-2"></i> Search
            </button>
        </form>

        <hr class="my-6 border-gray-200">

        <!-- Township List -->
        <h3 class="text-xl font-bold text-gray-800 mb-4">Township List</h3>

        <!-- Table Layout for Desktop -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 shadow-md rounded-lg">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-3 px-4 text-left">Township ID</th>
                        <th class="py-3 px-4 text-left">Township Name</th>
                        <th class="py-3 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($ret) > 0): ?>
                        <?php while ($arr = mysqli_fetch_array($ret)): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo $arr['TownshipID']; ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($arr['TownshipName']); ?></td>
                                <td class="py-3 px-4 flex space-x-2">
                                    <a href="TownshipEdit.php?TownshipID=<?php echo $arr['TownshipID']; ?>" class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-700 text-white rounded-lg shadow-md hover:from-green-600 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition-all duration-300 ease-in-out transform hover:scale-105">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="TownshipDelete.php?TownshipID=<?php echo $arr['TownshipID']; ?>" class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-700 text-white rounded-lg shadow-md hover:from-red-600 hover:to-red-800 focus:ring-4 focus:ring-red-300 transition-all duration-300 ease-in-out transform hover:scale-105">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="py-4 px-4 text-center text-gray-600">
                                <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>
                                No townships found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Card Layout for Mobile -->
        <div class="block md:hidden space-y-4">
            <?php 
            // Reset the result pointer to the beginning for the mobile view
            mysqli_data_seek($ret, 0);
            if (mysqli_num_rows($ret) > 0): ?>
                <?php while ($arr = mysqli_fetch_array($ret)): ?>
                    <div class="bg-gray-50 p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="flex flex-col space-y-2">
                            <div class="flex justify-between items-center">
                                <p class="text-sm font-semibold text-gray-800">Township ID: <?php echo $arr['TownshipID']; ?></p>
                            </div>
                            <p class="text-sm text-gray-600">Township Name: <?php echo htmlspecialchars($arr['TownshipName']); ?></p>
                            <div class="flex space-x-2">
                                <a href="TownshipEdit.php?TownshipID=<?php echo $arr['TownshipID']; ?>" class="w-full px-4 py-2 bg-gradient-to-r from-green-500 to-green-700 text-white rounded-lg shadow-md hover:from-green-600 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition-all duration-300 ease-in-out transform hover:scale-105 text-center">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                                <a href="TownshipDelete.php?TownshipID=<?php echo $arr['TownshipID']; ?>" class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-red-700 text-white rounded-lg shadow-md hover:from-red-600 hover:to-red-800 focus:ring-4 focus:ring-red-300 transition-all duration-300 ease-in-out transform hover:scale-105 text-center">
                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-gray-600 py-4">
                    <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>
                    No townships found.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../includes/admfooter.php'); ?>
</body>
</html>