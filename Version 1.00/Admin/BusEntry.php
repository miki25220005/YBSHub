<?php
session_start();
include('../config/database.php');
include('AutoID_Functions.php'); 

if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Generate BusID automatically
$NewBusID = AutoID("bus", "BusID", "YBID-", 6);

// Handle Save functionality
if (isset($_POST['btnSave'])) {
    $BusID = $_POST['BusID'];
    $txtBusNo = mysqli_real_escape_string($connect, $_POST['txtBusNo']);
    $txtPath = mysqli_real_escape_string($connect, $_POST['txtPath']);
    $txtCardQR = mysqli_real_escape_string($connect, $_POST['txtCardQR']);
    $txtColor = mysqli_real_escape_string($connect, $_POST['txtColor']);

    // Validation
    if (empty($txtBusNo) || empty($txtPath) || empty($txtCardQR) || empty($txtColor)) {
        echo "<script>alert('Please fill out all required fields.');</script>";
    } elseif (!in_array($txtColor, ['#0008ff', '#ff0000', '#cc00ff', '#00ff00', '#663300'])) {
        echo "<script>alert('Please select a valid color from the list.');</script>";
    } else {
        $InsertQuery = "INSERT INTO bus (BusID, BusNo, Path, CardQR, Color) VALUES ('$BusID', '$txtBusNo', '$txtPath', '$txtCardQR', '$txtColor')";
        if (mysqli_query($connect, $InsertQuery)) {
            echo "<script>alert('Bus $txtBusNo Successfully Saved'); window.location='BusEntry.php';</script>";
        } else {
            echo "<p>Something went wrong: " . mysqli_error($connect) . "</p>";
        }
    }
}

// Fetch most recent bus entries
$BusQuery = "SELECT * FROM bus ORDER BY BusID DESC LIMIT 5";
$ret = mysqli_query($connect, $BusQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased flex flex-col text-gray-800 transition-colors duration-300">
    <?php include('../includes/admheader.php'); ?>

    <div class="flex-grow pt-20">
        <div class="max-w-4xl mx-auto mt-6 p-6 bg-white shadow-lg rounded-xl">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-6 flex items-center justify-center">
                <i class="fas fa-bus mr-2 text-blue-500"></i> Bus Entry
            </h2>

            <form action="BusEntry" method="POST" class="space-y-6">
                <input type="hidden" name="BusID" value="<?php echo $NewBusID; ?>" />

                <div>
                    <label class="block text-sm font-medium text-gray-700">Bus No</label>
                    <input type="text" name="txtBusNo" placeholder="Enter Bus No" required
                           class="mt-1 w-full p-3 bg-white border border-gray-300 text-gray-800 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Path</label>
                    <input type="text" name="txtPath" placeholder="Enter Path" required
                           class="mt-1 w-full p-3 bg-white border border-gray-300 text-gray-800 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Card/QR Status</label>
                    <select name="txtCardQR" required
                            class="mt-1 w-full p-3 bg-white border border-gray-300 text-gray-800 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Card/QR Status</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Color</label>
                    <div class="mt-1 flex items-center space-x-4">
                        <select name="txtColor" id="colorSelect" required
                                class="w-full p-3 bg-white border border-gray-300 text-gray-800 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="" disabled selected>Select a color</option>
                            <option value="#0008ff">Blue</option>
                            <option value="#ff0000">Red</option>
                            <option value="#cc00ff">Purple</option>
                            <option value="#00ff00">Green</option>
                            <option value="#663300">Brown</option>
                        </select>
                        <span id="colorPreview" class="w-10 h-10 rounded-full border border-gray-300" style="background-color: #0008ff;"></span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between gap-4">
                    <button type="submit" name="btnSave" class="flex-1 bg-green-600 text-white py-3 px-6 rounded-lg hover:bg-green-700 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i> Save
                    </button>
                    <button type="reset" class="flex-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-undo-alt mr-2"></i> Reset
                    </button>
                    <a href="BusListForAdmin.php" class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-list mr-2"></i> View Bus List
                    </a>
                </div>
            </form>

            <hr class="my-8 border-gray-200">

            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-table mr-2 text-gray-600"></i> Recent Buses
            </h3>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 shadow-md rounded-lg">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Bus ID</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Bus No</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Path</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Card QR</th>
                            <th class="py-3 px-6 text-left text-sm font-semibold">Color</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($arr = mysqli_fetch_array($ret)): ?>
                            <tr class="hover:bg-gray-50:bg-slate-700 transition duration-200">
                                <td class="py-4 px-6"><?php echo $arr['BusID']; ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($arr['BusNo']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($arr['Path']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($arr['CardQR']); ?></td>
                                <td class="py-4 px-6">
                                    <span class="inline-block w-6 h-6 rounded-full mr-2" style="background-color: <?php echo htmlspecialchars($arr['Color']); ?>;"></span>
                                    <?php echo htmlspecialchars($arr['Color']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include('../includes/admfooter.php'); ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const colorSelect = document.getElementById("colorSelect");
            const colorPreview = document.getElementById("colorPreview");

            // Update preview on page load (default to Blue)
            colorPreview.style.backgroundColor = colorSelect.value || "#0008ff";

            // Update preview when selection changes
            colorSelect.addEventListener("change", function () {
                colorPreview.style.backgroundColor = this.value;
            });
        });
    </script>
</body>
</html>