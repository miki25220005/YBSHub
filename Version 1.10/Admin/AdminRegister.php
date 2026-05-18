<?php 
include('../config/database.php');

if (isset($_POST['btnregister'])) {
    $name = mysqli_real_escape_string($connect, $_POST['txtAdminName']);
    $email = mysqli_real_escape_string($connect, $_POST['txtAdminEmail']);
    $password = mysqli_real_escape_string($connect, $_POST['txtPassword']);
    $confirmPassword = mysqli_real_escape_string($connect, $_POST['txtConfirmPassword']);
    $Phno = mysqli_real_escape_string($connect, $_POST['txtAdminPhoneNumber']);
    $Bod = mysqli_real_escape_string($connect, $_POST['txtAdminbod']);
    $city = mysqli_real_escape_string($connect, $_POST['txtAdminCity']);
    $state = mysqli_real_escape_string($connect, $_POST['txtAdminState']);

    // Validate password match
    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match.');</script>";
        exit();
    }

    // Handle Photo Upload
    $photo = $_FILES['adminphoto']['name'];
    $Folder = "AdminPhoto/";  
    $FileName = $Folder . basename($photo);
    
    if (!move_uploaded_file($_FILES['adminphoto']['tmp_name'], $FileName)) {
        echo "<script>alert('Admin photo upload failed.');</script>";
        exit();
    }

    // Check if email already exists
    $stmt = $connect->prepare("SELECT EmailAddress FROM admin WHERE EmailAddress = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already exists. Please try a different email.');</script>";
        echo "<script>window.location='AdminRegister.php';</script>";
    } else {
        // Hash the password before storing
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new admin into pending approval with hashed password
        $stmt = $connect->prepare("INSERT INTO pending_admins (AdminName, EmailAddress, Password, PhoneNumber, BirthofDate, Photo, City, State) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $email, $hashedPassword, $Phno, $Bod, $FileName, $city, $state);
        
        if ($stmt->execute()) {
            echo "<script>alert('Registration request submitted. Waiting for approval.');</script>";
            echo "<script>window.location='AdminLogin.php';</script>";
        } else {
            echo "<script>alert('Error in registration. Try again.');</script>";
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - YBS Hub</title>
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

        /* Logo Animation */
        .logo {
            animation: bounce 1.2s ease infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-15px);
            }
            60% {
                transform: translateY(-7px);
            }
        }

        /* Glassmorphism Effect for Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
            max-width: 32rem; /* Approx A4 width (800px) */
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

        /* Button Hover Animation */
        .btn-hover {
            transition: all 0.3s ease;
        }
        .btn-hover:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        /* File Input Customization */
        .custom-file-input {
            position: relative;
            overflow: hidden;
        }
        .custom-file-input input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .custom-file-input label {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #3b82f6;
            color: white;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .custom-file-input label:hover {
            background-color: #2563eb;
        }

        /* Grid Layout for Desktop */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="form-card">
        <form action="AdminRegister" method="POST" enctype="multipart/form-data" class="glass-card">
            <div class="flex justify-center mb-6">
                <img src="../assets/images/BusLogo.png" alt="YBS Logo" class="w-20 h-20 logo">
            </div>
            <h2 class="text-2xl sm:text-3xl font-bold text-center text-gray-800 mb-6">YBS Hub Admin Registration</h2>

            <div class="form-grid">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="txtAdminName">Name</label>
                    <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           type="text" name="txtAdminName" id="txtAdminName" placeholder="Enter your name" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="txtAdminEmail">Email Address</label>
                    <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           type="email" name="txtAdminEmail" id="txtAdminEmail" placeholder="Enter Your Email" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="txtPassword">Password</label>
                    <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           type="password" name="txtPassword" id="txtPassword" placeholder="Enter Your Password" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="txtConfirmPassword">Confirm Password</label>
                    <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           type="password" name="txtConfirmPassword" id="txtConfirmPassword" placeholder="Confirm Your Password" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="txtAdminPhoneNumber">Phone Number</label>
                <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       type="tel" name="txtAdminPhoneNumber" id="txtAdminPhoneNumber" placeholder="Enter Your Phone Number" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="txtAdminbod">Birth of Date</label>
                <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       type="date" name="txtAdminbod" id="txtAdminbod" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="adminphoto">Photo</label>
                <div class="custom-file-input">
                    <input type="file" name="adminphoto" id="adminphoto" required>
                    <label for="adminphoto">Choose File</label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="txtAdminCity">City</label>
                <input class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       type="text" name="txtAdminCity" id="txtAdminCity" placeholder="Enter Your City" required>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="txtAdminState">State</label>
                <select class="input-field shadow-md appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        id="txtAdminState" name="txtAdminState" required>
                    <option value="">--Please choose a State--</option>
                    <option value="ayeyarwady">Ayeyarwady Region</option>
                    <option value="bago">Bago Region</option>
                    <option value="chin">Chin State</option>
                    <option value="kachin">Kachin State</option>
                    <option value="kayah">Kayah State</option>
                    <option value="kayin">Kayin State</option>
                    <option value="magway">Magway Region</option>
                    <option value="mandalay">Mandalay Region</option>
                    <option value="mon">Mon State</option>
                    <option value="naypyidaw">Naypyidaw</option>
                    <option value="rakhine">Rakhine State</option>
                    <option value="sagaing">Sagaing Region</option>
                    <option value="shan">Shan State</option>
                    <option value="tanintharyi">Tanintharyi Region</option>
                    <option value="yangon">Yangon Region</option>
                </select>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <input type="reset" name="btnreset" value="Reset" 
                       class="btn-hover w-full sm:w-1/2 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline">
                <input type="submit" name="btnregister" value="Register" 
                       class="btn-hover w-full sm:w-1/2 bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline">
            </div>

            <div class="text-center">
                <a href="AdminLogin.php" class="text-blue-500 hover:text-blue-700 font-medium transition duration-300 hover:underline">Do you have an account? Login Here!</a>
            </div>
        </form>
    </div>
</body>
</html>