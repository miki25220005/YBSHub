<?php 
include('../config/database.php');

if (isset($_POST['btnChangePassword'])) {
    $email = trim($_POST['txtAdminEmail']);
    $birthDate = trim($_POST['txtBirthofDate']);
    $newPassword = trim($_POST['txtNewPassword']);

    // Basic validation
    if (empty($email) || empty($birthDate) || empty($newPassword)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Verify email and birth date
        $stmt = $connect->prepare("SELECT AdminID, Password, BirthofDate FROM admin WHERE EmailAddress = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($AdminID, $storedPassword, $storedBirthDate);
            $stmt->fetch();

            if ($birthDate === $storedBirthDate) {
                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update the password in the database
                $updateStmt = $connect->prepare("UPDATE admin SET Password = ? WHERE AdminID = ?");
                $updateStmt->bind_param("si", $hashedPassword, $AdminID);

                if ($updateStmt->execute()) {
                    echo "<script>alert('Password changed successfully. Please login with your new password.');</script>";
                    echo "<script>window.location='AdminLogin.php';</script>";
                } else {
                    $error = "Error updating password. Try again.";
                }
                $updateStmt->close();
            } else {
                $error = "Incorrect birth date.";
            }
        } else {
            $error = "Email not found.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password - YBS Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        /* Gradient Background Animation */
        body {
            background: linear-gradient(135deg, #6b7280, #3b82f6, #1e3a8a);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Form Card Animation */
        .form-card {
            animation: slideUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Glassmorphism Effect for Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
            max-width: 28rem;
            width: 100%;
        }

        /* Input Focus Animation */
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3) !important;
            transform: scale(1.02);
        }

        /* Error Message Animation */
        .error-message {
            animation: slideIn 0.5s ease forwards;
            opacity: 0;
            transform: translateX(-20px);
        }
        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Button Hover Animation */
        .btn-hover {
            transition: all 0.3s ease;
        }
        .btn-hover:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="form-card">
        <!-- Error Message -->
        <?php if (isset($error)): ?>
            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg error-message">
                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Change Password Form -->
        <form action="ChangePassword" method="POST" class="glass-card shadow-2xl rounded-lg px-6 sm:px-8 pt-6 pb-8 mb-4">
            <div class="flex justify-center mb-6">
                <img src="../assets/images/BusLogo.png" alt="YBS Logo" class="w-20 h-20">
            </div>
            
            <h2 class="text-2xl sm:text-3xl font-bold text-center text-gray-800 mb-6">Change Password</h2>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="txtAdminEmail">Email Address</label>
                <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       type="email" name="txtAdminEmail" id="txtAdminEmail" placeholder="Enter Your Email" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="txtBirthofDate">Birth of Date</label>
                <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       type="date" name="txtBirthofDate" id="txtBirthofDate" required>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="txtNewPassword">New Password</label>
                <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       type="password" name="txtNewPassword" id="txtNewPassword" placeholder="Enter New Password" required>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <input type="reset" name="btnreset" value="Reset" 
                       class="btn-hover w-full sm:w-1/2 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline">
                <input type="submit" name="btnChangePassword" value="Change Password" 
                       class="btn-hover w-full sm:w-1/2 bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline">
            </div>

            <div class="text-center">
                <a href="AdminLogin.php" class="text-blue-500 hover:text-blue-700 font-medium transition duration-300 hover:underline">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>