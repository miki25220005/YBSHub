<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include database connection
if (!file_exists('config/database.php')) {
    die('Error: Database connection file not found.');
}
include('config/database.php');

// Public site loader
if (file_exists('includes/loader.php')) {
    include('includes/loader.php');
}

// Include maintenance check
if (!file_exists('includes/maintenance_check.php')) {
    die('Error: Maintenance check file not found.');
}
include('includes/maintenance_check.php');

// Check for active maintenance
checkMaintenance($connect);

// Set UTF-8 encoding
mysqli_set_charset($connect, "utf8mb4");

// Include centralized analytics tracking
if (file_exists('core/analytics.php')) {
    include_once('core/analytics.php');
}
if (function_exists('logStat')) {
    logStat($connect, 'page_view', 'Terms_and_Conditions');
}

// Set the last updated date to today's date
$lastUpdatedDate = date('F, Y'); // e.g., "September 25, 2025"
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Read the Terms and Conditions for using YBS Hub, your comprehensive guide to navigating Yangon Bus Service (YBS) and Yangon's public transport network.">
    <meta name="keywords" content="YBS Hub, Terms and Conditions, Yangon Bus Service, YBS, Yangon Public Transport, Yangon Bus">
    <meta name="author" content="YBS Hub Team">
    <title>Terms and Conditions - YBS Hub | Yangon Bus Service</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/Logo/YBS_Web_Logo.png">
    <style>
        html {
            scroll-behavior: smooth;
        }
        .collapse-toggle:checked ~ .collapse-content {
            max-height: 1000px;
        }
        .collapse-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }
        #back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 50;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        #back-to-top.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased">
    <?php
    if (!file_exists('includes/header.php')) {
        die('Error: Header file not found.');
    }
    include('includes/header.php');
    ?>

    <div class="max-w-4xl mx-auto p-6 sm:p-8 bg-white shadow-xl rounded-xl mt-10 mb-10" role="main">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-blue-600 border-b-4 border-blue-200 pb-3 inline-block">
                Terms and Conditions
            </h1>
            <p class="text-sm text-gray-500 mt-4">Last Updated: <?php echo htmlspecialchars($lastUpdatedDate); ?></p>
        </div>

        <!-- Introduction Section -->
        <section class="mb-8" aria-labelledby="introduction-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                <h2 id="introduction-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">1. Introduction</h2>
            </div>
            <p class="text-gray-600 mt-3 leading-relaxed">
                YBS Hub provides users with bus route information, bus gate locations, and real-time navigation assistance. Our services are intended to help users navigate public transport efficiently.
            </p>
        </section>

        <!-- Use of Our Services -->
        <section class="mb-8" aria-labelledby="services-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-user-check text-blue-500 text-xl"></i>
                <h2 id="services-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">2. Use of Our Services</h2>
            </div>
            <ul class="list-disc list-inside text-gray-600 mt-3 space-y-2 leading-relaxed">
                <li>You must use our website in accordance with applicable laws and regulations.</li>
                <li>You agree <strong class="text-red-600">not</strong> to misuse, hack, or attempt to disrupt our services.</li>
                <li>The information provided on YBS Hub is for reference only and may be subject to change.</li>
            </ul>
        </section>

        <!-- Location Services -->
        <section class="mb-8" aria-labelledby="location-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-map-marker-alt text-blue-500 text-xl"></i>
                <h2 id="location-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">3. Location Services</h2>
            </div>
            <div class="text-gray-600 mt-3 space-y-2 leading-relaxed">
                <p>- YBS Hub requests access to your device's location <strong class="text-blue-600">only</strong> for real-time navigation and to display nearby bus gates.</p>
                <p>- <strong class="text-blue-600">We do not store, collect, or share</strong> your location data.</p>
                <p>- Location access can be enabled or disabled through your device or browser settings.</p>
            </div>
        </section>

        <!-- Intellectual Property -->
        <section class="mb-8" aria-labelledby="intellectual-property-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-copyright text-blue-500 text-xl"></i>
                <h2 id="intellectual-property-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">4. Intellectual Property</h2>
            </div>
            <p class="text-gray-600 mt-3 leading-relaxed">
                All content on YBS Hub, including logos, text, graphics, and data, is the property of YBS Hub. You may not copy, distribute, or modify our content without written permission.
            </p>
            <p class="text-gray-600 mt-2 leading-relaxed">
                Unauthorized use of the YBS Hub logo is strictly prohibited and may result in <strong class="text-red-600">legal action</strong> by the Myanmar government.
            </p>
        </section>

        <!-- Limitation of Liability -->
        <section class="mb-8" aria-labelledby="liability-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-shield-alt text-blue-500 text-xl"></i>
                <h2 id="liability-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">5. Limitation of Liability</h2>
            </div>
            <p class="text-gray-600 mt-3 leading-relaxed">
                YBS Hub does not guarantee that all bus route data is accurate at all times. We are not liable for any delays, incorrect information, or inconvenience caused by using our website.
            </p>
        </section>

        <!-- Third-Party Links -->
        <section class="mb-8" aria-labelledby="third-party-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-link text-blue-500 text-xl"></i>
                <h2 id="third-party-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">6. Third-Party Links</h2>
            </div>
            <p class="text-gray-600 mt-3 leading-relaxed">
                Our website may contain links to third-party websites. We are not responsible for their content or privacy policies.
            </p>
        </section>

        <!-- Changes to These Terms -->
        <section class="mb-8" aria-labelledby="changes-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-edit text-blue-500 text-xl"></i>
                <h2 id="changes-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">7. Changes to These Terms</h2>
            </div>
            <p class="text-gray-600 mt-3 leading-relaxed">
                We may update these Terms and Conditions at any time. The latest version will always be available on our website. Your continued use of YBS Hub after changes means you accept the updated terms.
            </p>
        </section>

        <!-- Contact Us -->
        <section class="mb-8" aria-labelledby="contact-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-envelope text-blue-500 text-xl"></i>
                <h2 id="contact-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">8. Contact Us</h2>
            </div>
            <p class="text-gray-600 mt-3 leading-relaxed">
                If you have any questions about these Terms and Conditions, please contact us at: 
                <a href="mailto:support@YBSHub.com.mm" class="text-blue-600 hover:underline font-semibold" rel="noopener">support@YBSHub.com.mm</a>
            </p>
        </section>

        <!-- Advertisement Terms and Conditions -->
        <section class="mb-8" aria-labelledby="advertisement-heading">
            <div class="flex items-center space-x-3">
                <i class="fas fa-ad text-blue-500 text-xl"></i>
                <h2 id="advertisement-heading" class="text-xl sm:text-2xl font-semibold text-gray-800">9. Advertisement Terms and Conditions</h2>
            </div>
            <p class="text-gray-600 mt-3 leading-relaxed">
                YBS Hub may allow third-party advertisements to appear on our platform to support our operations. By submitting an advertisement request, you agree to the following conditions:
            </p>
            <ul class="list-disc list-inside text-gray-600 mt-3 space-y-2 leading-relaxed">
                <li>All advertisements must comply with applicable laws and regulations.</li>
                <li class="text-red-600 font-semibold">
                    Advertisements promoting illegal activities, including but not limited to, prohibited substances, fraudulent schemes, or unlawful services, are strictly prohibited.
                </li>
                <li class="text-red-600 font-semibold">
                    Advertisements related to gambling, betting, or lottery services will not be accepted under any circumstances.
                </li>
                <li class="text-red-600 font-semibold">
                    Multi-Level Marketing (MLM) products, services, or recruitment advertisements are not permitted.
                </li>
                <li>We reserve the right, at our sole discretion, to reject, remove, or request modifications to any advertisement without prior notice or explanation.</li>
                <li>Advertisers are solely responsible for ensuring that their advertisements do not infringe upon the rights of any third parties, including intellectual property rights and privacy rights.</li>
            </ul>
            <p class="text-gray-600 mt-4 leading-relaxed">
                Failure to comply with these terms may result in immediate removal of the advertisement and possible restrictions on future advertising opportunities.
            </p>
        </section>
    </div>

    <!-- Back to Top Button -->
    <a href="#top" id="back-to-top" class="bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-all duration-300 hidden" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </a>

    <?php
    if (!file_exists('includes/footer.php')) {
        die('Error: Footer file not found.');
    }
    include('includes/footer.php');
    ?>

    <script>
        // Back to Top Button Visibility
        window.addEventListener('scroll', function () {
            const backToTop = document.getElementById('back-to-top');
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
                backToTop.classList.remove('hidden');
            } else {
                backToTop.classList.remove('visible');
                backToTop.classList.add('hidden');
            }
        });
    </script>
</body>
</html>