<?php
session_start();
include('../config/database.php');

// Prevent direct access without BusID
if (!isset($_GET['BusID'])) {
    die("Invalid request. Bus ID is required.");
}

// Get and sanitize BusID
$BusID = mysqli_real_escape_string($connect, $_GET['BusID']);

// Fetch the existing bus details
$query = "SELECT * FROM bus WHERE BusID = ?";
$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "s", $BusID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$bus = mysqli_fetch_array($result);
mysqli_stmt_close($stmt);

if (!$bus) {
    die("Bus not found.");
}

// Check if the form is submitted for updating the bus
if (isset($_POST['btnSave'])) {
    $txtBusID = mysqli_real_escape_string($connect, $_POST['txtBusID']);
    $txtBusNo = mysqli_real_escape_string($connect, $_POST['txtBusNo']);
    $txtPath = mysqli_real_escape_string($connect, $_POST['txtPath']);
    $txtCardQR = mysqli_real_escape_string($connect, $_POST['txtCardQR']);
    $txtColor = mysqli_real_escape_string($connect, $_POST['txtColor']);

    // Validation consistent with BusEntry.php
    if (empty($txtBusNo) || empty($txtPath) || empty($txtCardQR) || empty($txtColor)) {
        echo "<script>alert('Please fill out all required fields.');</script>";
    } elseif (!in_array($txtColor, ['#0008ff', '#ff0000', '#cc00ff', '#00ff00', '#663300'])) {
        echo "<script>alert('Please select a valid color from the list.');</script>";
    } else {
        $updateQuery = "UPDATE bus SET BusNo = ?, Path = ?, CardQR = ?, Color = ? WHERE BusID = ?";
        $stmt = mysqli_prepare($connect, $updateQuery);
        mysqli_stmt_bind_param($stmt, "sssss", $txtBusNo, $txtPath, $txtCardQR, $txtColor, $txtBusID);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            $_SESSION['update_success'] = true;
            header("Location: BusEdit.php?BusID=" . urlencode($txtBusID));
            exit();
        } else {
            echo "<p class='text-red-600 text-center'>Something went wrong: " . mysqli_error($connect) . "</p>";
        }
        mysqli_stmt_close($stmt);
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
    <title>Edit Bus</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .admin-bg {
            background: linear-gradient(-45deg, #f8fafc, #e2e8f0, #f8fafc, #cbd5e1);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
            border-radius: 1.5rem;
        }
        .input-sleek {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: all 0.3s ease;
        }
        .input-sleek:focus {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02), 0 0 0 4px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }
        .gm-ui-hover-effect { display: none !important; }
        /* Popup Styles */
        .popup {
            position: fixed;
            top: 10px;
            left: 10px;
            background-color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            max-width: 90%;
        }
        .popup .icon {
            margin-right: 8px;
            font-size: 18px;
            color: #10b981; /* Green for success */
        }
        .popup .message {
            font-size: 14px;
            color: #374151;
            flex: 1;
        }
        .popup .dismiss {
            margin-left: 10px;
            color: #3b82f6;
            text-decoration: underline;
            cursor: pointer;
            font-size: 12px;
            white-space: nowrap;
        }
        .popup.hidden {
            animation: slideOutLeft 0.5s ease-out forwards;
        }
        @keyframes slideIn {
            0% { transform: translateY(-100%); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideOutLeft {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateX(-100%) translateY(0); opacity: 0; }
        }
        @keyframes slideOutRight {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateX(100%) translateY(0); opacity: 0; }
        }
        @keyframes slideOutTop {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(-100%); opacity: 0; }
        }
        @media (max-width: 640px) {
            .form-container {
                padding: 1rem;
            }
            .button-group button {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="admin-bg min-h-screen flex flex-col text-slate-800 transition-colors duration-300">
    <?php include('../includes/admheader.php'); ?>

    <main class="flex-grow flex items-center justify-center px-4 py-12 pt-28">
        <div class="w-full max-w-2xl glass-panel p-8 sm:p-10 form-container relative overflow-hidden">
            <!-- Decorative blur orbs -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-400 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-pulse"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-purple-400 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-pulse" style="animation-delay: 2s;"></div>

            <form action="BusEdit?BusID=<?php echo urlencode($BusID); ?>" method="POST" class="relative z-10">
                <div class="mb-8 border-b border-gray-200/50 pb-4">
                    <h2 class="text-3xl font-extrabold text-slate-800 flex items-center">
                        <i class="fas fa-bus-alt text-blue-500 mr-3"></i> Edit Bus Details
                    </h2>
                    <p class="text-slate-500 mt-2 font-light">Update configuration for Bus #<?php echo htmlspecialchars($bus['BusID']); ?></p>
                </div>
                
                <div class="space-y-6">
                    <!-- Bus ID (Readonly) -->
                    <div>
                        <label for="txtBusID" class="block text-sm font-semibold text-slate-700 mb-1">Bus ID</label>
                        <input type="text" name="txtBusID" id="txtBusID" value="<?php echo htmlspecialchars($bus['BusID']); ?>" readonly
                               class="w-full p-3 bg-gray-100/50 border border-gray-200 rounded-xl cursor-not-allowed text-gray-500 font-medium">
                    </div>

                    <!-- Bus No -->
                    <div>
                        <label for="txtBusNo" class="block text-sm font-semibold text-slate-700 mb-1">Bus No</label>
                        <input type="text" name="txtBusNo" id="txtBusNo" value="<?php echo htmlspecialchars($bus['BusNo']); ?>" required
                               class="w-full p-3 input-sleek rounded-xl text-slate-800 outline-none">
                    </div>

                    <!-- Path -->
                    <div>
                        <label for="txtPath" class="block text-sm font-semibold text-slate-700 mb-1">Path</label>
                        <input type="text" name="txtPath" id="txtPath" value="<?php echo htmlspecialchars($bus['Path']); ?>" required
                               class="w-full p-3 input-sleek rounded-xl text-slate-800 outline-none">
                    </div>

                    <!-- Card QR -->
                    <div>
                        <label for="txtCardQR" class="block text-sm font-semibold text-slate-700 mb-1">Card/QR Status</label>
                        <div class="relative">
                            <select name="txtCardQR" id="txtCardQR" required
                                    class="w-full p-3 input-sleek rounded-xl text-slate-800 outline-none appearance-none cursor-pointer">
                                <option value="" disabled>Select Card/QR Status</option>
                                <option value="Yes" <?php echo $bus['CardQR'] === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                <option value="No" <?php echo $bus['CardQR'] === 'No' ? 'selected' : ''; ?>>No</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Color -->
                    <div>
                        <label for="txtColor" class="block text-sm font-semibold text-slate-700 mb-1">Color Marker</label>
                        <div class="relative">
                            <select name="txtColor" id="txtColor" required
                                    class="w-full p-3 pl-12 input-sleek rounded-xl text-slate-800 outline-none appearance-none cursor-pointer">
                                <option value="" disabled>Select a color</option>
                                <option value="#0008ff" <?php echo $bus['Color'] === '#0008ff' ? 'selected' : ''; ?>>Blue</option>
                                <option value="#ff0000" <?php echo $bus['Color'] === '#ff0000' ? 'selected' : ''; ?>>Red</option>
                                <option value="#cc00ff" <?php echo $bus['Color'] === '#cc00ff' ? 'selected' : ''; ?>>Purple</option>
                                <option value="#00ff00" <?php echo $bus['Color'] === '#00ff00' ? 'selected' : ''; ?>>Green</option>
                                <option value="#663300" <?php echo $bus['Color'] === '#663300' ? 'selected' : ''; ?>>Brown</option>
                            </select>
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 w-4 h-4 rounded-full shadow-sm border border-white" style="background-color: <?php echo htmlspecialchars($bus['Color']); ?>;" id="colorIndicator"></div>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                <i class="fas fa-chevron-down text-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="mt-8 pt-6 border-t border-gray-200/50 flex flex-col sm:flex-row justify-end gap-3">
                    <button type="button" onclick="window.history.back();" class="px-6 py-3 bg-white hover:bg-gray-50 text-slate-600 font-semibold rounded-xl border border-gray-200 shadow-sm transition-all flex items-center justify-center order-3 sm:order-1 hover:-translate-y-0.5">
                        <i class="fas fa-arrow-left mr-2"></i> Cancel
                    </button>
                    <button type="reset" name="btnCancel" class="px-6 py-3 bg-red-50 hover:bg-red-100 text-red-600 font-semibold rounded-xl border border-red-100 shadow-sm transition-all flex items-center justify-center order-2 sm:order-2 hover:-translate-y-0.5">
                        <i class="fas fa-undo mr-2"></i> Reset
                    </button>
                    <button type="submit" name="btnSave" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-[0_4px_14px_rgba(59,130,246,0.4)] transition-all flex items-center justify-center order-1 sm:order-3 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(59,130,246,0.6)]">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?php include('../includes/admfooter.php'); ?>

    <!-- Popup Notification (Displayed on successful update) -->
    <?php if (isset($_SESSION['update_success']) && $_SESSION['update_success']): ?>
        <div id="notificationPopup" class="popup">
            <i class="fas fa-check-circle icon"></i>
            <span class="message">Bus successfully updated.</span>
            <span id="dismissBtn" class="dismiss">Dismiss</span>
        </div>
        <?php unset($_SESSION['update_success']); // Clear the session variable ?>
    <?php endif; ?>

    <script>
        document.addEventListener('keydown', function(event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault();
                document.querySelector('button[name="btnSave"]').click();
            }
        });

        // Popup Notification Logic
        document.addEventListener("DOMContentLoaded", function () {
            const notificationPopup = document.getElementById("notificationPopup");
            const dismissBtn = document.getElementById("dismissBtn");

            if (notificationPopup && dismissBtn) {
                let dismissDirection = 'left'; // Default slide direction

                // Show popup immediately if it exists
                notificationPopup.style.animation = 'slideIn 0.5s ease-out forwards';

                // Dismiss popup with animation
                dismissBtn.addEventListener("click", () => {
                    const animation = dismissDirection === 'left' ? 'slideOutLeft' :
                                    dismissDirection === 'right' ? 'slideOutRight' :
                                    'slideOutTop';
                    notificationPopup.style.animation = `${animation} 0.5s ease-out forwards`;
                    setTimeout(() => {
                        notificationPopup.classList.add("hidden");
                        notificationPopup.style.animation = '';
                    }, 500);

                    // Cycle through dismiss directions
                    dismissDirection = dismissDirection === 'left' ? 'right' :
                                    dismissDirection === 'right' ? 'top' : 'left';
                });

                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    if (!notificationPopup.classList.contains("hidden")) {
                        const animation = dismissDirection === 'left' ? 'slideOutLeft' :
                                        dismissDirection === 'right' ? 'slideOutRight' :
                                        'slideOutTop';
                        notificationPopup.style.animation = `${animation} 0.5s ease-out forwards`;
                        setTimeout(() => {
                            notificationPopup.classList.add("hidden");
                            notificationPopup.style.animation = '';
                        }, 500);
                    }
                }, 5000);
            }

            // Update color indicator on change
            const colorSelect = document.getElementById('txtColor');
            const colorIndicator = document.getElementById('colorIndicator');
            if (colorSelect && colorIndicator) {
                colorSelect.addEventListener('change', (e) => {
                    colorIndicator.style.backgroundColor = e.target.value;
                });
            }
        });
    </script>
</body>
</html>