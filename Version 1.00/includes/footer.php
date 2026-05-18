<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include Tailwind CSS (same version as popup_ads.php) -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Include Font Awesome 6 only -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <title>Footer</title>
    <style>
        /* Footer gradient background */
        .footer-main {
            background: linear-gradient(160deg, #0a0e27 0%, #121a3a 40%, #0d1330 70%, #080c20 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated subtle glow orbs */
        .footer-main::before {
            content: '';
            position: absolute;
            top: -80px;
            left: -80px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatOrb 8s ease-in-out infinite;
        }
        .footer-main::after {
            content: '';
            position: absolute;
            bottom: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(234, 179, 8, 0.06) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatOrb 10s ease-in-out infinite reverse;
        }

        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, 20px) scale(1.1); }
        }

        /* Wave SVG divider */
        .footer-wave {
            position: relative;
            margin-top: -2px;
        }
        .footer-wave svg {
            display: block;
            width: 100%;
            height: 60px;
        }

        /* Glassmorphism card style */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            backdrop-filter: blur(12px);
            padding: 28px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Quick link hover animation */
        .quick-link {
            position: relative;
            padding-left: 0;
            transition: all 0.3s ease;
        }
        .quick-link::before {
            content: '';
            position: absolute;
            left: -16px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #eab308, transparent);
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        .quick-link:hover::before {
            width: 12px;
        }
        .quick-link:hover {
            padding-left: 6px;
            color: #facc15 !important;
        }

        /* Social icon glow effect */
        .social-btn {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #9ca3af;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.15rem;
        }
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .social-btn.fb:hover {
            background: rgba(59, 89, 152, 0.2);
            border-color: rgba(59, 89, 152, 0.5);
            color: #4a90d9;
            box-shadow: 0 8px 25px -5px rgba(59, 89, 152, 0.3);
        }
        .social-btn.tw:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            box-shadow: 0 8px 25px -5px rgba(255, 255, 255, 0.15);
        }
        .social-btn.ig:hover {
            background: linear-gradient(135deg, rgba(228, 64, 95, 0.15), rgba(188, 42, 141, 0.15));
            border-color: rgba(228, 64, 95, 0.4);
            color: #e4405f;
            box-shadow: 0 8px 25px -5px rgba(228, 64, 95, 0.25);
        }
        .social-btn.tg:hover {
            background: rgba(0, 136, 204, 0.15);
            border-color: rgba(0, 136, 204, 0.4);
            color: #0088cc;
            box-shadow: 0 8px 25px -5px rgba(0, 136, 204, 0.25);
        }

        /* Phone number pill style */
        .phone-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background: rgba(234, 179, 8, 0.06);
            border: 1px solid rgba(234, 179, 8, 0.12);
            border-radius: 999px;
            font-size: 0.9rem;
            color: #d1d5db;
            transition: all 0.3s ease;
        }
        .phone-pill:hover {
            background: rgba(234, 179, 8, 0.1);
            border-color: rgba(234, 179, 8, 0.25);
        }

        /* Bottom bar animated gradient line */
        .bottom-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(234, 179, 8, 0.3), rgba(59, 130, 246, 0.2), transparent);
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>

    <!-- Wave Divider -->
    <div class="footer-wave">
        <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,30 C360,60 720,0 1080,30 C1260,45 1380,35 1440,30 L1440,60 L0,60 Z" fill="#0a0e27"/>
        </svg>
    </div>

    <!-- footer.php -->
    <footer class="footer-main text-gray-100 pt-14 pb-8 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                <!-- Brand Section -->
                <div class="glass-card text-center">
                    <div class="flex flex-col items-center space-y-3 mb-5">
                        <img src="assets/images/Logo/YBS_Web_Logo.svg" alt="YBS Hub Logo" class="h-20 w-auto" style="filter: drop-shadow(0 0 12px rgba(234, 179, 8, 0.15));">
                        <h2 class="text-2xl font-bold text-white tracking-tight">Yangon Bus Service</h2>
                        <p class="text-gray-400 text-sm leading-relaxed">Your journey, your city, your YBS guide.</p>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-sm font-semibold text-yellow-400 uppercase tracking-widest mb-3">
                            <i class="fas fa-phone-alt mr-2 text-xs"></i>YRTC Report Lines
                        </h3>
                        <div class="space-y-2">
                            <span class="phone-pill"><i class="fas fa-phone text-yellow-500 mr-2 text-xs"></i>09 448 147 149</span>
                            <span class="phone-pill"><i class="fas fa-phone text-yellow-500 mr-2 text-xs"></i>09 448 147 153</span>
                            <span class="phone-pill"><i class="fas fa-phone text-yellow-500 mr-2 text-xs"></i>09 448 147 154</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="glass-card text-center md:text-left">
                    <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-widest mb-5">
                        <i class="fas fa-compass mr-2 text-xs text-yellow-400"></i>Quick Links
                    </h3>
                    <ul class="space-y-4 flex flex-col items-center md:items-start">
                        <li>
                            <a href="index" class="quick-link text-gray-400 transition-all duration-300 flex items-center text-base">
                                <i class="fas fa-home mr-3 text-yellow-500 text-sm w-5 text-center"></i> Home
                            </a>
                        </li>
                        <li>
                            <a href="BusList" class="quick-link text-gray-400 transition-all duration-300 flex items-center text-base">
                                <i class="fas fa-bus mr-3 text-yellow-500 text-sm w-5 text-center"></i> Bus List
                            </a>
                        </li>
                        <li>
                            <a href="Contact_Us" class="quick-link text-gray-400 transition-all duration-300 flex items-center text-base">
                                <i class="fas fa-envelope mr-3 text-yellow-500 text-sm w-5 text-center"></i> Contact Us
                            </a>
                        </li>
                        <li>
                            <a href="About_Us" class="quick-link text-gray-400 transition-all duration-300 flex items-center text-base">
                                <i class="fas fa-info-circle mr-3 text-yellow-500 text-sm w-5 text-center"></i> About Us
                            </a>
                        </li>
                        <li>
                            <a href="T&C" class="quick-link text-gray-400 transition-all duration-300 flex items-center text-base">
                                <i class="fas fa-book mr-3 text-yellow-500 text-sm w-5 text-center"></i> Terms & Conditions
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Social & Connect -->
                <div class="glass-card text-center md:text-right">
                    <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-widest mb-5">
                        <i class="fas fa-share-alt mr-2 text-xs text-yellow-400"></i>Connect With Us
                    </h3>
                    <div class="flex justify-center md:justify-end space-x-3 mb-6">
                        <a href="https://facebook.com" target="_blank" class="social-btn fb" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://x.com" target="_blank" class="social-btn tw" aria-label="X (Twitter)">
                            <i class="fab fa-x-twitter"></i>
                        </a>
                        <a href="https://instagram.com" target="_blank" class="social-btn ig" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://telegram.org" target="_blank" class="social-btn tg" aria-label="Telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    </div>

                    <!-- Mini app info -->
                    <div class="mt-4 pt-4 border-t border-gray-800">
                        <p class="text-xs text-gray-500 leading-relaxed">
                            <i class="fas fa-map-marked-alt mr-1 text-yellow-600"></i>
                            Helping commuters navigate Yangon's bus network with up-to-date routes and schedules.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Bottom Bar -->
            <div class="mt-10">
                <div class="bottom-divider mb-5"></div>
                <div class="flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 space-y-2 sm:space-y-0">
                    <p>© <?php echo date("Y"); ?> Yangon Bus Service Route. All rights reserved. <span class="ml-2 px-2 py-0.5 bg-gray-800 rounded text-gray-400">Version 1.00</span></p>
                    <div class="flex space-x-5">
                        <a href="T&C" class="hover:text-gray-300 transition-colors duration-300">Terms</a>
                        <a href="Contact_Us" class="hover:text-gray-300 transition-colors duration-300">Contact</a>
                        <a href="About_Us" class="hover:text-gray-300 transition-colors duration-300">About</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>