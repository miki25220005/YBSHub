<?php
session_start();
include('../config/database.php');

// Redirect if not logged in as admin
if (!isset($_SESSION['AdminName'])) {
    echo "<script>alert('You don\'t have permission to access this page.'); window.location='AdminLogin.php';</script>";
    exit();
}

// Handle search query safely (search by Gate Name or Gate ID)
$searchQuery = '';
$whereClause = '';
if (!empty($_GET['search'])) {
    $searchQuery = $_GET['search'];
    $whereClause = " WHERE gate.GateName LIKE ? OR gate.GateID LIKE ?";
}

// Base query with pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$GateQuery = "
    SELECT gate.GateID, gate.GateName, gate.Latitude, gate.Longitude, gate.Road, township.TownshipName 
    FROM gate 
    LEFT JOIN township ON gate.TownshipID = township.TownshipID
    $whereClause
    LIMIT ?, ?";

$stmt = $connect->prepare($GateQuery);

if (!empty($searchQuery)) {
    $searchParam = "%$searchQuery%";
    $stmt->bind_param("ssii", $searchParam, $searchParam, $start, $limit); // Two search params for GateName and GateID
} else {
    $stmt->bind_param("ii", $start, $limit);
}

$stmt->execute();
$ret = $stmt->get_result();

// Total count query
$countQuery = "SELECT COUNT(*) as total FROM gate $whereClause";
$countStmt = $connect->prepare($countQuery);

if (!empty($searchQuery)) {
    $searchParam = "%$searchQuery%";
    $countStmt->bind_param("ss", $searchParam, $searchParam); // Two params for GateName and GateID
}

$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Pagination range logic
$maxVisiblePages = 4;
$halfVisible = floor($maxVisiblePages / 2);

$startPage = max(1, $page - $halfVisible);
$endPage = min($totalPages, $startPage + $maxVisiblePages - 1);

if ($endPage === $totalPages) {
    $startPage = max(1, $endPage - $maxVisiblePages + 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate List for Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased flex flex-col">
    <?php include('../includes/admheader.php'); ?>

    <div class="flex-grow pt-20">
        <div class="max-w-6xl mx-auto mt-6 p-6 bg-white shadow-xl rounded-xl">
            <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center flex items-center justify-center">
                <i class="fas fa-door-open mr-2 text-blue-500"></i> Gate List
            </h1>

            <!-- Search Bar -->
            <div class="mb-8 flex justify-center">
                <form action="GateListForAdmin.php" method="GET" class="relative w-full max-w-md flex items-center">
                    <input type="text" name="search" placeholder="Search by Gate Name or Gate ID"
                           value="<?php echo htmlspecialchars($searchQuery); ?>"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                    <i class="fas fa-search absolute left-3 text-gray-400"></i>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300 flex items-center">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Gate ID</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Gate Name</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Latitude</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Longitude</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Road</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Township</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($ret->num_rows > 0): ?>
                            <?php while ($row = $ret->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition duration-200">
                                    <td class="py-4 px-6"><?php echo $row['GateID']; ?></td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($row['GateName']); ?></td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($row['Latitude']); ?></td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($row['Longitude']); ?></td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($row['Road']); ?></td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($row['TownshipName']); ?></td>
                                    <td class="py-4 px-6 flex space-x-3">
                                        <a href="GateEdit.php?GateID=<?php echo $row['GateID']; ?>"
                                           class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300 flex items-center">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                        <a href="GateDelete.php?GateID=<?php echo $row['GateID']; ?>"
                                           class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-300 flex items-center"
                                           onclick="return confirm('Are you sure you want to delete this gate?')">
                                            <i class="fas fa-trash-alt mr-1"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-6 text-gray-600">No gates found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center items-center space-x-2">
                <?php if ($startPage > 1): ?>
                    <a href="GateListForAdmin.php?page=<?php echo ($startPage - 1); ?>&search=<?php echo urlencode($searchQuery); ?>"
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-blue-700 transition duration-300 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="GateListForAdmin.php?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>"
                       class="px-4 py-2 <?php echo ($page == $i) ? 'bg-blue-600' : 'bg-gray-600'; ?> text-white rounded-lg hover:bg-blue-700 transition duration-300">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <a href="GateListForAdmin.php?page=<?php echo ($endPage + 1); ?>&search=<?php echo urlencode($searchQuery); ?>"
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-blue-700 transition duration-300 flex items-center">
                        Show More <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('../includes/admfooter.php'); ?>
</body>
</html>