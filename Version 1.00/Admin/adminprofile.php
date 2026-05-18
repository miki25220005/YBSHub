<?php
session_start();
include('../config/database.php');

// Check if admin is logged in
if (!isset($_SESSION['AdminID'])) {
    echo "<script>alert('You need to log in first.');</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Get admin details from the database
$AdminID = $_SESSION['AdminID'];
$query = "SELECT * FROM admin WHERE AdminID='$AdminID'";
$result = mysqli_query($connect, $query);

// Check if the query returned a result
if (mysqli_num_rows($result) > 0) {
    $admin = mysqli_fetch_array($result);
} else {
    echo "<script>alert('Admin not found. Please log in again.');</script>";
    echo "<script>window.location='AdminLogin.php';</script>";
    exit();
}

// Handle profile update form submission
if (isset($_POST['btnUpdate'])) {
    $AdminName = mysqli_real_escape_string($connect, $_POST['AdminName']);
    $EmailAddress = mysqli_real_escape_string($connect, $_POST['EmailAddress']);
    $PhoneNumber = mysqli_real_escape_string($connect, $_POST['PhoneNumber']);
    $BirthofDate = $_POST['BirthofDate'];
    $City = mysqli_real_escape_string($connect, $_POST['City']);
    $State = mysqli_real_escape_string($connect, $_POST['State']);

    // Handle profile photo upload
    if (!empty($_FILES['Photo']['name'])) {
        $Photo = $_FILES['Photo']['name'];
        $Folder = "AdminPhoto/";
        $FileName = $Folder . basename($Photo);
        move_uploaded_file($_FILES['Photo']['tmp_name'], $FileName);
    } else {
        $Photo = $admin['Photo']; // Keep existing photo if no new upload
    }

    // Update query
    $updateQuery = "
        UPDATE admin
        SET AdminName='$AdminName',
            EmailAddress='$EmailAddress',
            PhoneNumber='$PhoneNumber',
            BirthofDate='$BirthofDate',
            Photo='$Photo',
            City='$City',
            State='$State'
        WHERE AdminID='$AdminID'";
    
    if (mysqli_query($connect, $updateQuery)) {
        echo "<script>alert('Profile updated successfully!');</script>";
        echo "<script>window.location='adminprofile.php';</script>";
    } else {
        echo "<script>alert('Error updating profile. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Yangon Bus Service</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Gradient background for container */
        .profile-container {
            background: linear-gradient(135deg, #f9fafb, #e5e7eb);
        }
        /* Hover effect for profile picture */
        .profile-pic {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .profile-pic:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        /* Hover effect for buttons */
        .action-btn {
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        /* Focus styles for accessibility */
        .action-btn:focus, input:focus, select:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        /* Input field hover effect */
        input, select {
            transition: all 0.3s ease;
        }
        input:hover, select:hover {
            border-color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased flex flex-col">
    <?php include('../includes/admheader.php'); ?>

    <div class="flex-grow pt-20">
        <div class="max-w-lg mx-auto mt-6 p-6 bg-white shadow-xl rounded-xl profile-container">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-8 text-center flex items-center justify-center">
                <i class="fas fa-user-cog mr-2 text-blue-600"></i> Admin Profile
            </h1>

            <!-- Profile Picture -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <img src="<?php echo !empty($admin['Photo']) ? $admin['Photo'] : 'default_photo.png'; ?>" 
                         alt="Profile Photo" 
                         class="profile-pic w-36 h-36 rounded-full border-4 border-blue-100 shadow-lg object-cover">
                    <label for="photoUpload" class="absolute bottom-2 right-2 bg-gradient-to-r from-blue-500 to-blue-700 text-white p-3 rounded-full cursor-pointer hover:from-blue-600 hover:to-blue-800 transition duration-200 shadow-md">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="file" id="photoUpload" name="Photo" class="hidden" onchange="this.form.submit()" />

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-user mr-2 text-blue-500"></i> Name
                    </label>
                    <input type="text" name="AdminName" value="<?php echo htmlspecialchars($admin['AdminName']); ?>" required 
                           class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-envelope mr-2 text-blue-500"></i> Email Address
                    </label>
                    <input type="email" name="EmailAddress" value="<?php echo htmlspecialchars($admin['EmailAddress']); ?>" required 
                           class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-phone mr-2 text-blue-500"></i> Phone Number
                    </label>
                    <input type="text" name="PhoneNumber" value="<?php echo htmlspecialchars($admin['PhoneNumber']); ?>" required 
                           class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-blue-500"></i> Date of Birth
                    </label>
                    <input type="date" name="BirthofDate" value="<?php echo htmlspecialchars($admin['BirthofDate']); ?>" required 
                           class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-city mr-2 text-blue-500"></i> City
                    </label>
                    <input type="text" name="City" value="<?php echo htmlspecialchars($admin['City']); ?>" required 
                           class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i> State
                    </label>
                    <select name="State" required 
                            class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                        <option value="Yangon" <?php if ($admin['State'] == "Yangon") echo "selected"; ?>>Yangon</option>
                        <option value="Mandalay" <?php if ($admin['State'] == "Mandalay") echo "selected"; ?>>Mandalay</option>
                        <option value="Naypyidaw" <?php if ($admin['State'] == "Naypyidaw") echo "selected"; ?>>Naypyidaw</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row justify-between gap-4 mt-6">
                    <button type="submit" name="btnUpdate" 
                            class="action-btn flex-1 bg-gradient-to-r from-green-500 to-green-700 text-white py-3 px-6 rounded-lg hover:from-green-600 hover:to-green-800 focus:ring-4 focus:ring-green-300 flex items-center justify-center shadow-md">
                        <i class="fas fa-save mr-2"></i> Update Profile
                    </button>
                    <a href="dashboard.php" 
                       class="action-btn flex-1 bg-gradient-to-r from-gray-500 to-gray-700 text-white py-3 px-6 rounded-lg hover:from-gray-600 hover:to-gray-800 focus:ring-4 focus:ring-gray-300 flex items-center justify-center shadow-md">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include('../includes/admfooter.php'); ?>div
</body>
</html>