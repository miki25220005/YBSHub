<?php
session_start();
include('../config/database.php'); // Include your database connection file

// Set the server's time zone to avoid mismatches
date_default_timezone_set('Asia/Yangon'); // Adjust to your server's time zone

// Get the current time on the server
$currentTime = time();

// Query the database for the active maintenance schedule
$query = "SELECT * FROM maintenance_schedule WHERE is_active = 1 AND start_time <= NOW() AND end_time >= NOW() LIMIT 1";
$result = mysqli_query($connect, $query);

$maintenanceActive = false;
$maintenanceEndTime = null;
$formattedEndDate = null;
$timeRemainingSeconds = 0;

if (mysqli_num_rows($result) > 0) {
    $maintenanceActive = true;
    $row = mysqli_fetch_assoc($result);
    $maintenanceEndTime = strtotime($row['end_time']); // Convert end_time to timestamp
    if ($maintenanceEndTime !== false && $maintenanceEndTime > $currentTime) {
        $timeRemainingSeconds = $maintenanceEndTime - $currentTime; // Calculate remaining seconds
        $formattedEndDate = date("d F Y, h:i A", $maintenanceEndTime); // Format for display
    } else {
        // If the end time is invalid or in the past, redirect to index
        header("Location: ../index"); // Updated to extensionless URL
        exit();
    }
} else {
    // If no active maintenance, redirect to index
    header("Location: ../index"); // Updated to extensionless URL
    exit();
}

// Debug: Log the time values for verification
echo "<script>console.log('Current Time: $currentTime');</script>";
echo "<script>console.log('Maintenance End Time: $maintenanceEndTime');</script>";
echo "<script>console.log('Time Remaining Seconds: $timeRemainingSeconds');</script>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add meta refresh tag to reload the page every 60 seconds -->
    <meta http-equiv="refresh" content="60;url=/maintenance">
    <title>System Under Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Add Google Fonts for English and Myanmar -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Roboto:wght@400;500&family=Noto+Sans+Myanmar:wght@400;500&display=swap" rel="stylesheet">
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 1.5s linear infinite;
        }
        .bg-maintenance {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        .inquiry-link {
            transition: transform 0.2s ease;
        }
        .inquiry-link:hover {
            transform: scale(1.1);
        }
        /* Apply Google Fonts */
        body {
            font-family: 'Roboto', sans-serif;
        }
        h1, h2, h3 {
            font-family: 'Poppins', sans-serif;
        }
        /* Myanmar font styling */
        .myanmar-text {
            font-family: 'Noto Sans Myanmar', sans-serif;
            line-height: 1.6;
        }
        /* Adjust spacing for bilingual text */
        .bilingual-section p {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="bg-maintenance min-h-screen flex items-center justify-center">
    <div class="max-w-lg mx-auto p-8 bg-white shadow-lg rounded-lg text-center">
        <div class="mb-6">
            <i class="fas fa-tools text-6xl text-yellow-500 mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">System Under Maintenance</h1>
        </div>
        <div class="bilingual-section text-gray-600 mb-4">
            <p>We are currently performing scheduled maintenance to improve your experience. The system will be back online shortly. Thank you for your patience!</p>
            <p class="myanmar-text">ကျွန်ုပ်တို့သည် သင်ၤအတွေ့အကြုံကို ပိုမိုကောင်းမွန်စေရန် စီစဉ်ထားသော ပြုပြင်ထိန်းသိမ်းမှုကို လက်ရှိလုပ်ဆောင်လျက်ရှိပါသည်။ Website သည် မကြာမီပြန်လည်အသုံးပြနိုင်မည်ဖြစ်ပါသည်။ သင်ၤစိတ်ရှည်မှုအတွက် ကျေးဇူးတင်ပါသည်။</p>
        </div>
        <p class="text-gray-700 font-semibold mb-6">
            Scheduled maintenance will end on: <br>
            <span class="text-blue-600"><?php echo $formattedEndDate; ?></span>
        </p>
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-700">Time remaining:</h2>
            <div id="countdown" class="text-2xl font-mono text-blue-600 mt-2" aria-live="polite">
                <span id="timer">Loading...</span>
            </div>
        </div>
        <div class="flex justify-center mb-6">
            <i class="fas fa-spinner text-2xl text-blue-500 spinner"></i>
        </div>
        <!-- Inquiry Links -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Need Assistance?</h3>
            <div class="flex justify-center space-x-6">
                <a href="mailto:support@example.com" class="inquiry-link" title="Contact us via Gmail">
                    <i class="fas fa-envelope text-3xl text-red-500 hover:text-red-600"></i>
            </a>
                <a href="https://t.me/+your_telegram_link" target="_blank" class="inquiry-link" title="Contact us via Telegram">
                    <i class="fab fa-telegram-plane text-3xl text-blue-500 hover:text-blue-600"></i>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Time remaining in seconds (calculated on the server)
        let timeRemainingSeconds = <?php echo $timeRemainingSeconds; ?>;

        function updateCountdown() {
            // Validate time remaining
            if (timeRemainingSeconds <= 0) {
                // Maintenance is over, redirect to user dashboard
                document.getElementById('countdown').innerHTML = '<p class="text-green-600">Maintenance is complete! Redirecting...</p>';
                setTimeout(() => {
                    window.location.href = '../index'; // Updated to extensionless URL
                }, 2000);
                return;
            }

            // Calculate hours, minutes, and seconds (no days)
            const hours = Math.floor(timeRemainingSeconds / (60 * 60));
            const minutes = Math.floor((timeRemainingSeconds % (60 * 60)) / 60);
            const seconds = Math.floor(timeRemainingSeconds % 60);

            // Format the time with leading zeros (HH:MM:SS)
            const formattedTime = 
                (hours < 10 ? "0" + hours : hours) + ":" + 
                (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                (seconds < 10 ? "0" + seconds : seconds);

            // Update the timer display
            document.getElementById('timer').textContent = formattedTime;

            // Decrement the time remaining
            timeRemainingSeconds--;

            // Update every second
            setTimeout(updateCountdown, 1000);
        }

        // Start the countdown when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (timeRemainingSeconds > 0) {
                updateCountdown();
            } else {
                document.getElementById('countdown').innerHTML = '<p class="text-red-600">Error: Invalid time remaining.</p>';
            }
        });
    </script>
</body>
</html>