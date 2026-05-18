<?php
session_start();
include('config/database.php');
if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}
include('includes/maintenance_check.php');

// Check for active maintenance
checkMaintenance($connect);

// Human Checker Logic - REFRESH ON PAGE LOAD
if (!isset($_SESSION['human_verified']) || $_SESSION['human_verified'] !== true) {
    // Generate a new CAPTCHA question on every page load unless it's already verified
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['captcha_question'] = "$num1 + $num2 = ?";
    $_SESSION['captcha_answer'] = $num1 + $num2;
}

if (isset($_POST['btnSubmit'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $message = mysqli_real_escape_string($connect, $_POST['message']);
    $captcha_answer = isset($_POST['captcha_answer']) ? (int)$_POST['captcha_answer'] : null;

    if (empty($name) || empty($email) || empty($message)) {
        echo "<script>alert('Please fill out all required fields.');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address.');</script>";
    } elseif (!isset($_SESSION['human_verified']) || $_SESSION['human_verified'] !== true) {
        if ($captcha_answer === null || $captcha_answer !== $_SESSION['captcha_answer']) {
            echo "<script>alert('Incorrect CAPTCHA answer. Please try again.');</script>";
            
            // To ensure the CAPTCHA refreshes even on a failed submission
            $num1 = rand(1, 10);
            $num2 = rand(1, 10);
            $_SESSION['captcha_question'] = "$num1 + $num2 = ?";
            $_SESSION['captcha_answer'] = $num1 + $num2;
            
        } else {
            $_SESSION['human_verified'] = true;
            unset($_SESSION['captcha_question']);
            unset($_SESSION['captcha_answer']);
            processForm($connect, $name, $email, $message);
        }
    } else {
        processForm($connect, $name, $email, $message);
    }
}

function processForm($connect, $name, $email, $message) {
    // Use a prepared statement for security
    $insertQuery = "INSERT INTO feedback (`Name`, `Email`, `Message`) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($connect, $insertQuery);
    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $message);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
            localStorage.removeItem('contactFormData');
            alert('Thank you for your feedback!');
            window.location='Contact_Us.php';
        </script>";
    } else {
        echo "<script>alert('Error submitting feedback. Please try again.');</script>";
        echo "<p>Error: " . mysqli_error($connect) . "</p>";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact the YBS Hub team for feedback, questions, or suggestions about the Yangon Bus Service (YBS) and public transport in Yangon.">
    <meta name="keywords" content="YBS Hub Contact, Yangon Bus Service, YBS, Yangon Public Transport, Yangon Bus">
    <title>Contact Us - YBS Hub | Yangon Bus Service</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/Logo/web_logo.png">
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased flex flex-col">
    <?php include('includes/header.php'); ?>

    <div class="flex-grow pt-20">
        <div class="max-w-lg mx-auto mt-6 p-6 bg-white shadow-xl rounded-xl">
            <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center flex items-center justify-center">
                <i class="fas fa-envelope mr-2 text-blue-500"></i> Contact Us
            </h1>

            <form method="POST" class="space-y-6" id="contactForm">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name" placeholder="Your Name" required
                           class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" placeholder="Your Email" required
                           class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea name="message" id="message" placeholder="Your Message" required
                              class="mt-1 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm h-32 resize-y"></textarea>
                </div>

                <?php if (!isset($_SESSION['human_verified']) || $_SESSION['human_verified'] !== true): ?>
                    <div class="bg-gray-50 p-4 rounded-lg shadow-inner border border-gray-200">
                        <label class="block text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-user-check mr-2 text-blue-500"></i> Are You Human?
                        </label>
                        <div class="mt-2 flex items-center justify-between">
                            <p id="captcha-question" class="text-gray-600 text-lg font-semibold bg-white px-3 py-1 rounded-md shadow-sm">
                                <?php echo $_SESSION['captcha_question']; ?>
                            </p>
                            <button type="button" id="refresh-captcha" 
                                    class="text-gray-500 hover:text-blue-600 focus:outline-none transition duration-200">
                                <i class="fas fa-sync-alt text-lg"></i>
                            </button>
                        </div>
                        <input type="text" name="captcha_answer" id="captcha_answer" placeholder="Enter your answer" required
                               class="mt-3 w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm bg-white">
                    </div>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row justify-between gap-4">
                    <a href="index.php" 
                       class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 text-white py-3 px-6 rounded-lg hover:from-gray-600 hover:to-gray-700 transition duration-300 flex items-center justify-center shadow-md transform hover:scale-105">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Home
                    </a>
                    <button type="submit" name="btnSubmit" 
                            class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-6 rounded-lg hover:from-blue-700 hover:to-blue-800 transition duration-300 flex items-center justify-center shadow-md transform hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Restore form data from localStorage
            const form = document.getElementById("contactForm");
            const nameInput = document.getElementById("name");
            const emailInput = document.getElementById("email");
            const messageInput = document.getElementById("message");
            const captchaInput = document.getElementById("captcha_answer");

            const savedData = JSON.parse(localStorage.getItem("contactFormData")) || {};
            if (savedData.name) nameInput.value = savedData.name;
            if (savedData.email) emailInput.value = savedData.email;
            if (savedData.message) messageInput.value = savedData.message;
            if (captchaInput && savedData.captcha_answer) captchaInput.value = savedData.captcha_answer;

            // Save form data to localStorage on input
            form.addEventListener("input", function () {
                const formData = {
                    name: nameInput.value,
                    email: emailInput.value,
                    message: messageInput.value,
                    captcha_answer: captchaInput ? captchaInput.value : ""
                };
                localStorage.setItem("contactFormData", JSON.stringify(formData));
            });

            // CAPTCHA refresh logic
            const refreshCaptcha = document.getElementById("refresh-captcha");
            const captchaQuestion = document.getElementById("captcha-question");

            if (refreshCaptcha && captchaQuestion) {
                refreshCaptcha.addEventListener("click", function () {
                    const icon = refreshCaptcha.querySelector("i");
                    icon.classList.add("fa-spin");

                    fetch("./refresh_captcha.php", { 
                        method: "POST",
                        headers: { "Content-Type": "application/json" }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        captchaQuestion.textContent = data.question;
                        if (captchaInput) captchaInput.value = ""; // Clear CAPTCHA input on refresh
                        icon.classList.remove("fa-spin");

                        // Update localStorage after CAPTCHA refresh
                        const formData = JSON.parse(localStorage.getItem("contactFormData")) || {};
                        formData.captcha_answer = "";
                        localStorage.setItem("contactFormData", JSON.stringify(formData));
                    })
                    .catch(error => {
                        console.error("Fetch Error:", error.message);
                        alert("Failed to refresh CAPTCHA: " + error.message);
                        icon.classList.remove("fa-spin");
                    });
                });
            } else {
                console.warn("Refresh CAPTCHA button or question element not found!");
            }
        });
    </script>
</body>
</html>