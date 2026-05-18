<?php 
    session_start();
    include('../config/database.php');

    // Get the TownshipID from the query parameter
    $TownshipID = $_GET['TownshipID'];

    // Fetch the existing township details
    $query = "SELECT * FROM township WHERE TownshipID='$TownshipID'";
    $result = mysqli_query($connect, $query);
    $township = mysqli_fetch_array($result);

    // Check if the form is submitted for updating the township
    if (isset($_POST['btnEdit'])) {
        $txtTownshipID = $_POST['txtTownshipID'];
        $txtTownshipName = $_POST['txtTownshipName'];

        // Update query to modify the township details
        $updateQuery = "UPDATE township 
                        SET TownshipName='$txtTownshipName' 
                        WHERE TownshipID='$txtTownshipID'";
        $result = mysqli_query($connect, $updateQuery);

        // Check if the update was successful
        if ($result) {
            echo "<script>alert('Township successfully updated.');</script>";
            echo "<script>window.location='TownshipListForAdmin.php'</script>";
        } else {
            echo "<p>Something went wrong in Township Edit: " . mysqli_error($connect) . "</p>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Township</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased flex flex-col">
    <?php include('../includes/admheader.php'); ?>

    <main class="flex-grow flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-2xl bg-white rounded-lg shadow-lg p-6">
            <form action="TownshipEdit?TownshipID=<?php echo $TownshipID ?>" method="POST">
                <fieldset class="border border-gray-300 rounded-lg p-6">
                    <legend class="text-2xl font-bold text-gray-800 px-2">Edit Township</legend>
                    <div class="space-y-4">
                        <!-- Township ID (Readonly) -->
                        <div>
                            <label for="txtTownshipID" class="block text-sm font-medium text-gray-700">Township ID</label>
                            <input type="text" name="txtTownshipID" id="txtTownshipID" value="<?php echo $township['TownshipID']; ?>" readonly
                                   class="mt-1 w-full p-2 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                        </div>

                        <!-- Township Name -->
                        <div>
                            <label for="txtTownshipName" class="block text-sm font-medium text-gray-700">Township Name</label>
                            <input type="text" name="txtTownshipName" id="txtTownshipName" value="<?php echo $township['TownshipName']; ?>" required
                                   class="mt-1 w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
                        <button type="submit" name="btnEdit" class="flex-1 bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 transition duration-300 flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i> Update Township
                        </button>
                        <button type="reset" name="btnCancel" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 transition duration-300 flex items-center justify-center">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </button>
                        <button type="button" onclick="window.history.back();" class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition duration-300 flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i> Return
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </main>

    <?php include('../includes/admfooter.php'); ?>

    <script>
        document.addEventListener('keydown', function(event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault();
                document.querySelector('button[name="btnEdit"]').click();
            }
        });
    </script>
</body>
</html>