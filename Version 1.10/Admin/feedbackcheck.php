<?php
include('../config/database.php');

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Handle Solved action
if (isset($_GET['solve'])) {
    $id = intval($_GET['solve']);
    $update = "UPDATE feedback SET Status = 'Solved' WHERE FeedbackID = $id";
    $connect->query($update);
    header("Location: feedbackcheck.php"); // Refresh the page after update
    exit();
}

// Get all feedback
$sql = "SELECT * FROM feedback ORDER BY FeedbackID DESC";
$result = $connect->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom animations */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up {
            animation: slideUp 0.5s ease-out forwards;
        }
        /* Ensure table rows animate on load */
        tbody tr {
            opacity: 0;
            animation: slideUp 0.5s ease-out forwards;
        }
        tbody tr:nth-child(1) { animation-delay: 0.1s; }
        tbody tr:nth-child(2) { animation-delay: 0.2s; }
        tbody tr:nth-child(3) { animation-delay: 0.3s; }
        tbody tr:nth-child(4) { animation-delay: 0.4s; }
        tbody tr:nth-child(5) { animation-delay: 0.5s; }
        
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased">
    <?php include('../includes/admheader.php'); ?>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-40 pb-12">
        <div class="bg-white p-8 rounded-2xl shadow-xl">
            <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center flex items-center justify-center">
                <i class="fas fa-comments mr-2 text-black-500"></i> Feedback Check
            </h1>

            <!-- Feedback Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead class="bg-gray-800 text-white text-sm uppercase font-semibold">
                        <tr>
                            <th class="py-4 px-6 text-left">ID</th>
                            <th class="py-4 px-6 text-left">Name</th>
                            <th class="py-4 px-6 text-left">Email</th>
                            <th class="py-4 px-6 text-left">Message</th>
                            <th class="py-4 px-6 text-left">Status</th>
                            <th class="py-4 px-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition duration-200">
                                    <td class="py-4 px-6 font-medium"><?= htmlspecialchars($row['FeedbackID']) ?></td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="py-4 px-6 max-w-xs truncate"><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                                    <td class="py-4 px-6">
                                        <?php if ($row['Status'] == 'Solved'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Solved
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-exclamation-circle mr-1"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($row['Status'] != 'Solved'): ?>
                                            <a href="?solve=<?= $row['FeedbackID'] ?>" 
                                               class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200 text-sm">
                                                <i class="fas fa-check mr-1"></i> Mark as Solved
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">Done</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-6 px-6 text-center text-gray-500">
                                    <i class="fas fa-inbox mr-2"></i> No feedback found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include('../includes/admfooter.php'); ?>
</body>
</html>

<?php
$connect->close();
?>