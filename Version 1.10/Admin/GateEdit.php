<?php
session_start();
include('../config/database.php');
if (!isset($_SESSION['AdminName'])) {
    echo "<script>window.alert('You don\'t have permission to access this page.')</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Get the GateID from the query parameter
$GateID = $_GET['GateID'];

// Fetch the existing gate details
$query = "SELECT * FROM gate WHERE GateID='$GateID'";
$result = mysqli_query($connect, $query);
$gate = mysqli_fetch_array($result);

// Fetch townships for the dropdown
$TownshipQuery = "SELECT * FROM township";
$TownshipResult = mysqli_query($connect, $TownshipQuery);

// Handle the update functionality
if (isset($_POST['btnEdit'])) {
    $GateName = $_POST['GateName'];
    $Latitude = $_POST['Latitude'];
    $Longitude = $_POST['Longitude'];
    $Road = $_POST['Road'];
    $TownshipID = $_POST['TownshipID'];

    // Validation
    if (empty($GateName) || empty($Latitude) || empty($Longitude) || empty($Road) || empty($TownshipID)) {
        echo "<script>alert('Please fill out all required fields.');</script>";
    } else {
        // Update query to modify gate details
        $updateQuery = "
            UPDATE gate 
            SET GateName='$GateName', Latitude='$Latitude', Longitude='$Longitude', Road='$Road', TownshipID='$TownshipID' 
            WHERE GateID='$GateID'
        ";
        $updateResult = mysqli_query($connect, $updateQuery);

        // Check if the update was successful
        if ($updateResult) {
            echo "<script>alert('Gate successfully updated.');</script>";
            echo "<script>window.location='GateListForAdmin.php'</script>";
        } else {
            echo "<p>Error updating gate: " . mysqli_error($connect) . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Gate</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php include('../includes/admheader.php'); ?>

    <main class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-lg shadow-lg p-6">
            <form action="GateEdit?GateID=<?php echo $GateID; ?>" method="POST">
                <fieldset class="border border-gray-300 rounded-lg p-6">
                    <legend class="text-2xl font-bold text-gray-800 px-2">Edit Gate</legend>
                    <div class="space-y-4">
                        <!-- Gate Name -->
                        <div>
                            <label for="GateName" class="block text-sm font-medium text-gray-700">Gate Name</label>
                            <input type="text" name="GateName" value="<?php echo $gate['GateName']; ?>" required
                                   class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Latitude -->
                        <div>
                            <label for="Latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                            <input type="text" name="Latitude" value="<?php echo $gate['Latitude']; ?>" required
                                   class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Longitude -->
                        <div>
                            <label for="Longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                            <input type="text" name="Longitude" value="<?php echo $gate['Longitude']; ?>" required
                                   class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Road -->
                        <div>
                            <label for="Road" class="block text-sm font-medium text-gray-700">Road</label>
                            <input type="text" name="Road" value="<?php echo $gate['Road']; ?>" required
                                   class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Township Dropdown -->
                        <div>
                            <label for="TownshipID" class="block text-sm font-medium text-gray-700">Township</label>
                            <select name="TownshipID" required
                                    class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="" disabled>Select Township</option>
                                <?php while ($row = mysqli_fetch_assoc($TownshipResult)) {
                                    $selected = $row['TownshipID'] == $gate['TownshipID'] ? "selected" : "";
                                    echo "<option value='{$row['TownshipID']}' $selected>{$row['TownshipName']}</option>";
                                } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-6 flex space-x-4">
                        <button type="submit" name="btnEdit"
                                class="flex-1 bg-black text-white py-2 px-4 rounded-md hover:bg-gray-800 transition duration-300">
                            Update Gate
                        </button>
                        <button type="reset"
                                class="flex-1 bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 transition duration-300">
                            Reset
                        </button>
                        <button type="button" onclick="window.history.back();"
                                class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition duration-300">
                            Return
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </main>

    <?php include('../includes/admfooter.php'); ?>
</body>
</html>