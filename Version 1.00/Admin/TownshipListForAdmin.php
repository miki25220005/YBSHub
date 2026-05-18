<?php 
session_start();
include('../config/database.php');
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You need have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}


// Search query handling
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = mysqli_real_escape_string($connect, $_GET['search']);
}

//Query
$TownshipQuery = "SELECT * FROM township";
if (!empty($searchQuery)) {
    $TownshipQuery .= " WHERE TownshipName LIKE '%$searchQuery%'";
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$TownshipQuery .= " LIMIT $start, $limit";

// Execute the query
$ret = mysqli_query($connect, $TownshipQuery);

// Get total rows
$totalResult = mysqli_query($connect, "SELECT COUNT(*) as total FROM township");
$totalRows = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Township List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

    <!-- Include Header -->
    <?php include('../includes/admheader.php'); ?>

    <div class="container mx-auto mt-24 p-6 bg-white shadow-lg rounded-lg max-w-6xl">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Township List</h1>

        <!-- Search Bar -->
        <div class="mb-6 flex justify-center">
            <form action="TownshipListForAdmin.php" method="GET" class="flex items-center space-x-2">
                <input type="text" name="search" placeholder="Search by Township Name" 
                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       class="w-72 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Township Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 shadow-md rounded-lg">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-2 px-4 text-left">Township ID</th>
                        <th class="py-2 px-4 text-left">Township Name</th>
                        <th class="py-2 px-4 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($ret) > 0) {
                        while ($row = mysqli_fetch_assoc($ret)) {
                            $highlightedName = $searchQuery ? str_replace($searchQuery, "<mark>$searchQuery</mark>", $row['TownshipName']) : $row['TownshipName'];
                            echo "<tr class='border-b hover:bg-gray-100'>";
                            echo "<td class='py-2 px-4'>" . $row['TownshipID'] . "</td>";
                            echo "<td class='py-2 px-4'>" . $highlightedName . "</td>";
                            echo "<td class='py-2 px-4 flex space-x-2'>
                                <a href='TownshipEdit.php?TownshipID=" . $row['TownshipID'] . "' 
                                   class='px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors'>
                                    <i class='fas fa-edit'></i> Edit
                                </a>
                                <a href='TownshipDelete.php?TownshipID=" . $row['TownshipID'] . "' 
                                   class='px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition-colors'
                                   onclick='return confirm(\"Are you sure you want to delete this township?\")'>
                                    <i class='fas fa-trash-alt'></i> Delete
                                </a>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' class='text-center py-4 text-gray-600'>
                                No townships found. <a href='TownshipListForAdmin.php' class='text-blue-600 underline'>Clear Search</a>
                              </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center space-x-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="TownshipListForAdmin.php?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>"
                   class="px-4 py-2 <?php echo ($page == $i) ? 'bg-gray-800' : 'bg-black'; ?> text-white rounded hover:bg-gray-700 transition-colors">
                   <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include('../includes/admfooter.php'); ?>

</body>
</html>
