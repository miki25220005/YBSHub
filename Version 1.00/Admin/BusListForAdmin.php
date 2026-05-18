<?php
session_start();
include('../config/database.php');
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don't have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Handle search query
$searchQuery = '';
$searchValue = ''; // Numeric part for database search
if (isset($_GET['search'])) {
    $searchQuery = mysqli_real_escape_string($connect, $_GET['search']);
    // Remove "YBID " prefix for database search
    $searchValue = preg_replace('/^YBID\s*/i', '', $searchQuery);
}

// Base query to fetch buses
$BusQuery = "SELECT * FROM bus";
if (!empty($searchValue)) {
    $BusQuery .= " WHERE BusNo LIKE '%$searchValue%' OR Path LIKE '%$searchValue%'";
}

// Pagination setup
$limit = 10; // Number of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$BusQuery .= " ORDER BY BusID ASC LIMIT $start, $limit"; // Changed to BusID

// Execute the query
$ret = mysqli_query($connect, $BusQuery);

// Get total rows for pagination
$totalResult = mysqli_query($connect, "SELECT COUNT(*) as total FROM bus");
$totalRows = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus List for Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex flex-col min-h-screen text-gray-800 transition-colors duration-300">

    <!-- Include Header -->
    <?php include('../includes/admheader.php'); ?>

    <div class="container mx-auto mt-24 p-6 bg-white shadow-lg rounded-lg max-w-5xl">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Bus List</h1>

        <!-- Search Box -->
        <div class="mb-6 flex justify-center">
            <form action="BusListForAdmin.php" method="GET" class="flex items-center space-x-2">
                <input type="text" name="search" placeholder="Search by Bus No (e.g., YBID 1) or Path" 
                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       class="w-72 px-4 py-2 bg-white border border-gray-300 text-gray-800 rounded-lg focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Bus Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 shadow-md rounded-lg">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-2 px-4 text-left">Bus ID</th>
                        <th class="py-2 px-4 text-left">Bus No</th>
                        <th class="py-2 px-4 text-left">Path</th>
                        <th class="py-2 px-4 text-left">Card QR</th>
                        <th class="py-2 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($ret) > 0) {
                        while ($row = mysqli_fetch_assoc($ret)) {
                            $displayBusNo = "" . $row['BusNo']; // Add "YBID " prefix for display
                            echo "<tr class='border-b hover:bg-gray-100:bg-slate-700'>";
                            echo "<td class='py-2 px-4'>" . $row['BusID'] . "</td>";
                            echo "<td class='py-2 px-4'>" . htmlspecialchars($displayBusNo) . "</td>";
                            echo "<td class='py-2 px-4'>" . htmlspecialchars($row['Path']) . "</td>";
                            echo "<td class='py-2 px-4'>" . htmlspecialchars($row['CardQR']) . "</td>";
                            echo "<td class='py-2 px-4 flex space-x-2'>
                                <a href='BusEdit.php?BusID=" . $row['BusID'] . "' 
                                   class='px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors'>
                                    <i class='fas fa-edit'></i> Edit
                                </a>
                                <a href='BusDelete.php?BusID=" . $row['BusID'] . "' 
                                   class='px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition-colors'
                                   onclick='return confirm(\"Are you sure you want to delete this bus?\")'>
                                    <i class='fas fa-trash-alt'></i> Delete
                                </a>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-4 text-gray-600'>No buses found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="BusListForAdmin.php?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>"
                   class="px-4 py-2 bg-black text-white rounded hover:bg-gray-800:bg-slate-600 transition-colors">
                   <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include('../includes/admfooter.php'); ?>

</body>
</html>