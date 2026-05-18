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

// Detect device type
function detectDeviceType() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
        return 'Tablet';
    }
    if (preg_match('/mobile/i', $userAgent)) {
        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/android/i', $userAgent)) {
            return 'Android';
        }
        return 'Mobile';
    }
    if (preg_match('/windows|macintosh|linux/i', $userAgent)) {
        return 'Computer';
    }
    return 'Unknown';
}

$deviceType = detectDeviceType();

// Log stats
function logStat($connect, $action_type, $township_id = null, $action_value = '', $device_type = 'Unknown') {
    $query = "INSERT INTO website_stats (township_id, action_type, action_value, device_type) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($connect, $query)) {
        mysqli_stmt_bind_param($stmt, 'isss', $township_id, $action_type, $action_value, $device_type);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log('Failed to prepare statement for website_stats: ' . mysqli_error($connect));
    }
}
logStat($connect, 'page_view', null, 'About_Us', $deviceType);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learn about YBS Hub, the most reliable and smart companion for navigating the Yangon Bus Service (YBS). Explore Yangon public transport with ease.">
    <meta name="keywords" content="YBS Hub, Yangon Bus Service, YBS, Yangon Public Transport, Yangon transit, Yangon Bus, public transport">
    <meta name="author" content="YBS Hub Team">
    <title>About Us - YBS Hub | Yangon Bus Service</title>
    <link rel="icon" type="image/png" href="assets/images/Logo/web_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        .hero-section {
            background-image: linear-gradient(to right, rgba(163, 213, 230, 0.8), rgba(197, 146, 230, 0.8)), url('Image/113.jpg');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.4s ease-in-out;
        }
        .modal.show {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transition: transform 0.4s ease-in-out;
            transform: scale(0.8);
        }
        .modal.show .modal-content {
            transform: scale(1);
        }
        .modal-close {
            position: absolute;
            top: 30px;
            right: 30px;
            color: white;
            font-size: 3rem;
            cursor: pointer;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.8);
            transition: color 0.3s ease;
        }
        .modal-close:hover {
            color: #f87171;
        }
        .card-hover {
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        @media (max-width: 767px) {
            .hero-section {
                height: 300px;
            }
            .h-72-mobile {
                height: 288px !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased text-gray-700">
    <?php
    if (!file_exists('includes/header.php')) {
        die('Error: Header file not found.');
    }
    include('includes/header.php');
    ?>

    <header class="hero-section mb-16 shadow-xl" role="banner">
        <div class="text-center text-white max-w-4xl mx-auto px-4" data-aos="fade-up">
            <h1 class="text-6xl md:text-7xl font-extrabold leading-none tracking-tight">
                About <span class="text-yellow-300">YBS Hub</span>
            </h1>
            <p class="mt-4 text-xl md:text-2xl font-light opacity-90">
                Your trusted, smart companion for navigating Yangon's entire bus network.
            </p>
            <a href="index" class="inline-block mt-8 bg-white text-blue-600 font-bold px-8 py-3 rounded-full shadow-lg hover:bg-gray-200 transition-all duration-300 transform hover:scale-105" aria-label="Find a bus route">
                <i class="fas fa-bus mr-2"></i> Find a Route Now
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <section class="mb-16 bg-white p-8 md:p-12 rounded-xl shadow-lg" data-aos="fade-up" aria-labelledby="vision-heading">
            <div class="flex flex-col md:flex-row items-center gap-10">
                <div class="md:w-1/2 relative order-2 md:order-1 card-hover" data-aos="fade-right">
                    <img src="assets/images/Image/MainPhoto.jpg" alt="Yangon Bus" class="w-full h-72-mobile md:h-80 object-cover rounded-xl shadow-xl border-4 border-gray-100" loading="lazy">
                </div>
                <div class="md:w-1/2 order-1 md:order-2" data-aos="fade-left">
                    <h2 id="vision-heading" class="text-3xl font-bold text-gray-800 mb-5 border-l-4 border-blue-600 pl-4">Our Vision for Yangon</h2>
                    <p class="text-lg text-gray-600 mb-4">
                        <span class="font-semibold text-blue-700">YBS Hub</span> was founded to solve the complexity of public transit in Yangon. We aim to be the simplest, most reliable platform for everyone—from first-time visitors to seasoned daily commuters.
                    </p>
                    <ul class="space-y-2 text-gray-600 list-none pl-0">
                        <li class="flex items-start"><i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i> <strong>Clarity: </strong> Unambiguous route and stop information.</li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i> <strong>Accessibility: </strong> Usable on any device, anywhere in Yangon.</li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i> <strong>Modernity: </strong> Integrating payment and system updates seamlessly.</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="mb-16 p-8 rounded-xl shadow-inner bg-blue-50" aria-labelledby="journey-heading">
            <h2 id="journey-heading" class="text-3xl font-bold text-gray-800 mb-10 text-center" data-aos="zoom-in">
                <i class="fas fa-history mr-3 text-blue-600"></i> The Journey of YBS
            </h2>
            <div class="relative max-w-4xl mx-auto">
                <div class="hidden md:block absolute left-1/2 w-1 h-full bg-blue-300 transform -translate-x-1/2"></div>
                <div class="flex flex-col md:flex-row items-start mb-8" data-aos="fade-up">
                    <div class="md:w-1/2 md:pr-10 text-center md:text-right">
                        <div class="bg-white p-6 rounded-xl shadow-lg card-hover border-r-4 border-blue-500 md:border-r-0 md:border-l-0">
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Early Days: မထသ (Ma Hta Tha)</h3>
                            <p class="text-gray-600">
                                The foundation of Yangon's public transport system, marked by the မ ထ သ (Ma Hta Tha) bus name. This period established the initial bus routes and the groundwork for the city's mobility.
                            </p>
                        </div>
                    </div>
                    <div class="hidden md:block absolute left-1/2 top-4 w-4 h-4 bg-blue-600 rounded-full transform -translate-x-1/2"></div>
                    <div class="md:w-1/2 mt-4 md:mt-0 md:pl-10">
                        <img src="assets/images/Image/MaHtaTha.jpg" alt="Ma Hta Tha Bus" class="w-full h-48 object-cover rounded-lg shadow-md mt-4 md:mt-0" loading="lazy">
                    </div>
                </div>
                <div class="flex flex-col md:flex-row-reverse items-start mt-12" data-aos="fade-up">
                    <div class="md:w-1/2 md:pl-10 text-center md:text-left">
                        <div class="bg-white p-6 rounded-xl shadow-lg card-hover border-l-4 border-blue-500 md:border-l-0 md:border-r-0">
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Present: Modern YBS</h3>
                            <p class="text-gray-600">
                                Today, the Yangon Bus Service (YBS) operates a revitalized, modern fleet. Our platform was launched in <strong>October 2024</strong> to complement this modern system with accurate digital information.
                            </p>
                        </div>
                    </div>
                    <div class="hidden md:block absolute left-1/2 top-1/2 w-4 h-4 bg-blue-600 rounded-full transform -translate-x-1/2 -translate-y-1/2"></div>
                    <div class="md:w-1/2 mt-4 md:mt-0 md:pr-10">
                        <img src="assets/images/Image/YBS.jpg" alt="Modern YBS Bus" class="w-full h-48 object-cover rounded-lg shadow-md mt-4 md:mt-0" loading="lazy">
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-16" aria-labelledby="features-heading">
            <h2 id="features-heading" class="text-3xl font-bold text-gray-800 mb-10 text-center" data-aos="fade-up">
                <i class="fas fa-cogs mr-3 text-blue-600"></i> Core Features
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-lg text-center card-hover hover:border-b-4 hover:border-blue-600" data-aos="zoom-in" data-aos-delay="100">
                    <div class="p-4 bg-blue-100 rounded-full inline-block mb-4">
                        <i class="fas fa-map-marked-alt text-4xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-gray-800 mb-2">Interactive Route Maps</h3>
                    <p class="text-gray-500">Easily trace bus lines across Yangon with visual, detailed map overlays.</p>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-lg text-center card-hover hover:border-b-4 hover:border-blue-600" data-aos="zoom-in" data-aos-delay="200">
                    <div class="p-4 bg-blue-100 rounded-full inline-block mb-4">
                        <i class="fas fa-road text-4xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-gray-800 mb-2">Detailed Stop Data</h3>
                    <p class="text-gray-500">Access comprehensive information about every bus stop, including connecting routes.</p>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-lg text-center card-hover hover:border-b-4 hover:border-blue-600" data-aos="zoom-in" data-aos-delay="300">
                    <div class="p-4 bg-blue-100 rounded-full inline-block mb-4">
                        <i class="fas fa-money-check-alt text-4xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-gray-800 mb-2">Payment Compatibility</h3>
                    <p class="text-gray-500">Know exactly which buses accept YPS card, cash, or mobile QR payments.</p>
                </div>
            </div>
        </section>

        <section class="text-center mb-16 bg-gray-100 p-12 rounded-xl border border-gray-200" data-aos="fade-up" aria-labelledby="contact-heading">
            <h2 id="contact-heading" class="text-3xl font-extrabold text-gray-800 mb-4">Have Questions or Suggestions?</h2>
            <p class="text-lg text-gray-600 mb-8 max-w-3xl mx-auto">Your feedback drives our improvements. We're committed to making Yangon transit easier for you.</p>
            <div>
                <a href="Contact_Us" class="inline-block bg-blue-600 text-white font-bold px-8 py-3 rounded-full shadow-md hover:bg-blue-700 transition-colors duration-300 transform hover:scale-105 mr-4" aria-label="Contact us">
                    <i class="fas fa-envelope mr-2"></i> Get in Touch
                </a>
                <a href="index" class="inline-block border-2 border-gray-400 text-gray-700 font-bold px-8 py-3 rounded-full shadow-md hover:bg-gray-200 transition-colors duration-300" aria-label="Go back to home">
                    Go Back to Home
                </a>
            </div>
        </section>

        <section class="p-8 rounded-xl shadow-lg bg-white" aria-labelledby="attributions-heading">
            <h2 id="attributions-heading" class="text-3xl font-bold text-gray-800 mb-10 text-center">
                <i class="fas fa-medal mr-2 text-blue-600"></i> Attributions & Credits
            </h2>
            <div class="mb-10 p-6 bg-gray-50 rounded-lg shadow-inner">
                <h3 class="text-xl font-semibold text-gray-700 mb-6 text-center border-b pb-3">Icons <i class="fas fa-icons text-blue-500 ml-2"></i></h3>
                <div class="flex flex-wrap justify-center gap-10">
                    <?php 
                    $icons = [
                        ['src' => 'assets/images/SVG/bus.svg', 'alt' => 'Bus Icon', 'name' => 'Bus'],
                        ['src' => 'assets/images/SVG/bus_stop.svg', 'alt' => 'Bus Station Icon', 'name' => 'Bus Station'],
                        ['src' => 'assets/images/SVG/bus-arrived.svg', 'alt' => 'Bus Arrived Icon', 'name' => 'Bus Arrived'],
                    ];
                    foreach ($icons as $icon):
                    ?>
                        <div class="text-center w-20 p-2">
                            <img src="<?php echo htmlspecialchars($icon['src']); ?>" alt="<?php echo htmlspecialchars($icon['alt']); ?>" class="w-12 h-12 mx-auto mb-2 opacity-70 hover:opacity-100 transition-opacity duration-300" loading="lazy">
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($icon['name']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-gray-500 text-center mt-6 text-sm">
                    All icons are sourced from <a href="https://www.flaticon.com/" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium" rel="noopener">Flaticon</a>.
                </p>
            </div>
            <div class="p-6 bg-gray-50 rounded-lg shadow-inner">
                <h3 class="text-xl font-semibold text-gray-700 mb-6 text-center border-b pb-3">Photography <i class="fas fa-camera text-blue-500 ml-2"></i></h3>
                <div class="space-y-8">
                    <?php
                    $photos = [
                        ['img' => 'assets/images/Image/thumbnail.jpg', 'alt' => 'Yangon Downtown View', 'title' => 'Yangon Downtown View', 'photographer' => 'Zuyet Awarmatik', 'source' => 'Unsplash', 'link' => 'https://unsplash.com/photos/a-city-street-filled-with-lots-of-traffic-next-to-tall-buildings-493OOL5zTG0'],
                        ['img' => 'assets/images/Image/thumbnail (2).jpg', 'alt' => 'Yangon Night Vision', 'title' => 'Yangon Night Vision', 'photographer' => 'yenaingsince1993', 'source' => 'Pixabay', 'link' => 'https://pixabay.com/photos/yangon-myanmar-lake-downtown-3861650/'],
                        ['img' => 'assets/images/Image/thumbnail (3).jpg', 'alt' => 'Yangon Strand Road', 'title' => 'Yangon Strand Road', 'photographer' => 'Flo Dahm', 'source' => 'Pexels', 'link' => 'https://www.pexels.com/photo/assorted-vehicles-travelling-on-road-1137476/'],
                    ];
                    foreach ($photos as $i => $photo):
                    ?>
                        <div class="flex flex-col md:flex-row items-center gap-6 bg-white p-4 rounded-xl shadow-md border-l-4 border-blue-200 card-hover" data-aos="<?php echo $i % 2 == 0 ? 'fade-right' : 'fade-left'; ?>">
                            <div class="w-full md:w-1/3 flex-shrink-0">
                                <img src="<?php echo htmlspecialchars($photo['img']); ?>" alt="<?php echo htmlspecialchars($photo['alt']); ?>" class="w-full h-40 object-cover rounded-lg shadow-sm cursor-pointer hover:shadow-lg transition-shadow duration-300" onclick="openModal(this.src)" loading="lazy">
                            </div>
                            <div class="w-full md:w-2/3 text-center md:text-left space-y-1">
                                <p class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($photo['title']); ?></p>
                                <p class="text-md text-gray-600">Captured by <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($photo['photographer']); ?></span></p>
                                <p class="text-sm text-gray-500">
                                    Source: <a href="<?php echo htmlspecialchars($photo['link']); ?>" target="_blank" class="text-blue-500 hover:text-blue-700 underline" rel="noopener"><?php echo htmlspecialchars($photo['source']); ?></a>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <div id="imageModal" class="modal" role="dialog" aria-label="Image modal">
        <span class="modal-close" onclick="closeModal()" aria-label="Close modal">&times;</span>
        <img id="modalImage" class="modal-content" src="" alt="Full Image" loading="lazy">
    </div>

    <?php
    if (!file_exists('includes/footer.php')) {
        die('Error: Footer file not found.');
    }
    include('includes/footer.php');
    ?>

    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 900,
            once: true,
            easing: 'ease-in-out',
        });

        function openModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = src;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            modalImage.focus();
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('imageModal').classList.contains('show')) {
                closeModal();
            }
        });
    </script>
</body>
</html>