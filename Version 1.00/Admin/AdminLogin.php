<?php 
session_start();
include('../config/database.php');

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['btnlogin'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "<script>alert('Invalid CSRF token.');</script>";
        echo "<script>window.location='AdminLogin.php';</script>";
        exit();
    }

    $email = trim($_POST['txtAdminEmail']);
    $password = trim($_POST['txtPassword']); // User's entered password

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Prepare statement to fetch admin data
        $stmt = $connect->prepare("SELECT AdminID, AdminName, Password, BirthofDate FROM admin WHERE EmailAddress = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($AdminID, $Aname, $storedPassword, $storedBirthDate);
            $stmt->fetch();

            // Compare hashed passwords
            if (password_verify($password, $storedPassword)) {  
                $_SESSION['AdminName'] = $Aname;
                $_SESSION['AdminID'] = $AdminID;

                // Redirect to AdminHome
                echo "<script>window.location='index.php';</script>";
                exit();
            } else {
                $error = "Incorrect Password.";
            }
        } else {
            $error = "Email Not Found.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Login - YBS Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        /* Gradient Background Animation */
        body {
            background: linear-gradient(135deg, #6b7280, #3b82f6, #1e3a8a);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
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

        /* Glassmorphism Effect for Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Password Toggle Button Styling */
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            transition: color 0.3s ease;
        }
        .password-toggle:hover {
            color: #3b82f6;
        }

        /* Caps Lock Warning Styling */
        .caps-lock-warning {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: rgba(220, 38, 38, 0.9); /* Red with transparency */
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 2px;
            animation: fadeIn 0.3s ease forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .caps-lock-warning.show {
            display: block;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md form-card">
        <!-- Error Message -->
        <?php if (isset($error)): ?>
            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg error-message">
                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="AdminLogin" method="POST" class="glass-card shadow-2xl rounded-lg px-6 sm:px-8 pt-6 pb-8 mb-4">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <img src="../assets/images/BusLogo.png" alt="YBS Logo" class="w-24 h-24 logo">
            </div>
            
            <!-- Title -->
            <h2 class="text-2xl sm:text-3xl font-bold text-center text-gray-800 mb-6 sm:mb-8">YBS Hub Admin Login</h2>
            
            <!-- Email Field -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email Address
                </label>
                <input class="input-field shadow-md appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       type="email" name="txtAdminEmail" id="email" placeholder="Enter Your Email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <!-- Password Field with Toggle and Caps Lock Warning -->
            <div class="mb-8 relative">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <div class="password-container">
                    <input class="input-field shadow-md appearance-none border rounded-lg w-full py-3 px-4 pr-12 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           type="password" name="txtPassword" id="password" placeholder="Enter Your Password" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    <div class="caps-lock-warning" id="capsLockWarning">Caps Lock is on</div>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <input type="reset" name="btnreset" value="Reset" 
                       class="btn-hover w-full sm:w-1/2 bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline">
                <input type="submit" name="btnlogin" value="Login" 
                       class="btn-hover w-full sm:w-1/2 bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline">
            </div>
            
            <!-- Forgot Password and Register Links -->
            <div class="text-center space-y-2">
                <a href="ChangePassword.php" class="text-blue-500 hover:text-blue-700 font-medium transition duration-300 hover:underline">Forgot Password?</a>
                <a href="AdminRegister.php" class="text-blue-500 hover:text-blue-700 font-medium transition duration-300 hover:underline">Don't have an account? Register Here!</a>
            </div>
        </form>
    </div>

    <!-- JavaScript for Password Toggle and Caps Lock Detection -->
    <script>
        // Password Toggle Functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.classList.toggle('fa-eye');
            togglePassword.classList.toggle('fa-eye-slash');
        });

        // Caps Lock Detection
        const capsLockWarning = document.getElementById('capsLockWarning');

        passwordInput.addEventListener('keyup', (e) => {
            const isCapsLock = e.getModifierState('CapsLock');
            if (isCapsLock && passwordInput.value.length > 0) {
                capsLockWarning.classList.add('show');
            } else {
                capsLockWarning.classList.remove('show');
            }
        });

        // Hide warning when input is empty
        passwordInput.addEventListener('input', () => {
            if (passwordInput.value.length === 0) {
                capsLockWarning.classList.remove('show');
            }
        });
    </script>
</body>
</html>